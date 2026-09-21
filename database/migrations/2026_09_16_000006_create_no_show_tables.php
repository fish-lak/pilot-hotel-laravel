<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('no_show_policies', function (Blueprint $table) {
            $table->id();
            $table->string('check_in_deadline')->default('14:00');
            $table->string('payment_action')->default('retain');
            $table->unsignedTinyInteger('payment_retention_percent')->default(100);
            $table->timestamps();
        });

        Schema::create('booking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->string('changed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_histories');
        Schema::dropIfExists('no_show_policies');
    }
};