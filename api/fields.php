<?php

// RESTful API endpoints for custom fields

header('Content-Type: application/json');

// Database connection (replace with your connection parameters)
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get custom fields
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM custom_fields";
    $result = $conn->query($sql);
    $fields = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $fields[] = $row;
        }
    }
    echo json_encode($fields);
}

// Create custom field
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'];
    $type = $data['type'];

    $sql = "INSERT INTO custom_fields (name, type) VALUES ('$name', '$type')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => 'Custom field created successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $sql . ' - ' . $conn->error]);
    }
}

// Update custom field
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];
    $type = $data['type'];

    $sql = "UPDATE custom_fields SET name='$name', type='$type' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => 'Custom field updated successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $sql . ' - ' . $conn->error]);
    }
}

// Delete custom field
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $id = $data['id'];

    $sql = "DELETE FROM custom_fields WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => 'Custom field deleted successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $sql . ' - ' . $conn->error]);
    }
}

$conn->close();
?>