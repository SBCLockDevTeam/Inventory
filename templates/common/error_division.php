<?php
// Error Division
$errors = get_errors(true);
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo '<div class="error ' . htmlspecialchars($error['severity']) . '">';
        echo htmlspecialchars($error['message']);
        echo '</div>';
    }
}
?>