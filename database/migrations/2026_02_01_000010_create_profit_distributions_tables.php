<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('investment_project_id')->constrained('investment_projects')->restrictOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('total_income', 15, 2);
            $table->decimal('total_expenses', 15, 2);
            $table->decimal('management_fee_amount', 15, 2);
            $table->decimal('net_profit', 15, 2);
            $table->decimal('distributable_profit', 15, 2);
            // distributable_profit = investor_total + company_share (exact; see ProfitDistributionService)
            $table->decimal('investor_total', 15, 2)->default(0);
            $table->decimal('company_share', 15, 2)->default(0);
            $table->enum('status', ['pending', 'distributed'])->default('pending');
            $table->timestamp('distributed_at')->nullable();
            $table->foreignUuid('distributed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Database-level guard against double distribution of a month.
            $table->unique(['investment_project_id', 'month', 'year']);
        });

        Schema::create('user_profit_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('profit_distribution_id')->constrained('profit_distributions')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('share_percent', 9, 6);
            $table->timestamps();

            $table->unique(['profit_distribution_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('type', 40); // profit, bonus, adjustment
            $table->string('reference_type', 40);
            $table->uuid('reference_id');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'reference_type', 'reference_id']);
            $table->index(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings');
        Schema::dropIfExists('user_profit_shares');
        Schema::dropIfExists('profit_distributions');
    }
};
