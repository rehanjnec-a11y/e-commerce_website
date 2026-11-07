
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

// Only allow POST requests
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

// Validate category ID
if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
    die("Invalid category ID.");
}

$category_id = (int) $_POST['id'];

// Check whether category exists
$sql = "SELECT id FROM categories WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$category) {
    die("Category not found.");
}

// Check whether products are assigned to this category
$check_sql = "SELECT COUNT(*) AS total
              FROM products
              WHERE category_id = ?";

$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $category_id);
mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);
$row = mysqli_fetch_assoc($check_result);

mysqli_stmt_close($check_stmt);

if ((int)$row['total'] > 0) {
    die("Cannot delete this category. Please move or delete its products first.");
}

// Delete category
$delete_sql = "DELETE FROM categories WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);

mysqli_stmt_bind_param($delete_stmt, "i", $category_id);

if (mysqli_stmt_execute($delete_stmt)) {
    header("Location: categories.php");
    exit;
} else {
    die("Failed to delete category.");
}

mysqli_stmt_close($delete_stmt);

?>
