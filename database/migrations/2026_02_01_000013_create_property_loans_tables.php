<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('loan_project_id')->constrained('loan_projects')->restrictOnDelete();
            // Terms are copied from the loan project at application time and never change afterwards.
            $table->decimal('property_value', 15, 2);
            $table->decimal('initial_payment', 15, 2)->default(0);
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->unsignedSmallInteger('duration_months');
            $table->decimal('total_payable', 15, 2);
            $table->decimal('monthly_payment', 15, 2);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('balance_remaining', 15, 2);
            $table->enum('status', ['pending', 'active', 'overdue', 'completed', 'rejected', 'cancelled', 'defaulted'])->default('pending');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('property_loan_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_loan_id')->constrained('property_loans')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->char('period', 7); // YYYY-MM; one installment per calendar month, enforced here
            $table->string('method', 40)->nullable();
            $table->string('reference', 64)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('confirmed');
            $table->timestamps();

            $table->unique(['property_loan_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_loan_payments');
        Schema::dropIfExists('property_loans');
    }
};
