<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingHistory extends Model
{
    protected $fillable = ['reservation_id', 'from_status', 'to_status', 'reason', 'changed_by'];
}