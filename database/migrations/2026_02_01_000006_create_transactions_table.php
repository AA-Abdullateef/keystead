<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only ledger. Every wallet balance change has exactly one row here.
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['credit', 'debit']);
            $table->string('type', 40);
            $table->string('reference_type', 40);
            $table->uuid('reference_id');
            $table->enum('status', ['pending', 'completed', 'rejected', 'failed'])->default('completed');
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
