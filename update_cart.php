<?php

session_start();

require_once "config/database.php";


// Check login

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


$user_id = $_SESSION['user_id'];


// Check required values

if (
    !isset($_GET['id']) ||
    !isset($_GET['action']) ||
    !ctype_digit($_GET['id'])
) {

    die("Invalid request.");
}


$cart_id = (int) $_GET['id'];
$action = $_GET['action'];


// Get current cart item

$sql = "SELECT cart.quantity, products.stock
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        WHERE cart.id = ? AND cart.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $cart_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$item) {

    die("Cart item not found.");
}


$quantity = $item['quantity'];
$stock = $item['stock'];


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


// Invalid action

else {

    die("Invalid action.");
}


// If quantity becomes 0, remove item

if ($quantity <= 0) {

    $sql = "DELETE FROM cart
            WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $cart_id,
        $user_id
    );

}


// Otherwise update quantity

else {

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


// Go back to cart

header("Location: cart.php");
exit;

?>