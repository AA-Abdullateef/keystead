<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tokens are stored as SHA-256 hashes (the plain value only ever exists in the e-mail link),
        // are single-use, and expire. `email_verified_at` already exists on users.
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_token', 64)->nullable()->index()->after('remember_token');
            $table->timestamp('registration_token_expires_at')->nullable()->after('registration_token');
            $table->timestamp('registration_token_sent_at')->nullable()->after('registration_token_expires_at');
            $table->string('password_reset_token', 64)->nullable()->index()->after('registration_token_sent_at');
            $table->timestamp('password_reset_token_expires_at')->nullable()->after('password_reset_token');
            $table->timestamp('password_reset_token_sent_at')->nullable()->after('password_reset_token_expires_at');
            $table->timestamp('password_changed_at')->nullable()->after('password_reset_token_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_token', 'registration_token_expires_at', 'registration_token_sent_at',
                'password_reset_token', 'password_reset_token_expires_at', 'password_reset_token_sent_at',
                'password_changed_at',
            ]);
        });
    }
};
