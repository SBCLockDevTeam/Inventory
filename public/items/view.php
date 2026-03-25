<?php
session_start();
require '../../lib/database.php';
require '../../lib/logger.php';

db = new Database();
$logger = new Logger($db);

$itemCode = $_GET['code'] ?? null;

if (!$itemCode) {
    die('Item not found');
}

// Get item
db->query("SELECT i.*, b.name as brand_name FROM items i LEFT JOIN brands b ON i.brand_id = b.id WHERE i.public_code = :code");
db->bind(':code', $itemCode);
$item = $db->queryOne();

if (!$item) {
    die('Item not found');
}

// Get fields
db->query("SELECT * FROM item_fields WHERE item_public_code = :code ORDER BY sort_order");
db->bind(':code', $itemCode);
$fields = $db->queryAll();

// Get field values
db->query("SELECT * FROM item_field_values WHERE item_public_code = :code");
db->bind(':code', $itemCode);
$fieldValues = $db->queryAll();
$fieldValuesMap = [];
foreach ($fieldValues as $val) {
    $fieldValuesMap[$val['field_id']] = $val;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['name']) ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($item['name']) ?></h1>
        <a href="../index.php?page=items">Back to Items</a>
    </header>

    <main>
        <section>
            <h2>Item Information</h2>
            <p><strong>Code:</strong> <?= htmlspecialchars($item['public_code']) ?></p>
            <p><strong>Brand:</strong> <?= htmlspecialchars($item['brand_name']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($item['description'] ?? 'N/A') ?></p>
            <p><strong>Container:</strong> <?= $item['is_container'] ? 'Yes' : 'No' ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($item['location_item_id']) ?></p>
        </section>

        <section>
            <h2>Custom Fields</h2>
            <?php if ($fields): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?= htmlspecialchars($field['label']) ?></td>
                                <td><?= htmlspecialchars($field['field_type']) ?></td>
                                <td>
                                    <?php 
                                    $value = $fieldValuesMap[$field['id']] ?? null;
                                    if ($value) {
                                        echo htmlspecialchars($value['value_text'] ?? $value['value_number'] ?? $value['value_date'] ?? ($value['value_bool'] ? 'Yes' : 'No'));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No custom fields defined.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2>Actions</h2>
            <a href="edit.php?code=<?= $item['public_code'] ?>">Edit</a>
            <a href="add.php?clone=<?= $item['public_code'] ?>">Clone</a>
        </section>
    </main>
</body>
</html>
