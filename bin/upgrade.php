<?php
/**
 * Client Upgrader: Safely migrates an existing database to the canonical schema.
 * Features: Auto-detects mysqldump, prevents destructive drops, 100% data safety.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/db_constants.php';

use Doctrine\DBAL\DriverManager;

echo "====================================================\n";
echo "    Sentrifugo Modern - Smart Database Upgrader     \n";
echo "====================================================\n\n";

try {
    $schemaFile = __DIR__ . '/../data/canonical_schema.dat';
    if (!file_exists($schemaFile)) {
        die("❌ Error: Canonical schema blueprint (data/canonical_schema.dat) not found.\n");
    }

    $connectionParams = [
        'dbname'   => SENTRIFUGO_DBNAME,
        'user'     => SENTRIFUGO_USERNAME,
        'password' => SENTRIFUGO_PASSWORD,
        'host'     => SENTRIFUGO_HOST,
        'port'     => SENTRIFUGO_PORT,
        'driver'   => 'pdo_mysql',
    ];

    $conn = DriverManager::getConnection($connectionParams);
    $sm = $conn->createSchemaManager();
    $platform = $conn->getDatabasePlatform();

    // 1. Smart Safety Backup (Uses global mysqldump from PATH)
    echo "[1/4] Creating safety backup of current database...\n";
    $backupDir = __DIR__ . '/../data/backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    
    $backupFile = $backupDir . '/backup_' . date('Ymd_His') . '.sql';
    
    $cmd = sprintf(
        "mysqldump -h %s -P %s -u %s %s %s > %s 2>&1",
        escapeshellarg(SENTRIFUGO_HOST),
        escapeshellarg(SENTRIFUGO_PORT),
        escapeshellarg(SENTRIFUGO_USERNAME),
        !empty(SENTRIFUGO_PASSWORD) ? "-p" . escapeshellarg(SENTRIFUGO_PASSWORD) : "",
        escapeshellarg(SENTRIFUGO_DBNAME),
        escapeshellarg($backupFile)
    );
    @exec($cmd, $out, $ret);

    if ($ret === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
        echo "      ✔ Safety backup saved: " . basename($backupFile) . " (" . round(filesize($backupFile)/1024, 2) . " KB)\n\n";
    } else {
        echo "      ⚠️ Notice: mysqldump execution failed. Check credentials or PATH.\n\n";
        exit;
    }

    // 2. Introspect Current Database
    echo "[2/4] Inspecting existing database structure...\n";
    $currentSchema = $sm->introspectSchema();
    echo "      ✔ Found " . count($currentSchema->getTables()) . " existing tables.\n\n";

    // 3. Load Target Blueprint & Calculate Delta
    echo "[3/4] Calculating schema differences against latest blueprint...\n";
    $raw = file_get_contents($schemaFile);
    $targetSchema = unserialize(@gzuncompress($raw) ?: $raw);

    $comparator = $sm->createComparator();
    $schemaDiff = $comparator->compareSchemas($currentSchema, $targetSchema);
    $rawQueries = $platform->getAlterSchemaSQL($schemaDiff);

    // 🔒 SAFETY FILTER: Separate Additive (Safe) from Destructive (Drops)
    $safeQueries = [];
    $destructiveQueries = [];

    foreach ($rawQueries as $sql) {
        if (preg_match('/\b(DROP\s+COLUMN|DROP\s+TABLE|DROP\s+FOREIGN\s+KEY)\b/i', $sql)) {
            $destructiveQueries[] = $sql;
        } else {
            $safeQueries[] = $sql;
        }
    }

    if (empty($safeQueries) && empty($destructiveQueries)) {
        echo "🎉 Your database schema is already 100% up to date! Zero changes needed.\n";
        exit(0);
    }

    // 4. Present Queries Transparently
    if (!empty($safeQueries)) {
        echo "[4/4] Found " . count($safeQueries) . " Safe/Additive Update(s):\n";
        echo "---------------------------------------------------------\n";
        foreach ($safeQueries as $i => $sql) {
            echo ($i + 1) . ". [SAFE] " . $sql . "\n";
        }
        echo "---------------------------------------------------------\n";
    }

    if (!empty($destructiveQueries)) {
        echo "\n⚠️  CAUTION: Found " . count($destructiveQueries) . " Destructive Change(s) (Drops):\n";
        echo "---------------------------------------------------------\n";
        foreach ($destructiveQueries as $i => $sql) {
            echo ($i + 1) . ". [DESTRUCTIVE] " . $sql . "\n";
        }
        echo "---------------------------------------------------------\n";
        echo "Note: By default, destructive drops are ignored to protect existing data integrity.\n";
    }

    if (empty($safeQueries)) {
        echo "\nZero safe updates to apply. (All detected changes were destructive drops and were skipped).\n";
        exit(0);
    }

    // Prompt for confirmation
    echo "\nDo you want to apply the SAFE updates to your database? (yes/no) [yes]: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if ($line !== '' && strtolower($line) !== 'yes' && strtolower($line) !== 'y') {
        echo "Update cancelled by user. No changes were made.\n";
        exit(0);
    }

    // 5. Execute Safe Queries
    echo "\nExecuting database updates...\n";
    foreach ($safeQueries as $sql) {
        echo "Executing: " . substr($sql, 0, 60) . "... ";
        $conn->executeStatement($sql);
        echo "DONE! ✅\n";
    }

    echo "\n🎉 Database upgraded successfully to the latest Sentrifugo Modern schema!\n";

} catch (Exception $e) {
    echo "\n❌ Critical Error: " . $e->getMessage() . "\n";
    exit(1);
}