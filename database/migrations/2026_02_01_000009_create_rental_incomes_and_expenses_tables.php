<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One income row per project and month. Both tables are immutable once that month is distributed.
        Schema::create('rental_incomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('investment_project_id')->constrained('investment_projects')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignUuid('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['investment_project_id', 'month', 'year']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('investment_project_id')->constrained('investment_projects')->restrictOnDelete();
            $table->string('type', 100);
            $table->decimal('amount', 15, 2);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->text('note')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->timestamps();

            $table->unique(['investment_project_id', 'month', 'year', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('rental_incomes');
    }
};
