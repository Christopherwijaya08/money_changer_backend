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
            $table->string('transaction_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['buy', 'sell']);
            $table->foreignId('currency_id')->constrained();
            $table->decimal('amount', 18, 2);
            $table->decimal('rate_default', 15, 2);
            $table->decimal('rate_actual', 15, 2);
            $table->decimal('total_amount', 18, 2);
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->text('note')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
