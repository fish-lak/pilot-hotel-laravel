<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservations')->where('status', 'Arriving')->update(['status' => 'Confirmed']);
        DB::table('reservations')->where('status', 'Checked in')->update(['status' => 'Checked-In']);
        DB::table('reservations')->where('status', 'Checked out')->update(['status' => 'Checked-Out']);
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};