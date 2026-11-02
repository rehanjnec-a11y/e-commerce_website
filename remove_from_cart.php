<?php

session_start();

require_once "config/database.php";


// Check login

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// Check cart ID

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {

    die("Invalid cart item.");
}

$cart_id = (int) $_GET['id'];

$user_id = $_SESSION['user_id'];


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