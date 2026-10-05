#!/bin/sh
set -e

# Railway injects APP_URL when the container starts. Clear any configuration
# cache created by an earlier deployment so asset URLs and form actions keep
# the current HTTPS domain instead of a stale HTTP address.
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link

# Railway MySQL uses caching_sha2_password. Bootstrap through PDO instead of
# Alpine's MariaDB client, which cannot load that authentication plugin.
php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$hasPaymentTable = $pdo->query("SHOW TABLES LIKE \"transaksi_pembayaran\"")->fetchColumn();
if (! $hasPaymentTable) {
    // A prior startup may have left harmless base tables behind.  Make the
    // schema bootstrap resumable, then create every still-missing table.
    $schema = file_get_contents("database/schema/mysql-schema.sql");
    $schema = str_replace("CREATE TABLE `", "CREATE TABLE IF NOT EXISTS `", $schema);
    $pdo->exec($schema);
}
'

php artisan migrate --force --path=database/migrations/2026_09_28_090000_add_payment_handover_fields.php
php artisan migrate --force --path=database/migrations/2026_09_28_120000_purge_participant_test_data.php
php artisan migrate --force --path=database/migrations/2026_09_28_143000_add_parent_contact_to_kunjungan_pendaftar.php
php artisan migrate --force --path=database/migrations/2026_09_28_144000_remove_ilham_test_data.php
php artisan migrate --force --path=database/migrations/2026_09_28_161000_reset_ilham_registration_keep_one_visit.php
php artisan migrate --force --path=database/migrations/2026_09_28_180000_resend_ilham_official_form_invoice.php
php artisan migrate --force --path=database/migrations/2026_09_28_181000_retry_ilham_official_form_invoice.php
php artisan migrate --force --path=database/migrations/2026_09_28_182000_deliver_ilham_official_invoice_document.php
php artisan migrate --force --path=database/migrations/2026_09_29_090000_create_minat_promosi_table.php
php artisan migrate --force --path=database/migrations/2026_09_29_100000_remove_unused_promotion_interest_fields.php
php artisan migrate --force --path=database/migrations/2026_09_29_110000_remove_promotion_note_field.php
php artisan migrate --force --path=database/migrations/2026_09_29_120000_add_social_media_to_minat_promosi_table.php
php artisan migrate --force --path=database/migrations/2026_09_29_120000_reset_demo_participant_data.php
php artisan migrate --force --path=database/migrations/2026_09_30_120000_delete_confirmed_ilham_participant_data.php
php artisan migrate --force --path=database/migrations/2026_09_30_155000_delete_ilham_payment_data.php
php artisan migrate --force --path=database/migrations/2026_09_30_180000_delete_ilham_payment_data_retry.php
# This one-time reset is deliberately limited to payment rows and runs with
# normal foreign-key order, so it cannot disturb accounts or applicant data.
php artisan migrate --force --path=database/migrations/2026_09_30_190000_reset_all_payment_data_safely.php
php artisan migrate --force --path=database/migrations/2026_09_30_200000_ensure_manual_payment_columns.php
php artisan migrate --force --path=database/migrations/2026_09_30_210000_delete_selected_participant_data.php
php artisan migrate --force --path=database/migrations/2026_09_30_220000_ensure_participant_form_schema.php
php artisan migrate --force --path=database/migrations/2026_10_01_090000_ensure_major_fee_breakdown_schema.php
php artisan migrate --force --path=database/migrations/2026_10_01_100000_fix_audit_logs_actor_foreign_key.php
php artisan migrate --force --path=database/migrations/2026_10_01_110000_reset_ilham_re_registration_payment.php
php artisan migrate --force --path=database/migrations/2026_10_01_150000_create_invoice_email_deliveries_table.php
php artisan migrate --force --path=database/migrations/2026_10_04_120000_create_whatsapp_messages_table.php
php artisan migrate --force --path=database/migrations/2026_10_04_150000_add_remember_token_to_pengguna.php
# The school finder depends on this SMP/MTs reference data. It is idempotent
# (matched by NPSN), so it can safely run again whenever Railway restarts.
php artisan db:seed --force --class=Database\\Seeders\\CileungsiSmpSeeder
# CLI startup runs as root; FPM workers need write access to runtime/upload files.
chown -R www-data:www-data storage bootstrap/cache
task_port="${PORT:-10000}"
case "$task_port" in ''|*[!0-9]*) echo "Invalid HTTP port" >&2; exit 1;; esac
sed "s/__PORT__/$task_port/g" docker/nginx.conf.template > /etc/nginx/nginx.conf
nginx -t
php-fpm -t
exec /usr/bin/supervisord -c /etc/supervisord.conf
