<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once "config/database.php";


// Check if user is logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


$user_id = $_SESSION['user_id'];


// Get cart items

$sql = "SELECT 
            cart.id,
            cart.quantity,
            products.name,
            products.price,
            products.image,
            products.stock
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        WHERE cart.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

require_once "includes/header.php";

?>

<h2>My Cart</h2>

<?php if (mysqli_num_rows($result) === 0) { ?>

    <p>Your cart is empty.</p>

<?php } else { ?>

    <?php $total = 0; ?>

    <?php while ($item = mysqli_fetch_assoc($result)) { ?>

        <?php

        $subtotal = $item['price'] * $item['quantity'];

        $total += $subtotal;

        ?>

        <div class="cart-item">

            <img
                src="uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                alt="<?php echo htmlspecialchars($item['name']); ?>"
                width="150"
            >

            <h3>
                <?php echo htmlspecialchars($item['name']); ?>
            </h3>

            <p>
                Price: ₹<?php echo $item['price']; ?>
            </p>

            <p>
    Quantity:

    
<!-- Increase Quantity -->
<form method="POST" action="update_cart.php" style="display:inline;">

    <input type="hidden" name="id"
           value="<?php echo (int)$item['id']; ?>">

    <input type="hidden" name="action" value="increase">

    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <button type="submit">+</button>

</form>

<!-- Decrease Quantity -->
<form method="POST" action="update_cart.php" style="display:inline;">

    <input type="hidden" name="id"
           value="<?php echo (int)$item['id']; ?>">

    <input type="hidden" name="action" value="decrease">

    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <button type="submit">-</button>

</form>

</p>


<form method="POST" action="remove_from_cart.php"
      onsubmit="return confirm('Remove this product from your cart?');">

    <input
        type="hidden"
        name="id"
        value="<?php echo (int)$item['id']; ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
    >

    <button type="submit">Remove</button>

</form>


            <p>
                Subtotal: ₹<?php echo number_format($subtotal, 2); ?>
            </p>

        </div>

        <hr>

    <?php } ?>

    <h2>
        Total: ₹<?php echo number_format($total, 2); ?>
    </h2>

    <a href="checkout.php">
    Proceed to Checkout
</a>

<?php } ?>

<?php

mysqli_stmt_close($stmt);

require_once "includes/footer.php";

?>