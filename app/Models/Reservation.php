<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const STATUSES = [
        'Pending',
        'Confirmed',
        'Checked-In',
        'Checked-Out',
        'Cancelled',
        'No-Show',
    ];

    public const ACTIVE_STATUSES = ['Pending', 'Confirmed', 'Checked-In'];

    protected $fillable = [
        'code', 'guest_name', 'guest_email', 'guest_phone', 'guest_country', 'room', 'room_count', 'room_details', 'check_in', 'check_out',
        'status', 'booking_type', 'amount', 'payment_status', 'additional_charges', 'discount', 'amount_paid',
        'remaining_balance',
        'payment_method', 'booking_source', 'staff_name', 'adults', 'children', 'special_requests',
        'remarks', 'checked_in_at', 'checked_out_at', 'no_show_at', 'no_show_remarks',
        'cancelled_at', 'cancellation_reason', 'refund_amount',
        'checkout_processed_by', 'checkout_authorization_reason',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'room_count' => 'integer',
            'room_details' => 'array',
            'amount' => 'decimal:2',
            'additional_charges' => 'decimal:2',
            'discount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'no_show_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function bookingHistory()
    {
        return $this->hasMany(BookingHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
