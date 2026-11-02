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
// 2. Check order ID
// --------------------------------------------------

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {

    die("Invalid order ID.");
}

$order_id = (int) $_GET['id'];


// --------------------------------------------------
// 3. Get order information
// --------------------------------------------------

$sql = "SELECT
            id,
            total_amount,
            status,
            created_at
        FROM orders
        WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// 4. Check if order exists
// --------------------------------------------------

if (!$order) {

    die("Order not found.");
}


// --------------------------------------------------
// 5. Get order items
// --------------------------------------------------

$sql = "SELECT
            order_items.quantity,
            order_items.price,
            products.name,
            products.image
        FROM order_items
        INNER JOIN products
        ON order_items.product_id = products.id
        WHERE order_items.order_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


require_once "includes/header.php";

?>

<h2>Order Details</h2>


<h3>
    Order #<?php echo $order['id']; ?>
</h3>

<p>
    Status:
    <?php echo htmlspecialchars($order['status']); ?>
</p>

<p>
    Date:
    <?php echo $order['created_at']; ?>
</p>


<h3>Products</h3>


<?php while ($item = mysqli_fetch_assoc($result)) { ?>

    <?php
        $subtotal = $item['price'] * $item['quantity'];
    ?>

    <div class="order-item">

        <img
            src="uploads/products/<?php echo $item['image']; ?>"
            alt="<?php echo htmlspecialchars($item['name']); ?>"
            width="120"
        >

        <h4>
            <?php echo htmlspecialchars($item['name']); ?>
        </h4>

        <p>
            Price:
            ₹<?php echo number_format($item['price'], 2); ?>
        </p>

        <p>
            Quantity:
            <?php echo $item['quantity']; ?>
        </p>

        <p>
            Subtotal:
            ₹<?php echo number_format($subtotal, 2); ?>
        </p>

    </div>

    <hr>

<?php } ?>


<h2>
    Total:
    ₹<?php echo number_format($order['total_amount'], 2); ?>
</h2>


<a href="orders.php">
    ← Back to My Orders
</a>


<?php

mysqli_stmt_close($stmt);

require_once "includes/footer.php";

?>