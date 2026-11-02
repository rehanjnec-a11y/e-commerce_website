<?php

session_start();

require_once "config/database.php";


// Check if user is logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// Get user's information

$sql = "SELECT name, email
        FROM users
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// Get cart items

$sql = "SELECT
            cart.id,
            cart.quantity,
            products.name,
            products.price,
            products.image
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        WHERE cart.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// Calculate total

$total = 0;

$cart_items = [];

while ($item = mysqli_fetch_assoc($result)) {

    $item['subtotal'] = $item['price'] * $item['quantity'];

    $total += $item['subtotal'];

    $cart_items[] = $item;
}

mysqli_stmt_close($stmt);


// If cart is empty

if (empty($cart_items)) {

    header("Location: cart.php");
    exit;
}


require_once "includes/header.php";

?>

<h2>Checkout</h2>


<h3>Customer Information</h3>

<p>
    Name:
    <?php echo htmlspecialchars($user['name']); ?>
</p>

<p>
    Email:
    <?php echo htmlspecialchars($user['email']); ?>
</p>


<h3>Order Summary</h3>


<?php foreach ($cart_items as $item) { ?>

    <div class="checkout-item">

        <h4>
            <?php echo htmlspecialchars($item['name']); ?>
        </h4>

        <p>
            Price: ₹<?php echo number_format($item['price'], 2); ?>
        </p>

        <p>
            Quantity: <?php echo $item['quantity']; ?>
        </p>

        <p>
            Subtotal: ₹<?php echo number_format($item['subtotal'], 2); ?>
        </p>

    </div>

    <hr>

<?php } ?>


<h2>
    Total: ₹<?php echo number_format($total, 2); ?>
</h2>


<form action="place_order.php" method="POST">

    <button type="submit">
        Place Order
    </button>

</form>


<?php

require_once "includes/footer.php";

?>