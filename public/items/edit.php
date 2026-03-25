<?php
// Start the session
session_start();

// Include database connection file
echo '<pre>' . print_r($_SESSION, true) . '</pre>';

// Check if the user is logged in
if(!isset($_SESSION['user_id'])) {
    echo 'Unauthorized access!';
    exit;
}

// Load item details if editing
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item_details = null;
if($item_id > 0) {
    // Fetch item details from the database (prevent circular reference)
    // Example: $item_details = fetchItemFromDB($item_id);
}

// Handling form submission for editing the item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $custom_fields = $_POST['custom_fields'] ?? [];
    $updated_at = date('Y-m-d H:i:s');

    // Validate the input
to_validate = compact('name', 'description', 'custom_fields');
    foreach ($to_validate as $field) {
        if (empty($field)) {
            echo 'All fields are required!';
            exit;
        }
    }

    // Update the item in the database (replace circular reference prevention logic)
    // Example: updateItemInDB($item_id, $name, $description, $custom_fields, $updated_at);

    // Log activity
    // Example: logActivity($_SESSION['user_id'], 'Edited item ID ' . $item_id);
    echo 'Item updated successfully!';
}

// HTML form for editing the item
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item</title>
</head>
<body>
    <h1>Edit Item</h1>
    <form method="POST" action="">
        <label for="name">Item Name:</label>
        <input name="name" type="text" value="<?php echo htmlspecialchars($item_details['name'] ?? ''); ?>" required />
        <br />

        <label for="description">Description:</label>
        <textarea name="description" required><?php echo htmlspecialchars($item_details['description'] ?? ''); ?></textarea>
        <br />

        <h2>Custom Fields</h2>
        <div id="customFields">
            <?php foreach ($custom_fields as $field): ?>
                <input name="custom_fields[]" type="text" value="<?php echo htmlspecialchars($field); ?>" placeholder="Custom Field" />
                <br />
            <?php endforeach; ?>
        </div>

        <button type="submit">Update Item</button>
    </form>
</body>
</html>