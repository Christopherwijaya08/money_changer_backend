<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_rate_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_buy', 15, 2);
            $table->decimal('old_sell', 15, 2);
            $table->decimal('new_buy', 15, 2);
            $table->decimal('new_sell', 15, 2);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
    }
};
