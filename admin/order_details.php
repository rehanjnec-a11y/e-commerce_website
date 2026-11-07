
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate order ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Invalid Order ID.");
}

$order_id = (int) $_GET['id'];

// Fetch order and customer information
$sql = "SELECT orders.*, users.name AS customer_name,
               users.email AS customer_email
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        WHERE orders.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("Order not found.");
}

// Fetch order items
$sql = "SELECT order_items.*, products.name AS product_name,
               products.image AS product_image
        FROM order_items
        INNER JOIN products ON order_items.product_id = products.id
        WHERE order_items.order_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

$items = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details - Global Mart Admin</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 25px;
        }

        .container {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1, h2 {
            color: #222;
        }

        .details p {
            font-size: 16px;
            margin: 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #222;
            color: white;
        }

        img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }
        .table-container {
    width: 100%;
    overflow-x: auto;
}
    </style>
</head>

<body>

<div class="container">

    <h1>Order Details #<?php echo (int)$order['id']; ?></h1>

    <p>
        <a href="orders.php">← Back to Orders</a>
    </p>

    <h2>Customer Information</h2>

    <div class="details">
        <p><strong>Name:</strong>
            <?php echo htmlspecialchars($order['customer_name']); ?>
        </p>

        <p><strong>Email:</strong>
            <?php echo htmlspecialchars($order['customer_email']); ?>
        </p>

        <p><strong>Order Date:</strong>
            <?php echo htmlspecialchars($order['created_at']); ?>
        </p>

        <p><strong>Status:</strong>
            <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
        </p>

        <p><strong>Total Amount:</strong>
            ₹<?php echo number_format($order['total_amount'], 2); ?>
        </p>
    </div>

    <h2>Update Order Status</h2>

<form action="update_order_status.php" method="POST">

    <input type="hidden" name="order_id"
           value="<?php echo (int)$order['id']; ?>">

    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <select name="status" required>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
    </select>

    <button type="submit">Update Status</button>

</form>

    <h2>Products Ordered</h2>
    

  


    <div class="table-container">
    <table>
         <tr>
            <th>Product</th>
            <th>Image</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>

        <?php while ($item = mysqli_fetch_assoc($items)) { ?>

            <tr>
                <td>
                    <?php echo htmlspecialchars($item['product_name']); ?>
                </td>

                <td>
                    <?php if (!empty($item['product_image'])) { ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>"
                             alt="Product">
                    <?php } else { ?>
                        No Image
                    <?php } ?>
                </td>

                <td><?php echo (int)$item['quantity']; ?></td>

                <td>₹<?php echo number_format($item['price'], 2); ?></td>

                <td>
                    ₹<?php echo number_format(
                        $item['quantity'] * $item['price'], 2
                    ); ?>
                </td>
            </tr>

        <?php } ?>

    </table>
   
</div>
       

</div>

</body>
</html>
