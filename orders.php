<?php

session_start();

require_once "config/database.php";


// Check if user is logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// Get user's orders

$sql = "SELECT
            id,
            total_amount,
            status,
            created_at
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


require_once "includes/header.php";

?>

<h2>My Orders</h2>


<?php if (mysqli_num_rows($result) === 0) { ?>

    <p>You have not placed any orders yet.</p>

<?php } else { ?>

    <?php while ($order = mysqli_fetch_assoc($result)) { ?>

        <div class="order-card">

            <h3>
                Order #<?php echo $order['id']; ?>
            </h3>

            <p>
                Total:
                ₹<?php echo number_format($order['total_amount'], 2); ?>
            </p>

            <p>
                Status:
                <?php echo htmlspecialchars($order['status']); ?>
            </p>

            <p>
                Date:
                <?php echo $order['created_at']; ?>
            </p>

            <a href="order_details.php?id=<?php echo $order['id']; ?>">
                View Order
            </a>

        </div>

        <hr>

    <?php } ?>

<?php } ?>


<?php

mysqli_stmt_close($stmt);

require_once "includes/footer.php";

?>