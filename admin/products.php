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
    die("Access denied. Admins only.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch products from database
$sql = "SELECT products.*, categories.name AS category_name
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.id
        ORDER BY products.id DESC";

$result = mysqli_query($conn, $sql);

// Check query
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
    margin-bottom: 20px;
    color: #222;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    table-layout: fixed;
}

th, td {
    padding: 14px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
    overflow-wrap: break-word;
}

th {
    background: #222;
    color: white;
}

th:nth-child(1) { width: 6%; }
th:nth-child(2) { width: 13%; }
th:nth-child(3) { width: 25%; }
th:nth-child(4) { width: 17%; }
th:nth-child(5) { width: 13%; }
th:nth-child(6) { width: 10%; }
th:nth-child(7) { width: 16%; }

table img {
    width: 75px;
    height: 75px;
    object-fit: contain;
    display: block;
}

a {
    color: #0066cc;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

button {
    border-radius: 5px;
    font-size: 14px;
}

button:hover {
    opacity: 0.85;
}

    </style>
</head>
<body>
    <h1>Manage Products</h1>

<p>
    <a href="index.php">Back to Dashboard</a> |
    <a href="add_product.php">Add New Product</a>
</p>
    <table>

    <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Actions</th>
    </tr>

    <?php while ($product = mysqli_fetch_assoc($result)) { ?>

        <tr>

            <td><?php echo $product['id']; ?></td>

            <td>
                <?php if (!empty($product['image'])) { ?>
                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                         alt="Product Image">
                <?php } else { ?>
                    No Image
                <?php } ?>
            </td>

            <td><?php echo htmlspecialchars($product['name']); ?></td>

            <td><?php echo htmlspecialchars($product['category_name']); ?></td>

            <td>₹<?php echo number_format($product['price'], 2); ?></td>

            <td><?php echo (int)$product['stock']; ?></td>

            <td>
    <a href="edit_product.php?id=<?php echo (int)$product['id']; ?>">
        Edit
    </a>

    <form action="delete_product.php" method="POST"
          style="display:inline;"
          onsubmit="return confirm('Are you sure you want to delete this product?');">

        <input type="hidden" name="id"
               value="<?php echo (int)$product['id']; ?>">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <button type="submit"
                style="background:#dc3545; color:white; border:none;
                       padding:8px 12px; cursor:pointer;">
            Delete
        </button>

    </form>
</td>


</tr>

<?php } ?>

   
</table>
    


</body>
</html>
