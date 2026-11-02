<?php

session_start();

require_once "../config/database.php";


// Check login

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;
}


// Check admin role

if ($_SESSION['user_role'] !== 'admin') {

    die("Access denied.");
}


// Get statistics

$product_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$product_count = mysqli_fetch_assoc($product_result)['total'];


$user_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$user_count = mysqli_fetch_assoc($user_result)['total'];


$order_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM orders"
);

$order_count = mysqli_fetch_assoc($order_result)['total'];


$sales_result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE status != 'cancelled'"
);

$total_sales = mysqli_fetch_assoc($sales_result)['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Global Mart</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }

        .header {
            background-color: #222;
            color: white;
            padding: 20px;
        }

        .header h1 {
            margin: 0;
        }

        .container {
            padding: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 25px;
        }

        .card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card h2 {
            margin: 0;
            font-size: 30px;
        }

        .card p {
            color: #666;
        }

        .menu {
            margin-top: 30px;
        }

        .menu a {
            display: inline-block;
            padding: 12px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            background-color: #ff9900;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .menu a:hover {
            background-color: #e68a00;
        }

    </style>

</head>

<body>


<div class="header">

    <h1>Global Mart Admin</h1>

    <p>
        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
    </p>

</div>


<div class="container">

    <h2>Dashboard</h2>


    <div class="cards">

        <div class="card">

            <h2>
                <?php echo $product_count; ?>
            </h2>

            <p>Total Products</p>

        </div>


        <div class="card">

            <h2>
                <?php echo $user_count; ?>
            </h2>

            <p>Total Users</p>

        </div>


        <div class="card">

            <h2>
                <?php echo $order_count; ?>
            </h2>

            <p>Total Orders</p>

        </div>


        <div class="card">

            <h2>
                ₹<?php echo number_format($total_sales, 2); ?>
            </h2>

            <p>Total Sales</p>

        </div>

    </div>


    <div class="menu">

        <h2>Management</h2>

        <a href="products.php">
            Manage Products
        </a>

        <a href="orders.php">
            Manage Orders
        </a>

        <a href="users.php">
            Manage Users
        </a>

        <a href="categories.php">
            Manage Categories
        </a>

    </div>


    <br>

    <a href="../index.php">
        Back to Store
    </a>

    &nbsp;&nbsp;

    <a href="../logout.php">
        Logout
    </a>

</div>


</body>

</html>