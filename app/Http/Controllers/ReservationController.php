<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Payment;
use App\Models\NoShowPolicy;
use App\Services\FirebaseRealtimeService;
use App\Services\NoShowService;
use App\Support\RoomCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        app(NoShowService::class)->markExpired();
        $reservations = Reservation::query()
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('guest_name', 'like', '%' . $request->search . '%')
                    ->orWhere('room', 'like', '%' . $request->search . '%');
            }))
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->latest('check_in')->get();

        return view('reservations.index', ['reservations' => $reservations, 'policy' => NoShowPolicy::current()]);
    }

    public function store(Request $request, FirebaseRealtimeService $firebase): RedirectResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email'],
            'guest_phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'guest_country' => ['nullable', 'string', 'max:80'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'rooms' => ['required', 'array', 'min:1', 'max:10'],
            'rooms.*.type' => ['required', 'string', 'in:' . collect(RoomCatalog::all())->pluck('name')->implode(',')],
            'rooms.*.number' => ['required', 'string'],
            'room_count' => ['required', 'integer', 'min:1', 'max:10'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'special_requests' => ['nullable', 'string'],
            'booking_type' => ['required', Rule::in(['reservation', 'check_in_now'])],
            'amount_paid' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['Cash', 'GCash', 'Bank Transfer', 'Credit/Debit Card', 'Other'])],
            'remarks' => ['nullable', 'string'],
        ], [
            'guest_phone.regex' => 'Please enter a valid Philippine mobile number using 11 digits (09XXXXXXXXX).',
            'adults.integer' => 'Number of adults must be a whole number.',
            'adults.min' => 'Number of adults must be at least 1.',
            'children.integer' => 'Number of children must be a whole number.',
            'children.min' => 'Number of children cannot be negative.',
            'amount_paid.regex' => 'Payment amount must be a valid non-negative amount with up to two decimal places.',
        ]);

        $data['guest_phone'] = $this->formatPhilippineMobile($data['guest_phone'] ?? null);
        $roomDetails = $this->validateRoomSelections($data['rooms']);
        if ($roomDetails['error']) {
            return back()->withErrors(['rooms' => $roomDetails['error']])->withInput();
        }
        $roomDetails = $roomDetails['rooms'];
        if (count($roomDetails) !== (int) $data['room_count']) {
            return back()->withErrors(['room_count' => 'Select one room for each room count.'])->withInput();
        }
        $availableLabels = collect(RoomCatalog::withAvailability())->flatMap(fn (array $room) => collect($room['room_statuses'])->filter(fn (array $status) => $status['available'])->map(fn (array $status) => $room['name'] . ' - ' . $status['number']))->all();
        if (array_diff(collect($roomDetails)->pluck('label')->all(), $availableLabels)) {
            return back()->withErrors(['rooms' => 'One or more selected physical rooms are no longer available.'])->withInput();
        }
        $room = RoomCatalog::find($roomDetails[0]['label']);
        $guestCount = (int) $data['adults'] + (int) ($data['children'] ?? 0);
        $capacity = collect($roomDetails)->sum(fn (array $detail) => RoomCatalog::find($detail['type'])['max_guests'] ?? 0);
        if ($room && $guestCount > $capacity) {
            return back()->withErrors(['adults' => 'Number of guests exceeds the maximum capacity of this room.'])->withInput();
        }
        $codeNumber = 4820 + Reservation::count() + 1;
        do {
            $data['code'] = 'RES-' . $codeNumber++;
        } while (Reservation::where('code', $data['code'])->exists());
        $data['room'] = $roomDetails[0]['label'];
        $data['room_details'] = $roomDetails;
        $data['amount'] = $this->calculateRoomsAmount($roomDetails, $data['check_in'], $data['check_out']);
        $data['booking_type'] = $data['booking_type'];
        $data['status'] = $data['booking_type'] === 'check_in_now' ? 'Checked-In' : 'Confirmed';
        $data['amount_paid'] = (float) ($data['amount_paid'] ?? 0);
        if ($data['amount_paid'] > $data['amount']) {
            return back()->withErrors(['amount_paid' => 'Amount paid cannot be greater than the total amount.'])->withInput();
        }
        if ($data['amount_paid'] > 0 && empty($data['payment_method'])) {
            return back()->withErrors(['payment_method' => 'Select a payment method when a payment is recorded.'])->withInput();
        }
        $data['remaining_balance'] = max($data['amount'] - $data['amount_paid'], 0);
        $data['payment_status'] = $this->paymentStatus($data['amount'], $data['amount_paid']);
        $data['payment_method'] = $data['amount_paid'] > 0 ? ($data['payment_method'] ?? null) : null;
        $data['staff_name'] = $request->session()->get('staff_name', 'Sofia Anderson');
        if ($data['booking_type'] === 'check_in_now') {
            $data['checked_in_at'] = now();
        }
        $reservation = Reservation::create($data);
        if ($data['amount_paid'] > 0) {
            $reservation->payments()->create([
                'amount' => $data['amount_paid'],
                'payment_method' => $data['payment_method'],
                'paid_at' => now(),
                'recorded_by' => $data['staff_name'],
            ]);
        }
        $firebase->syncReservation($reservation->fresh());

        return to_route('reservations.index')->with('success', 'Reservation created.');
    }

    public function update(Request $request, Reservation $reservation, FirebaseRealtimeService $firebase): RedirectResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'room' => ['required', 'string', 'in:' . collect(RoomCatalog::roomOptions())->pluck('value')->implode(',')],
            'room_count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after_or_equal:check_in'],
            'status' => ['required', Rule::in(['Pending', 'Confirmed', 'Checked-In', 'Cancelled', 'No-Show'])],
        ]);
        $data['room_count'] = (int) ($data['room_count'] ?? $reservation->room_count ?? 1);
        $data['amount'] = $this->calculateAmount($data['room'], $data['room_count'], $data['check_in'], $data['check_out']);
        if ($data['status'] === 'Checked-In' && !$reservation->checked_in_at) {
            $data['checked_in_at'] = now();
        }
        if ($data['status'] === 'Checked-Out' && !$reservation->checked_out_at) {
            $data['checked_out_at'] = now();
        }
        $reservation->update($data);
        $firebase->syncReservation($reservation->fresh());

        return to_route('reservations.index')->with('success', 'Reservation updated.');
    }

    public function addPayment(Request $request, Reservation $reservation, FirebaseRealtimeService $firebase): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash', 'Bank Transfer', 'Credit/Debit Card', 'Other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);
        $total = $this->totalAmount($reservation);
        $paid = (float) $reservation->amount_paid;
        if ($paid + (float) $data['amount'] > $total) {
            return back()->withErrors(['payment' => 'Payment cannot be greater than the outstanding balance.'])->withInput();
        }
        $data['recorded_by'] = $request->session()->get('staff_name', 'Sofia Anderson');
        $payment = $reservation->payments()->create($data);
        $this->syncPaymentTotals($reservation);
        $reservation->bookingHistory()->create([
            'from_status' => $reservation->status,
            'to_status' => $reservation->status,
            'reason' => 'Payment recorded: ₱' . number_format((float) $payment->amount, 2),
            'changed_by' => $data['recorded_by'],
        ]);
        $firebase->syncReservation($reservation->fresh());

        return to_route('reservations.index')->with('success', 'Payment recorded successfully.');
    }

    public function checkOut(Request $request, Reservation $reservation, FirebaseRealtimeService $firebase): RedirectResponse
    {
        if ($reservation->status !== 'Checked-In') {
            return back()->withErrors(['checkout' => 'Only checked-in guests can be checked out.']);
        }
        $data = $request->validate([
            'authorization_reason' => ['nullable', 'string', 'max:500'],
            'confirm_checkout' => ['accepted'],
        ]);
        $this->syncPaymentTotals($reservation->fresh());
        $reservation->refresh();
        $balance = (float) $reservation->remaining_balance;
        if ($balance > 0 && blank($data['authorization_reason'] ?? null)) {
            return back()->withErrors(['authorization_reason' => 'An authorization reason is required to check out with an outstanding balance.'])->withInput();
        }
        $staff = $request->session()->get('staff_name', 'Sofia Anderson');
        $reservation->update([
            'status' => 'Checked-Out',
            'checked_out_at' => now(),
            'checkout_processed_by' => $staff,
            'checkout_authorization_reason' => $data['authorization_reason'] ?? null,
        ]);
        $reservation->bookingHistory()->create([
            'from_status' => 'Checked-In',
            'to_status' => 'Checked-Out',
            'reason' => $balance > 0 ? 'Checked out with outstanding balance: ' . number_format($balance, 2) : 'Fully paid checkout',
            'changed_by' => $staff,
        ]);
        $firebase->syncReservation($reservation->fresh());

        return to_route('reservations.index')->with('success', 'Guest checked out successfully.');
    }

    public function markNoShow(Reservation $reservation, NoShowService $noShowService, FirebaseRealtimeService $firebase): RedirectResponse
    {
        $noShowService->markNoShow($reservation);
        $firebase->syncReservation($reservation->fresh());

        return to_route('reservations.index')->with('success', 'Reservation marked as No-Show and room released.');
    }

    public function updatePolicy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'check_in_deadline' => ['required', 'date_format:H:i'],
            'payment_action' => ['required', 'in:retain,release,partial'],
            'payment_retention_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        NoShowPolicy::current()->update($data);

        return to_route('reservations.index')->with('success', 'No-Show policy updated.');
    }

    private function calculateAmount(string $room, int $roomCount, string $checkIn, string $checkOut): int
    {
        $nightlyRate = RoomCatalog::find($room)['rate_min'] ?? 0;
        $nights = max(Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)), 1);

        return $nightlyRate * $nights * $roomCount;
    }

    private function calculateRoomsAmount(array $rooms, string $checkIn, string $checkOut): int
    {
        $nights = max(Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)), 1);

        return collect($rooms)->sum('rate') * $nights;
    }

    private function validateRoomSelections(array $selections): array
    {
        $details = [];
        $labels = [];
        foreach ($selections as $selection) {
            $room = RoomCatalog::find($selection['type']);
            if (!$room || !in_array($selection['number'], $room['room_numbers'], true)) {
                return ['rooms' => [], 'error' => 'Each room number must belong to its selected room type.'];
            }
            $label = $room['name'] . ' - ' . $selection['number'];
            if (in_array($label, $labels, true)) {
                return ['rooms' => [], 'error' => 'The same physical room cannot be selected twice.'];
            }
            $labels[] = $label;
            $details[] = ['type' => $room['name'], 'number' => $selection['number'], 'label' => $label, 'rate' => (float) ($room['rate_min'] ?? 0)];
        }

        return ['rooms' => $details, 'error' => null];
    }

    private function formatPhilippineMobile(?string $phone): ?string
    {
        return $phone;
    }

    private function paymentStatus(float $total, float $paid): string
    {
        return $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partially-paid');
    }

    private function totalAmount(Reservation $reservation): float
    {
        return (float) $reservation->amount + (float) $reservation->additional_charges - (float) $reservation->discount;
    }

    private function syncPaymentTotals(Reservation $reservation): void
    {
        $paid = $reservation->payments()->exists()
            ? (float) $reservation->payments()->sum('amount')
            : (float) $reservation->amount_paid;
        $total = $this->totalAmount($reservation);
        $reservation->update([
            'amount_paid' => $paid,
            'remaining_balance' => max($total - $paid, 0),
            'payment_status' => $this->paymentStatus($total, $paid),
        ]);
    }
}
