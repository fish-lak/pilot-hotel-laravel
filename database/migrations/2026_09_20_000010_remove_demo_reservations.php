<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservations')
            ->whereIn('code', ['RES-4821', 'RES-4822', 'RES-4823', 'RES-4824'])
            ->delete();
    }

    public function down(): void
    {
        // Demo reservations are intentionally not restored.
    }
};