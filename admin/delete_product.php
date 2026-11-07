
<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid request. Please go back and try again.");
}

// Validate product ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Invalid product ID.");
}

// Check whether product exists
$stmt = mysqli_prepare(
    $conn,
    "SELECT image FROM products WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    die("Product not found.");
}

// Protect products that are part of existing orders
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM order_items WHERE product_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$order_result = mysqli_stmt_get_result($stmt);
$has_orders = mysqli_num_rows($order_result) > 0;

mysqli_stmt_close($stmt);

if ($has_orders) {
    die("This product belongs to an existing order and cannot be deleted. <a href='products.php'>Go back</a>");
}

// Delete product
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM products WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    // Delete image after successful database deletion
    if (!empty($product['image'])) {

        $image_path = "../uploads/products/" .
                      basename($product['image']);

        if (is_file($image_path)) {
            unlink($image_path);
        }
    }

    header("Location: products.php?deleted=1");
    exit;

} else {

    mysqli_stmt_close($stmt);
    die("Could not delete product. <a href='products.php'>Go back</a>");
}
?>
