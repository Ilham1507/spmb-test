<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production was initially bootstrapped from an older database. Keep every
     * column used by the participant's Save & Continue flow available without
     * touching or deleting existing registrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('alamat_pendaftar')) {
            Schema::table('alamat_pendaftar', function (Blueprint $table): void {
                if (! Schema::hasColumn('alamat_pendaftar', 'distance_to_school')) {
                    $table->decimal('distance_to_school', 6, 2)->nullable();
                }
                if (! Schema::hasColumn('alamat_pendaftar', 'distance_range')) {
                    $table->string('distance_range', 50)->nullable();
                }
                if (! Schema::hasColumn('alamat_pendaftar', 'distance_point')) {
                    $table->unsignedSmallInteger('distance_point')->nullable();
                }
            });
        }

        if (Schema::hasTable('sekolah_asal')) {
            Schema::table('sekolah_asal', function (Blueprint $table): void {
                if (! Schema::hasColumn('sekolah_asal', 'referensi_sekolah_id')) {
                    $table->unsignedBigInteger('referensi_sekolah_id')->nullable();
                }
                if (! Schema::hasColumn('sekolah_asal', 'school_address')) {
                    $table->text('school_address')->nullable();
                }
                foreach (['bentuk_pendidikan' => 30, 'status_sekolah' => 30, 'desa_kelurahan' => 255, 'kecamatan' => 255, 'kabupaten_kota' => 255, 'provinsi' => 255] as $column => $length) {
                    if (! Schema::hasColumn('sekolah_asal', $column)) {
                        $table->string($column, $length)->nullable();
                    }
                }
            });
        }

        if (Schema::hasTable('kontak_pendaftar')) {
            Schema::table('kontak_pendaftar', function (Blueprint $table): void {
                if (! Schema::hasColumn('kontak_pendaftar', 'email')) {
                    $table->string('email', 150)->nullable();
                }
                if (! Schema::hasColumn('kontak_pendaftar', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }
                if (! Schema::hasColumn('kontak_pendaftar', 'email_verification_token')) {
                    $table->string('email_verification_token', 80)->nullable();
                }
                if (! Schema::hasColumn('kontak_pendaftar', 'email_verification_expires_at')) {
                    $table->timestamp('email_verification_expires_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('jadwal_spmb') && ! Schema::hasColumn('jadwal_spmb', 'available_for_student_selection')) {
            Schema::table('jadwal_spmb', fn (Blueprint $table) => $table->boolean('available_for_student_selection')->default(false));
        }

        if (Schema::hasTable('pendaftar')) {
            Schema::table('pendaftar', function (Blueprint $table): void {
                if (! Schema::hasColumn('pendaftar', 'verification_notes')) {
                    $table->text('verification_notes')->nullable();
                }
                if (! Schema::hasColumn('pendaftar', 'correction_status')) {
                    $table->string('correction_status')->nullable();
                }
                if (! Schema::hasColumn('pendaftar', 'correction_submitted_at')) {
                    $table->timestamp('correction_submitted_at')->nullable();
                }
                if (! Schema::hasColumn('pendaftar', 'preferred_test_schedule_id')) {
                    $table->unsignedBigInteger('preferred_test_schedule_id')->nullable();
                }
                if (! Schema::hasColumn('pendaftar', 'preferred_test_selected_at')) {
                    $table->timestamp('preferred_test_selected_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // This migration repairs production schema compatibility. Do not drop
        // columns automatically because they may already contain form data.
    }
};
