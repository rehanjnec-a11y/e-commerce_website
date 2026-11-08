
<?php

session_start();

require_once "config/database.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid CSRF token.");
}

// Validate cart ID
if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
    die("Invalid cart item.");
}

$cart_id = (int) $_POST['id'];

// Delete only this user's cart item
$sql = "DELETE FROM cart
        WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $cart_id,
    $user_id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

// Return to cart
header("Location: cart.php");
exit;

?>
