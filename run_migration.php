<?php
require_once __DIR__ . '/subsystem/config/database.php';

echo "Connecting to Supabase...\n";
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die("Failed to connect to database\n");
}

$sql = file_get_contents(__DIR__ . '/supabase_migration.sql');
echo "Executing migration...\n";

try {
    $conn->exec($sql);
    echo "Migration applied successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
