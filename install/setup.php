<?php
require __DIR__ . '/../config/secrets.php';
require __DIR__ . '/../lib/database.php';
require __DIR__ . '/../lib/logger.php';

$db = new Database();
$logger = new Logger('../logs/activity.log');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'init_db') {
        try {
            $schema = file_get_contents('../db/schema.sql');
            
            // Remove SQL comments
            $schema = preg_replace('/--.*$/m', '', $schema);
            $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);
            
            // Split statements properly
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->query($statement);
                    $db->execute();
                }
            }
            
            $logger->log('Database initialization', 'INFO', ['status' => 'success']);
            $message = '<div class="alert alert-success">Database initialized successfully!</div>';
        } catch (Exception $e) {
            $logger->log('Database initialization', 'ERROR', ['error' => $e->getMessage()]);
            $message = '<div class="alert alert-danger">Error initializing database: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } elseif ($action === 'seed_db') {
        try {
            $seedFile = '../db/seed.sql';
            if (file_exists($seedFile)) {
                $seed = file_get_contents($seedFile);
                
                // Remove SQL comments
                $seed = preg_replace('/--.*$/m', '', $seed);
                $seed = preg_replace('/\/\*.*?\*\//s', '', $seed);
                
                $statements = array_filter(array_map('trim', explode(';', $seed)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->query($statement);
                        $db->execute();
                    }
                }
                
                $logger->log('Database seeding', 'INFO', ['status' => 'success']);
                $message = '<div class="alert alert-success">Sample data loaded successfully!</div>';
            } else {
                $message = '<div class="alert alert-warning">No seed file found</div>';
            }
        } catch (Exception $e) {
            $logger->log('Database seeding', 'ERROR', ['error' => $e->getMessage()]);
            $message = '<div class="alert alert-danger">Error loading sample data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Setup</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <h1>Inventory Setup</h1>
    </header>

    <main>
        <section>
            <h2>Installation Steps</h2>
            
            <?php if (isset($message)) echo $message; ?>
            
            <form method="POST">
                <fieldset>
                    <legend>Database Setup</legend>
                    
                    <p>1. Initialize the database schema:</p>
                    <button type="submit" name="action" value="init_db" class="btn btn-primary">Initialize Database</button>
                    
                    <p style="margin-top: 20px;">2. Load sample data (optional):</p>
                    <button type="submit" name="action" value="seed_db" class="btn btn-secondary">Load Sample Data</button>
                </fieldset>
            </form>

            <div style="margin-top: 30px;">
                <h3>Next Steps</h3>
                <ol>
                    <li>Update config/secrets.php with your database credentials</li>
                    <li>Click "Initialize Database" to create tables</li>
                    <li>Optionally load sample data</li>
                    <li>Go to <a href="../public/index.php">Dashboard</a></li>
                </ol>
            </div>
        </section>
    </main>

    <script src="../js/main.js"></script>
</body>
</html>
