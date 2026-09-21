<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('additional_charges', 10, 2)->default(0)->after('payment_status');
            $table->decimal('discount', 10, 2)->default(0)->after('additional_charges');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('discount');
            $table->string('payment_method')->nullable()->after('amount_paid');
            $table->string('booking_source')->default('Front Desk')->after('payment_method');
            $table->string('staff_name')->default('Sofia Anderson')->after('booking_source');
            $table->text('remarks')->nullable()->after('special_requests');
            $table->timestamp('no_show_at')->nullable()->after('checked_out_at');
            $table->text('no_show_remarks')->nullable()->after('no_show_at');
            $table->timestamp('cancelled_at')->nullable()->after('no_show_remarks');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'additional_charges', 'discount', 'amount_paid', 'payment_method',
                'booking_source', 'staff_name', 'remarks', 'no_show_at', 'no_show_remarks',
                'cancelled_at', 'cancellation_reason', 'refund_amount',
            ]);
        });
    }
};