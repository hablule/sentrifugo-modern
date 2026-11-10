<?php
/**
 * Developer Tool: Exports current local database schema as the canonical blueprint.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/db_constants.php';

use Doctrine\DBAL\DriverManager;

echo "====================================================\n";
echo "   Sentrifugo Modern - Canonical Schema Exporter    \n";
echo "====================================================\n\n";

try {
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

    echo "Inspecting local database tables... ";
    $schema = $sm->introspectSchema();
    echo "DONE! (" . count($schema->getTables()) . " tables found)\n";

    $outputFile = __DIR__ . '/../data/canonical_schema.dat';
    if (!is_dir(dirname($outputFile))) {
        mkdir(dirname($outputFile), 0755, true);
    }

    file_put_contents($outputFile, gzcompress(serialize($schema), 9));
    echo "Canonical schema saved to: data/canonical_schema.dat\n";
    echo "File size: " . round(filesize($outputFile) / 1024, 2) . " KB\n";
    echo "Ready to commit to Git! ✅\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
