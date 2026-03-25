<?php
// Public search functionality for items

function searchItems($keyword) {
    // Connect to the database
    $conn = new mysqli('localhost', 'username', 'password', 'database');

    // Check connection
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Prepare and bind
    $stmt = $conn->prepare('SELECT * FROM items WHERE name LIKE ?');
    $searchTerm = '%' . $keyword . '%';
    $stmt->bind_param('s', $searchTerm);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch data
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    // Close connection
    $stmt->close();
    $conn->close();

    return $items;
}

?>