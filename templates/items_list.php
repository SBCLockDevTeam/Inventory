<?php

// Display items list

// Sample items data
$items = [
    ['name' => 'Item 1', 'price' => 10.00, 'quantity' => 100],
    ['name' => 'Item 2', 'price' => 15.50, 'quantity' => 200],
    ['name' => 'Item 3', 'price' => 7.25, 'quantity' => 150],
];

// Function to display the items list
function displayItems($items) {
    echo "<h1>Items List</h1>";
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Price</th><th>Quantity</th></tr>";

    foreach ($items as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>" . htmlspecialchars(number_format($item['price'], 2)) . "</td>";
        echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

displayItems($items);