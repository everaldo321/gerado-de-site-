<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
    
    echo "<h2>Environment Variables:</h2>";
    echo "<p>DB_HOST: " . getenv('DB_HOST') . "</p>";
    echo "<p>DB_NAME: " . getenv('DB_NAME') . "</p>";
    echo "<p>DB_USER: " . getenv('DB_USER') . "</p>";
    echo "<p>MYSQL_URL (first 50 chars): " . substr(getenv('MYSQL_URL'), 0, 50) . "...</p>";
    
    // List existing tables
    $tables = $db->query("SHOW TABLES")->fetchAll();
    echo "<h2>Existing Tables:</h2>";
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠️ No tables found. Run <a href='install.php?key=instalar123'>install.php</a> to create tables.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $row) {
            echo "<li>" . reset($row) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
}
?>
