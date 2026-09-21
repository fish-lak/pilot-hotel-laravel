<?php

namespace App\Services;

use App\Models\BookingHistory;
use App\Models\NoShowPolicy;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class NoShowService
{
    public function __construct(private FirebaseRealtimeService $firebase)
    {
    }

    public function markExpired(): int
    {
        $policy = NoShowPolicy::current();
        $count = 0;

        Reservation::query()
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->get()
            ->each(function (Reservation $reservation) use ($policy, &$count): void {
                $deadline = Carbon::parse($reservation->check_in->format('Y-m-d') . ' ' . $policy->check_in_deadline);
                if (now()->gte($deadline)) {
                    $this->markNoShow($reservation, 'Check-in deadline passed');
                    $count++;
                }
            });

        return $count;
    }

    public function markNoShow(Reservation $reservation, string $reason = 'Manually marked by front desk'): void
    {
        if (in_array($reservation->status, ['Checked-In', 'Checked-Out', 'Cancelled', 'No-Show'], true)) {
            return;
        }

        $fromStatus = $reservation->status;
        $reservation->update([
            'status' => 'No-Show',
            'payment_status' => $this->paymentStatus(),
            'no_show_at' => now(),
            'no_show_remarks' => $reason,
        ]);
        BookingHistory::create([
            'reservation_id' => $reservation->id,
            'from_status' => $fromStatus,
            'to_status' => 'No-Show',
            'reason' => $reason,
            'changed_by' => 'front desk',
        ]);
        $this->firebase->syncReservation($reservation->fresh());
    }

    private function paymentStatus(): string
    {
        return match (NoShowPolicy::current()->payment_action) {
            'release' => 'refunded',
            'partial' => 'partially-retained',
            default => 'retained',
        };
    }
}