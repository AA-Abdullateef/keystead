<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['investment', 'loan']);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->enum('status', ['available', 'active', 'completed'])->default('available');
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
