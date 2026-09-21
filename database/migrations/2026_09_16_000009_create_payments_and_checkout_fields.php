<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('checkout_processed_by')->nullable()->after('checked_out_at');
            $table->text('checkout_authorization_reason')->nullable()->after('checkout_processed_by');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->timestamp('paid_at');
            $table->string('reference')->nullable();
            $table->string('recorded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['checkout_processed_by', 'checkout_authorization_reason']);
        });
    }
};