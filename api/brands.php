<?php
header('Content-Type: application/json');
$request_method = $_SERVER['REQUEST_METHOD'];

switch ($request_method) {
    case 'GET':
        // Code to handle retrieving brands
        echo json_encode(["message" => "Retrieve brands"]);
        break;

    case 'POST':
        // Code to handle creating a new brand
        echo json_encode(["message" => "Create brand"]);
        break;

    case 'PUT':
        // Code to handle updating a brand
        echo json_encode(["message" => "Update brand"]);
        break;

    case 'DELETE':
        // Code to handle deleting a brand
        echo json_encode(["message" => "Delete brand"]);
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
} 
?>