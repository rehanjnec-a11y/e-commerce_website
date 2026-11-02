<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Global Mart</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>

    <h1>Global Mart</h1>

    <nav>

    <a href="index.php">Home</a>

    <a href="#">Products</a>

    <a href="cart.php">Cart</a>

    <?php if (isset($_SESSION['user_id'])) { ?>

        <span>
            Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </span>

        <a href="orders.php">My Orders</a>

        <a href="logout.php">Logout</a>

    <?php } else { ?>

        <a href="login.php">Login</a>

        <a href="register.php">Register</a>

    <?php } ?>

</nav>

</header>

