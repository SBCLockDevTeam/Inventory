<?php
require '../lib/database.php';
require '../lib/logger.php';

$db = new Database();
$logger = new Logger('../logs/activity.log');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'init_db') {
        try {
            $schema = file_get_contents('../db/schema.sql');
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->query($statement);
                    $db->execute();
                }
            }
            
            $message = 'Database initialized successfully!';
            $logger->log('Database initialized', 'INFO', []);
        } catch (Exception $e) {
            $error = 'Error initializing database: ' . $e->getMessage();
            $logger->log('Database initialization failed', 'ERROR', ['error' => $e->getMessage()]);
        }
    } elseif ($action === 'seed_db') {
        try {
            $seed = file_get_contents('../db/seed.sql');
            $statements = array_filter(array_map('trim', explode(';', $seed)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->query($statement);
                    $db->execute();
                }
            }
            
            $message = 'Sample data loaded successfully!';
            $logger->log('Sample data loaded', 'INFO', []);
        } catch (Exception $e) {
            $error = 'Error loading sample data: ' . $e->getMessage();
            $logger->log('Sample data load failed', 'ERROR', ['error' => $e->getMessage()]);
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
            
            <?php if (!empty($message)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

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
