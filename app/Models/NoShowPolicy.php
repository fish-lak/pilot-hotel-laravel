<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoShowPolicy extends Model
{
    protected $fillable = ['check_in_deadline', 'payment_action', 'payment_retention_percent'];

    protected function casts(): array
    {
        return ['payment_retention_percent' => 'integer'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'check_in_deadline' => '14:00',
            'payment_action' => 'retain',
            'payment_retention_percent' => 100,
        ]);
    }
}