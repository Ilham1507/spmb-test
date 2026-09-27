#!/bin/sh
set -e

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
exec php -S "0.0.0.0:${PORT:-10000}" -t public
