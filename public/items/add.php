<?php
// Start transaction
try {
    // Database connection code here

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process the form submission
        $action = $_POST['action'];
        // Implement logic for adding or cloning items here
        
        // Validate and insert into database, using transactions
        
        // Commit transaction
    }
} catch (Exception $e) {
    // Rollback transaction on error
    // Handle the exception
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Items</title>
</head>
<body>
    <h1>Add or Clone Items</h1>
    <form method="POST" action="">
        <label for="item_name">Item Name:</label>
        <input type="text" name="item_name" required>
        
        <label for="brand">Brand:</label>
        <select name="brand" required>
            <!-- Options will be populated from the database -->
        </select>
        
        <div id="custom-fields">
            <label for="custom_field">Custom Field:</label>
            <input type="text" name="custom_fields[]" placeholder="Custom Field">
            <button type="button" onclick="addCustomField()">Add More</button>
        </div>
        
        <input type="submit" name="action" value="Add Item">
        <input type="submit" name="action" value="Clone Item">
    </form>

    <script>
        function addCustomField() {
            const div = document.createElement('div');
            div.innerHTML = `<label for="custom_field">Custom Field:</label>\n                            <input type="text" name="custom_fields[]" placeholder="Custom Field">`;
            document.getElementById('custom-fields').appendChild(div);
        }
    </script>
</body>
</html>
