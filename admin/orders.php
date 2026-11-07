
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

// Fetch all orders with customer information
$sql = "SELECT orders.*, users.name AS customer_name,
               users.email AS customer_email
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        ORDER BY orders.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Global Mart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 25px;
            background: #f4f4f4;
        }

        h1 {
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
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

        .status {
            padding: 6px 10px;
            border-radius: 5px;
            background: #eee;
            display: inline-block;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>

</head>

<body>

    <h1>Manage Orders</h1>

    <p>
        <a href="index.php">Back to Dashboard</a>
    </p>

    <table>

        <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Total Amount</th>
            <th>Order Status</th>
            <th>Order Date</th>
            <th>Actions</th>
        </tr>

        <?php while ($order = mysqli_fetch_assoc($result)) { ?>

            <tr>

                <td>
                    #<?php echo (int)$order['id']; ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($order['customer_name']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($order['customer_email']); ?>
                </td>

                <td>
                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                </td>

                <td>
                    <span class="status">
                        <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                    </span>
                </td>

                <td>
                    <?php echo htmlspecialchars($order['created_at']); ?>
                </td>

                <td>
    <a href="order_details.php?id=<?php echo (int)$order['id']; ?>">
        View Details
    </a>
</td>

            </tr>

            

        <?php } ?>

    </table>

</body>
</html>
