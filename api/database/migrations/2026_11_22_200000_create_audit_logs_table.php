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
            $table->id();
            // No foreign key: the log outlives the user it names, so the label is kept too.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_label')->nullable();
            $table->string('event', 60)->index();
            $table->string('auditable_type')->nullable();
            $table->string('auditable_id')->nullable();
            $table->string('auditable_label')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['auditable_type', 'auditable_id']);
        });

        // Append-only, enforced by the database: no row can be changed or removed.
        DB::unprepared(<<<'SQL'
            create or replace function audit_logs_append_only() returns trigger language plpgsql as $$
            begin
                raise exception 'audit_logs is append-only';
            end;
            $$;
        SQL);
        DB::unprepared('create trigger audit_logs_no_change before update or delete on audit_logs for each row execute function audit_logs_append_only()');
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        DB::unprepared('drop function if exists audit_logs_append_only()');
    }
};
