<?php

require_once "config/database.php";

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Invalid product ID.");
}

$product_id = (int) $_GET['id'];

$sql = "SELECT * FROM products WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Statement preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $product_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$product = mysqli_fetch_assoc($result);

if (!$product) {
    die("Product not found.");
}

mysqli_stmt_close($stmt);

require_once "includes/header.php";

?>

<h2><?php echo $product['name']; ?></h2>

<img
    src="uploads/products/<?php echo $product['image']; ?>"
    alt="<?php echo $product['name']; ?>"
    width="300"
>

<p>
    <?php echo $product['description']; ?>
</p>

<p>
    Price: ₹<?php echo $product['price']; ?>
</p>

<p>
    Stock: <?php echo $product['stock']; ?>
</p>

<a href="#">
    Add to Cart
</a>

<?php

require_once "includes/footer.php";

?>