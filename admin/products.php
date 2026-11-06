
<?php

session_start();

require_once "../config/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Fetch all products
$sql = "SELECT products.*, categories.name AS category_name
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.id
        ORDER BY products.id DESC";

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
    <title>Manage Products - Global Mart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 25px;
            background: #f4f4f4;
        }

        h1 {
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
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
            color: blue;
        }
    </style>
</head>

<body>

    <h1>Manage Products</h1>

    <p>
        <a href="index.php">Back to Dashboard</a>
    </p>

    <table>

        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
        </tr>

        <?php while ($product = mysqli_fetch_assoc($result)) { ?>

            <tr>

                <td><?php echo $product['id']; ?></td>

                <td>
                    <?php if (!empty($product['image'])) { ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>">
                    <?php } else { ?>
                        No Image
                    <?php } ?>
                </td>

                <td><?php echo htmlspecialchars($product['name']); ?></td>

                <td><?php echo htmlspecialchars($product['category_name']); ?></td>

                <td>₹<?php echo number_format($product['price'], 2); ?></td>

                <td><?php echo $product['stock']; ?></td>

            </tr>

        <?php } ?>

    </table>

</body>
</html>
