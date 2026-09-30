<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasTable('pengguna')) {
            return;
        }

        // The authenticated application model is App\Models\User, whose
        // table is `pengguna`. The old Laravel starter-table foreign key
        // incorrectly pointed audit_logs.actor_id at `users`.
        DB::table('audit_logs')
            ->whereNotNull('actor_id')
            ->whereNotIn('actor_id', DB::table('pengguna')->select('id'))
            ->update(['actor_id' => null]);

        $database = DB::getDatabaseName();
        $hasOldForeignKey = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('CONSTRAINT_NAME', 'audit_logs_actor_id_foreign')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($hasOldForeignKey) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign('audit_logs_actor_id_foreign');
            });
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('actor_id', 'audit_logs_actor_id_foreign')
                ->references('id')
                ->on('pengguna')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Keep the corrected relationship in place for live audit history.
    }
};
