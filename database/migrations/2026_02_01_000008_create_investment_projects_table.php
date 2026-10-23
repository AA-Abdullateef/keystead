<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();
            $table->decimal('target_amount', 15, 2);
            $table->decimal('min_investment', 15, 2);
            $table->decimal('management_fee_percent', 5, 2)->default(0);
            $table->decimal('expected_roi', 5, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['open', 'funded', 'distributing', 'closed'])->default('open');
            $table->decimal('total_raised', 15, 2)->default(0);
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('investment_project_id')->constrained('investment_projects')->restrictOnDelete();
            $table->decimal('total_invested', 15, 2);
            // Share of the project TARGET (not of the amount raised so far); 4 dp so 33.3333% survives.
            $table->decimal('ownership_percent', 7, 4);
            $table->enum('status', ['active', 'exited'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'investment_project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investment_projects');
    }
};
