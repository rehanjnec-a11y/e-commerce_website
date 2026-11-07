
<?php

require_once "config/database.php";

// Get filter values
$search = trim($_GET['search'] ?? '');
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build SQL query
$sql = "SELECT products.*, categories.name AS category_name
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.id
        WHERE 1=1";

$params = [];
$types = "";

// Search by product name
if ($search !== '') {
    $sql .= " AND products.name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Filter by category
if ($category_id !== '' && ctype_digit($category_id)) {
    $sql .= " AND products.category_id = ?";
    $params[] = (int)$category_id;
    $types .= "i";
}

// Minimum price
if ($min_price !== '' && is_numeric($min_price) && $min_price >= 0) {
    $sql .= " AND products.price >= ?";
    $params[] = (float)$min_price;
    $types .= "d";
}

// Maximum price
if ($max_price !== '' && is_numeric($max_price) && $max_price >= 0) {
    $sql .= " AND products.price <= ?";
    $params[] = (float)$max_price;
    $types .= "d";
}

// Sorting
if ($sort === 'price_low') {
    $sql .= " ORDER BY products.price ASC";
} elseif ($sort === 'price_high') {
    $sql .= " ORDER BY products.price DESC";
} else {
    $sql .= " ORDER BY products.created_at DESC";
}



// Save filter parameters
$filter_params = $params;
$filter_types = $types;

// Count matching products
$count_sql = "SELECT COUNT(*) AS total
              FROM products
              WHERE 1=1";

if ($search !== '') {
    $count_sql .= " AND name LIKE ?";
}

if ($category_id !== '' && ctype_digit($category_id)) {
    $count_sql .= " AND category_id = ?";
}

if ($min_price !== '' && is_numeric($min_price) && $min_price >= 0) {
    $count_sql .= " AND price >= ?";
}

if ($max_price !== '' && is_numeric($max_price) && $max_price >= 0) {
    $count_sql .= " AND price <= ?";
}

// Execute count query
$count_stmt = mysqli_prepare($conn, $count_sql);

if (!empty($filter_params)) {
    mysqli_stmt_bind_param(
        $count_stmt,
        $filter_types,
        ...$filter_params
    );
}

mysqli_stmt_execute($count_stmt);

$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);

$total_products = (int)$count_row['total'];

mysqli_stmt_close($count_stmt);

// Pagination settings
$products_per_page = 8;
$total_pages = (int)ceil($total_products / $products_per_page);

$current_page = isset($_GET['page']) && ctype_digit($_GET['page'])
    ? max(1, (int)$_GET['page'])
    : 1;

$current_page = min($current_page, max(1, $total_pages));

$offset = ($current_page - 1) * $products_per_page;

// Add pagination to product query
$sql .= " LIMIT ? OFFSET ?";

$params = $filter_params;
$params[] = $products_per_page;
$params[] = $offset;

$final_types = $filter_types . "ii";

// Execute product query
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Product query preparation failed.");
}

mysqli_stmt_bind_param($stmt, $final_types, ...$params);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);






// Fetch categories
$category_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$category_result = mysqli_query($conn, $category_sql);

include "includes/header.php";

?>

<main class="container">

    <h2>Our Products</h2>

    <!-- Search and Filter Form -->
    <form method="GET" action="index.php" class="filter-form">

        <input
            type="text"
            name="search"
            placeholder="Search products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="category">

            <option value="">All Categories</option>

            <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>

                <option
                    value="<?php echo (int)$category['id']; ?>"
                    <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>

            <?php } ?>

        </select>

        <input
            type="number"
            name="min_price"
            placeholder="Min Price"
            min="0"
            step="0.01"
            value="<?php echo htmlspecialchars($min_price); ?>"
        >

        <input
            type="number"
            name="max_price"
            placeholder="Max Price"
            min="0"
            step="0.01"
            value="<?php echo htmlspecialchars($max_price); ?>"
        >

        <select name="sort">

            <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>
                Newest First
            </option>

            <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>
                Price: Low to High
            </option>

            <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>
                Price: High to Low
            </option>

        </select>

        <button type="submit">Apply Filters</button>

        <a href="index.php">Clear Filters</a>

    </form>

    <!-- Product Listing -->
    <div class="products">

        <?php if (mysqli_num_rows($result) > 0) { ?>

            <?php while ($product = mysqli_fetch_assoc($result)) { ?>

                <div class="product-card">

                    <img
                        src="uploads/products/<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                    >

                    <h3>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                    </p>

                    <p class="price">
                        ₹<?php echo number_format((float)$product['price'], 2); ?>
                    </p>

                    <p class="stock">
                        Stock: <?php echo (int)$product['stock']; ?>
                    </p>

                    <a href="product.php?id=<?php echo (int)$product['id']; ?>">
                        View Details
                    </a>

                    <a
                        href="add_to_cart.php?id=<?php echo (int)$product['id']; ?>"
                        class="add-cart"
                    >
                        Add to Cart
                    </a>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>No products found. Try changing your search or filters.</p>

        <?php } ?>

    </div>

    
    <p class="product-count">
        Showing <?php echo mysqli_num_rows($result); ?>
        of <?php echo $total_products; ?> products
    </p>

    <?php if ($total_pages > 1) { ?>

        <div class="pagination">

            <?php if ($current_page > 1) { ?>

                <?php
                    $query = $_GET;
                    $query['page'] = $current_page - 1;
                ?>

                <a href="?<?php echo htmlspecialchars(http_build_query($query)); ?>">
                    Previous
                </a>

            <?php } ?>

            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>

                <?php
                    $query = $_GET;
                    $query['page'] = $i;
                ?>

                <a
                    href="?<?php echo htmlspecialchars(http_build_query($query)); ?>"
                    class="<?php echo ($i === $current_page) ? 'active' : ''; ?>"
                >
                    <?php echo $i; ?>
                </a>

            <?php } ?>

            <?php if ($current_page < $total_pages) { ?>

                <?php
                    $query = $_GET;
                    $query['page'] = $current_page + 1;
                ?>

                <a href="?<?php echo htmlspecialchars(http_build_query($query)); ?>">
                    Next
                </a>

            <?php } ?>

        </div>

    <?php } ?>


</main>

<?php

mysqli_stmt_close($stmt);

require_once "includes/footer.php";

?>
