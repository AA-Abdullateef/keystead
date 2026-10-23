<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('occurred_at', 6)->index();
            $table->uuid('actor_id')->nullable()->index();      // no FK: the trail must outlive users
            $table->string('actor_label', 255);                 // name at the time, or "system"
            $table->string('actor_role', 40)->nullable();
            $table->string('event', 80)->index();               // e.g. loan.approved
            $table->string('subject_type', 60)->nullable();
            $table->uuid('subject_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();                // ip, user agent, route
            $table->index(['subject_type', 'subject_id']);
        });

        $this->guard();
    }

    /** Append-only at the database level: no UPDATE, no DELETE, whatever the application does. */
    private function guard(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END");
        } else {
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_delete');
        Schema::dropIfExists('audit_logs');
    }
};
