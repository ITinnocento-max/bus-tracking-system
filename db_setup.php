<?php
// Run once after deploying to Render to initialize the database
// Access: https://your-app.onrender.com/db_setup.php
// DELETE this file after successful setup!

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$password = $_GET['key'] ?? '';
if ($password !== 'setup2026') {
    echo "<h2>Unauthorized</h2><p>Add ?key=setup2026 to the URL</p>";
    exit;
}

try {
    $db = getDb();

    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');

    $statements = explode(';', $schema);
    $success = 0;
    $errors = [];

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        // Skip CREATE DATABASE and USE statements (Aiven has DB pre-created)
        if (preg_match('/^(CREATE DATABASE|USE)/i', $stmt)) continue;
        try {
            $db->exec($stmt);
            $success++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (stripos($e->getMessage(), 'already exists') === false) {
                $errors[] = htmlspecialchars($stmt) . '<br>→ ' . htmlspecialchars($e->getMessage());
            } else {
                $success++;
            }
        }
    }

    echo "<h2>Database Setup Complete</h2>";
    echo "<p>Statements executed: $success</p>";

    if (count($errors) > 0) {
        echo "<h3>Errors:</h3><pre>";
        foreach ($errors as $e) echo "$e\n\n";
        echo "</pre>";
    } else {
        echo "<p style='color:green;font-weight:bold;'>All tables created successfully!</p>";
    }

    // Show tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables:</h3><ul>";
    foreach ($tables as $t) echo "<li>$t</li>";
    echo "</ul>";

    echo "<p style='color:red;font-weight:bold;'>DELETE this file after use!</p>";

} catch (Exception $e) {
    echo "<h2>Connection Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
