<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->ulid();

            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // user who made payment

            $table->string('reference')->unique(); // Payment gateway reference
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('NGN');
            $table->string('status'); // pending, success, failed, cancelled
            $table->string('payment_gateway')->nullable(); // e.g., paystack
            $table->text('meta')->nullable(); // JSON response from gateway

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
