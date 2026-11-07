
<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Accept POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid security token.");
}

// Validate order ID
if (!isset($_POST['order_id']) || !ctype_digit($_POST['order_id'])) {
    die("Invalid Order ID.");
}

$order_id = (int) $_POST['order_id'];

// Allowed statuses
$allowed_statuses = [
    'pending',
    'confirmed',
    'shipped',
    'delivered',
    'cancelled'
];

$status = $_POST['status'] ?? '';

if (!in_array($status, $allowed_statuses, true)) {
    die("Invalid order status.");
}

// Update order status
$sql = "UPDATE orders SET status = ? WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $status, $order_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Failed to update order status.");
}

// Redirect back to order details
header("Location: order_details.php?id=" . $order_id . "&updated=1");
exit;

?>
