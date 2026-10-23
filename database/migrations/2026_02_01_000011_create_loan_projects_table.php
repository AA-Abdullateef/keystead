<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();
            $table->enum('loan_type', ['mortgage', 'property']);
            $table->decimal('property_value', 15, 2);
            $table->decimal('min_initial_payment', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0); // flat percentage on the financed amount
            $table->unsignedSmallInteger('duration_months');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['loan_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_projects');
    }
};
