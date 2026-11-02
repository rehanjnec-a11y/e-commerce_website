<?php

session_start();

require_once "config/database.php";


// --------------------------------------------------
// 1. Check if user is logged in
// --------------------------------------------------

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// --------------------------------------------------
// 2. Start database transaction
// --------------------------------------------------

mysqli_begin_transaction($conn);


// --------------------------------------------------
// 3. Get user's cart items
// --------------------------------------------------

$sql = "SELECT
            cart.product_id,
            cart.quantity,
            products.price,
            products.stock
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        WHERE cart.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cart_items = [];

while ($item = mysqli_fetch_assoc($result)) {

    $cart_items[] = $item;
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 4. Check if cart is empty
// --------------------------------------------------

if (empty($cart_items)) {

    mysqli_rollback($conn);

    die("Your cart is empty.");
}


// --------------------------------------------------
// 5. Check stock and calculate total
// --------------------------------------------------

$total = 0;

foreach ($cart_items as $item) {

    // Check whether enough stock is available

    if ($item['quantity'] > $item['stock']) {

        mysqli_rollback($conn);

        die("Not enough stock available for one of the products.");
    }


    // Calculate product subtotal

    $subtotal = $item['price'] * $item['quantity'];


    // Add subtotal to total

    $total += $subtotal;
}


// --------------------------------------------------
// 6. Create the order
// --------------------------------------------------

$sql = "INSERT INTO orders (user_id, total_amount)
        VALUES (?, ?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "id",
    $user_id,
    $total
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    mysqli_rollback($conn);

    die("Failed to create order.");
}


// --------------------------------------------------
// 7. Get the newly created order ID
// --------------------------------------------------

$order_id = mysqli_insert_id($conn);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 8. Add products to order_items
// --------------------------------------------------

$sql = "INSERT INTO order_items
        (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);


foreach ($cart_items as $item) {

    mysqli_stmt_bind_param(
        $stmt,
        "iiid",
        $order_id,
        $item['product_id'],
        $item['quantity'],
        $item['price']
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        mysqli_rollback($conn);

        die("Failed to add products to order.");
    }
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 9. Reduce product stock
// --------------------------------------------------

$sql = "UPDATE products
        SET stock = stock - ?
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);


foreach ($cart_items as $item) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $item['quantity'],
        $item['product_id']
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        mysqli_rollback($conn);

        die("Failed to update product stock.");
    }
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 10. Clear the user's cart
// --------------------------------------------------

$sql = "DELETE FROM cart
        WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    mysqli_rollback($conn);

    die("Failed to clear cart.");
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 11. Commit the transaction
// --------------------------------------------------

mysqli_commit($conn);


// --------------------------------------------------
// 12. Show order confirmation
// --------------------------------------------------

require_once "includes/header.php";

?>

<h2>Order Placed Successfully!</h2>

<p>
    Thank you for your order.
</p>

<p>
    Your Order ID is:
    <strong>#<?php echo $order_id; ?></strong>
</p>

<p>
    Total Amount:
    <strong>₹<?php echo number_format($total, 2); ?></strong>
</p>

<p>
    Order Status:
    <strong>Pending</strong>
</p>

<a href="index.php">
    Continue Shopping
</a>

<?php

require_once "includes/footer.php";

?>