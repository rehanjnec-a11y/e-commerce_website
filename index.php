<?php

require_once "config/database.php";

$sql = "SELECT * FROM products";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

require_once "includes/header.php";

?>




    <h2>Our Products</h2>

    <div class="products">

        <?php while ($product = mysqli_fetch_assoc($result)) { ?>

            <div class="product-card">
                <img 
    src="uploads/products/<?php echo $product['image']; ?>" 
    alt="<?php echo $product['name']; ?>"
>



                <h3>
                    <?php echo $product['name']; ?>
                </h3>

                <p>
                    <?php echo $product['description']; ?>
                </p>

                <p class="price">
                    ₹<?php echo $product['price']; ?>
                </p>

                <p class="stock">
                    Stock: <?php echo $product['stock']; ?>
                </p>

                <a href="product.php?id=<?php echo $product['id']; ?>">
    View Details
</a>

                <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="add-cart">
    Add to Cart
</a>

            </div>

        <?php } ?>

    </div>



<?php

require_once "includes/footer.php";

?>