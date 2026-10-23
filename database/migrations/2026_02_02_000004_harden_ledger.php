<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->unsignedBigInteger('ledger_seq')->default(0)->after('balance'); // last sequence handed out
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Gapless per-user sequence, assigned under the wallet lock: lets the reconciler prove the chain.
            $table->unsignedBigInteger('user_seq')->nullable()->after('user_id');
            // "Only once" key for movements that must never be duplicated (refunds, profit credits, installments).
            $table->string('dedupe_key', 120)->nullable()->unique()->after('description');
            $table->unique(['user_id', 'user_seq']);
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER wallets_non_negative BEFORE UPDATE ON wallets WHEN NEW.balance < 0 BEGIN SELECT RAISE(ABORT, 'wallet balance cannot be negative'); END");
            DB::unprepared("CREATE TRIGGER transactions_no_delete BEFORE DELETE ON transactions BEGIN SELECT RAISE(ABORT, 'ledger rows cannot be deleted'); END");
            DB::unprepared("CREATE TRIGGER transactions_immutable BEFORE UPDATE ON transactions WHEN
                NEW.user_id IS NOT OLD.user_id OR NEW.amount IS NOT OLD.amount OR NEW.direction IS NOT OLD.direction OR NEW.type IS NOT OLD.type
                OR NEW.reference_type IS NOT OLD.reference_type OR NEW.reference_id IS NOT OLD.reference_id
                OR NEW.balance_after IS NOT OLD.balance_after OR NEW.user_seq IS NOT OLD.user_seq OR NEW.dedupe_key IS NOT OLD.dedupe_key
                BEGIN SELECT RAISE(ABORT, 'ledger rows are immutable; only status may change'); END");
        } else {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT wallets_balance_non_negative CHECK (balance >= 0)');
            DB::unprepared("CREATE TRIGGER transactions_no_delete BEFORE DELETE ON transactions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ledger rows cannot be deleted'");
            DB::unprepared("CREATE TRIGGER transactions_immutable BEFORE UPDATE ON transactions FOR EACH ROW
                BEGIN
                    IF NOT (NEW.user_id <=> OLD.user_id AND NEW.amount <=> OLD.amount AND NEW.direction <=> OLD.direction AND NEW.type <=> OLD.type
                        AND NEW.reference_type <=> OLD.reference_type AND NEW.reference_id <=> OLD.reference_id
                        AND NEW.balance_after <=> OLD.balance_after AND NEW.user_seq <=> OLD.user_seq AND NEW.dedupe_key <=> OLD.dedupe_key) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ledger rows are immutable; only status may change';
                    END IF;
                END");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS transactions_immutable');
        DB::unprepared('DROP TRIGGER IF EXISTS transactions_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS wallets_non_negative');
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE wallets DROP CONSTRAINT wallets_balance_non_negative');
        }
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'user_seq']);
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn(['user_seq', 'dedupe_key']);
        });
        Schema::table('wallets', fn (Blueprint $t) => $t->dropColumn('ledger_seq'));
    }
};
