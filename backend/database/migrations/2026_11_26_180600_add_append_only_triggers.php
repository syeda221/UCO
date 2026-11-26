<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private array $appendOnlyTables = [
        'collection_events',
        'collection_photos',
        'audit_logs',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            foreach ($this->appendOnlyTables as $table) {
                try {
                    DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_update");
                    DB::unprepared("
                        CREATE TRIGGER trg_{$table}_no_update
                        BEFORE UPDATE ON `{$table}`
                        FOR EACH ROW
                        BEGIN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Append-only violation: updates are prohibited on {$table}.';
                        END
                    ");

                    DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_delete");
                    DB::unprepared("
                        CREATE TRIGGER trg_{$table}_no_delete
                        BEFORE DELETE ON `{$table}`
                        FOR EACH ROW
                        BEGIN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Append-only violation: deletes are prohibited on {$table}.';
                        END
                    ");
                } catch (\Throwable $e) {
                    // Shared cPanel hosts sometimes restrict TRIGGER privilege; Eloquent guards remain active.
                    Log::warning("Skipped MySQL trigger creation on {$table}: " . $e->getMessage());
                }
            }
        } elseif ($driver === 'sqlite') {
            foreach ($this->appendOnlyTables as $table) {
                DB::unprepared("
                    CREATE TRIGGER IF NOT EXISTS trg_{$table}_no_update
                    BEFORE UPDATE ON \"{$table}\"
                    BEGIN
                        SELECT RAISE(ABORT, 'Append-only violation: updates are prohibited.');
                    END;
                ");
                DB::unprepared("
                    CREATE TRIGGER IF NOT EXISTS trg_{$table}_no_delete
                    BEFORE DELETE ON \"{$table}\"
                    BEGIN
                        SELECT RAISE(ABORT, 'Append-only violation: deletes are prohibited.');
                    END;
                ");
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            foreach ($this->appendOnlyTables as $table) {
                try {
                    DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_update");
                    DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_delete");
                } catch (\Throwable) {
                    // Ignore on teardown.
                }
            }
        }
    }
};
