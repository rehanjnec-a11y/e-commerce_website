<?php

session_start();

require_once "config/database.php";


// 1. Check if user is logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// 2. Check product ID

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {

    die("Invalid product ID.");
}

$product_id = (int) $_GET['id'];

$user_id = $_SESSION['user_id'];


// 3. Get product information

$sql = "SELECT id, stock FROM products WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $product_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// 4. Check product exists

if (!$product) {

    die("Product not found.");
}


// 5. Check stock

if ($product['stock'] <= 0) {

    die("Product is out of stock.");
}


// 6. Check if product is already in cart

$sql = "SELECT id, quantity
        FROM cart
        WHERE user_id = ? AND product_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $user_id,
    $product_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cart_item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// 7. Add or increase quantity

if ($cart_item) {

    $new_quantity = $cart_item['quantity'] + 1;

    if ($new_quantity > $product['stock']) {

        die("You cannot add more than available stock.");
    }

    $sql = "UPDATE cart
            SET quantity = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $new_quantity,
        $cart_item['id']
    );

} else {

    $sql = "INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, 1)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $user_id,
        $product_id
    );
}


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: index.php");
    exit;

} else {

    mysqli_stmt_close($stmt);

    die("Failed to add product to cart.");
}

?>