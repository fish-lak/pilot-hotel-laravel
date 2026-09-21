<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\NoShowService;
use App\Support\RoomCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request, NoShowService $noShowService): View
    {
        $noShowService->markExpired();
        $filters = $this->filters($request);
        $reportType = $request->input('report', 'reservations');
        $reservations = $this->reservations($filters, $reportType);

        return view('reports.index', [
            'reservations' => $reservations,
            'filters' => $filters,
            'reportType' => $reportType,
            'reportTitle' => $this->title($reportType),
            'summary' => $this->summary($filters),
            'revenue' => $this->revenue($reservations),
            'occupancy' => $this->occupancy(),
        ]);
    }

    public function exportExcel(Request $request, NoShowService $noShowService): Response
    {
        $noShowService->markExpired();
        $reportType = $request->input('report', 'reservations');
        $reservations = $this->reservations($this->filters($request), $reportType);
        $headers = ['Reservation ID', 'Guest Name', 'Contact Number', 'Room Number', 'Room Type', 'Guests', 'Booking Date', 'Check-In Date', 'Check-Out Date', 'Total Amount', 'Amount Paid', 'Balance', 'Payment Status', 'Reservation Status'];
        $lines = [implode(',', $headers)];
        foreach ($reservations as $reservation) {
            $lines[] = implode(',', array_map(fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', [
                $reservation->code, $reservation->guest_name, $reservation->guest_phone, $reservation->room,
                $reservation->room_type, $reservation->adults + $reservation->children, $reservation->created_at?->format('Y-m-d'),
                $reservation->check_in->format('Y-m-d'), $reservation->check_out->format('Y-m-d'), $reservation->total_amount,
                $reservation->amount_paid, $reservation->balance, $reservation->payment_status, $reservation->status,
            ]));
        }

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pilot-hotel-' . $reportType . '-report.csv"',
        ]);
    }

    public function print(Request $request, NoShowService $noShowService): View
    {
        $noShowService->markExpired();
        $filters = $this->filters($request);
        $reportType = $request->input('report', 'reservations');

        return view('reports.print', [
            'reservations' => $this->reservations($filters, $reportType),
            'filters' => $filters,
            'reportTitle' => $this->title($reportType),
            'reportType' => $reportType,
        ]);
    }

    private function reservations(array $filters, string $reportType)
    {
        $query = Reservation::query()->orderByDesc('check_in');
        $dateColumn = match ($reportType) {
            'check-ins' => 'checked_in_at',
            'check-outs' => 'checked_out_at',
            'no-shows' => 'no_show_at',
            'cancellations' => 'cancelled_at',
            default => 'created_at',
        };
        if ($reportType === 'no-shows') {
            $query->whereNotNull('no_show_at');
        } elseif ($reportType === 'cancellations') {
            $query->whereNotNull('cancelled_at');
        }
        if (in_array($reportType, ['check-ins', 'check-outs', 'no-shows', 'cancellations'], true)) {
            $query->whereBetween($dateColumn, [$filters['from'], $filters['to']->endOfDay()]);
        } else {
            $query->whereDate($dateColumn, '>=', $filters['from']->toDateString())
                ->whereDate($dateColumn, '<=', $filters['to']->toDateString());
        }
        if ($reportType === 'check-ins') {
            $query->whereNotNull('checked_in_at');
        }
        if ($reportType === 'check-outs') {
            $query->whereNotNull('checked_out_at');
        }
        $query->when($filters['search'], fn ($q) => $q->where(function ($q) use ($filters) {
            $q->where('guest_name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('code', 'like', '%' . $filters['search'] . '%')
                ->orWhere('room', 'like', '%' . $filters['search'] . '%');
        }));
        $query->when($filters['status'], fn ($q) => $q->where('status', $filters['status']));
        $query->when($filters['payment_status'], fn ($q) => $q->where('payment_status', $filters['payment_status']));
        $query->when($filters['payment_method'], fn ($q) => $q->where('payment_method', $filters['payment_method']));

        return $query->get()->each(function (Reservation $reservation): void {
            $room = RoomCatalog::find($reservation->room);
            $reservation->room_type = $room['name'] ?? $reservation->room;
            $reservation->total_amount = (float) $reservation->amount + (float) $reservation->additional_charges - (float) $reservation->discount;
            $reservation->balance = max($reservation->total_amount - (float) $reservation->amount_paid, 0);
        });
    }

    private function filters(Request $request): array
    {
        $preset = $request->input('period', 'today');
        [$from, $to] = match ($preset) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [Carbon::parse($request->input('from', today()->toDateString()))->startOfDay(), Carbon::parse($request->input('to', today()->toDateString()))->endOfDay()],
            default => [today()->startOfDay(), today()->endOfDay()],
        };

        return compact('preset', 'from', 'to') + [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'payment_method' => $request->input('payment_method'),
        ];
    }

    private function summary(array $filters): array
    {
        return [
            'reservations' => Reservation::whereBetween('created_at', [$filters['from'], $filters['to']->endOfDay()])->count(),
            'check_ins' => Reservation::whereDate('checked_in_at', $filters['from'])->count(),
            'check_outs' => Reservation::whereDate('checked_out_at', $filters['from'])->count(),
            'no_shows' => Reservation::whereNotNull('no_show_at')->whereBetween('no_show_at', [$filters['from'], $filters['to']->endOfDay()])->count(),
            'cancelled' => Reservation::whereNotNull('cancelled_at')->whereBetween('cancelled_at', [$filters['from'], $filters['to']->endOfDay()])->count(),
            'revenue' => (float) Reservation::whereBetween('created_at', [$filters['from'], $filters['to']->endOfDay()])->sum('amount_paid'),
        ];
    }

    private function revenue($reservations): array
    {
        return [
            'room' => $reservations->sum('amount'),
            'additional' => $reservations->sum('additional_charges'),
            'discounts' => $reservations->sum('discount'),
            'collected' => $reservations->sum('amount_paid'),
            'outstanding' => $reservations->sum('balance'),
            'refunds' => $reservations->sum('refund_amount'),
        ];
    }

    private function occupancy(): array
    {
        $rooms = collect(RoomCatalog::withAvailability())->flatMap(fn (array $room) => collect($room['room_statuses'])->map(fn (array $status) => [
            'number' => $status['number'],
            'type' => $room['name'],
            'status' => $status['available'] ? 'Available' : 'Reserved / Occupied',
        ]));
        $total = $rooms->count();
        $available = $rooms->where('status', 'Available')->count();
        return ['rooms' => $rooms, 'total' => $total, 'available' => $available, 'reserved' => $total - $available, 'occupied' => Reservation::where('status', 'Checked-In')->count(), 'rate' => $total ? round((($total - $available) / $total) * 100, 1) : 0];
    }

    private function title(string $reportType): string
    {
        return match ($reportType) {
            'check-ins' => 'Check-In Report', 'check-outs' => 'Check-Out Report', 'no-shows' => 'No-Show Report',
            'cancellations' => 'Cancellation Report', 'revenue' => 'Revenue Report', 'occupancy' => 'Room Occupancy Report',
            default => 'Reservation Report',
        };
    }
}