#!/bin/sh
set -e

# Railway injects APP_URL when the container starts. Clear any configuration
# cache created by an earlier deployment so asset URLs and form actions keep
# the current HTTPS domain instead of a stale HTTP address.
php artisan config:clear
php artisan route:clear

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
# The school finder depends on this SMP/MTs reference data. It is idempotent
# (matched by NPSN), so it can safely run again whenever Railway restarts.
php artisan db:seed --force --class=Database\\Seeders\\CileungsiSmpSeeder
exec php -S "0.0.0.0:${PORT:-10000}" -t public
