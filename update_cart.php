
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

// Validate required values
if (
    !isset($_POST['id']) ||
    !isset($_POST['action']) ||
    !ctype_digit($_POST['id'])
) {
    die("Invalid request.");
}

$cart_id = (int) $_POST['id'];
$action = $_POST['action'];

// Validate action
if (!in_array($action, ['increase', 'decrease'], true)) {
    die("Invalid action.");
}

// Get current cart item
$sql = "SELECT cart.quantity, products.stock
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        WHERE cart.id = ? AND cart.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$item) {
    die("Cart item not found.");
}

$quantity = (int) $item['quantity'];
$stock = (int) $item['stock'];

// Increase quantity
if ($action === "increase") {

    if ($quantity < $stock) {
        $quantity++;
    } else {
        die("Maximum available stock reached.");
    }
}

// Decrease quantity
elseif ($action === "decrease") {

    $quantity--;
}

// Delete item if quantity becomes zero
if ($quantity <= 0) {

    $sql = "DELETE FROM cart
            WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);

} else {

    // Update quantity
    $sql = "UPDATE cart
            SET quantity = ?
            WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $quantity,
        $cart_id,
        $user_id
    );
}

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

// Return to cart
header("Location: cart.php");
exit;

?>
