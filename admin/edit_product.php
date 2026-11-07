
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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate product ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Invalid product ID.");
}

// Fetch existing product
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    die("Product not found.");
}

// Fetch categories
$category_result = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name"
);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = "Invalid form submission.";
    }

    // Get submitted values
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = filter_var(
        $_POST['category_id'] ?? '',
        FILTER_VALIDATE_INT
    );
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';

    // Validate name and description
    if ($name === '') {
        $errors[] = "Product name is required.";
    }

    if ($description === '') {
        $errors[] = "Description is required.";
    }

    // Validate category
    if (!$category_id || $category_id <= 0) {
        $errors[] = "Select a valid category.";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM categories WHERE id = ?"
        );
        mysqli_stmt_bind_param($check, "i", $category_id);
        mysqli_stmt_execute($check);
        $category_check = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($category_check) === 0) {
            $errors[] = "Selected category does not exist.";
        }

        mysqli_stmt_close($check);
    }

    // Validate price
    if (
        !is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price <= 0
    ) {
        $errors[] = "Enter a valid price.";
    }

    // Validate stock
    if (
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0
    ) {
        $errors[] = "Enter a valid stock quantity.";
    }

    // Keep the old image unless a new one is uploaded
    $image_name = $product['image'];
    $new_image_path = null;

    // Process optional new image
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image must be smaller than 2 MB.";
        } else {
            $tmp_path = $_FILES['image']['tmp_name'];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp_path);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (
                !isset($allowed_types[$mime]) ||
                getimagesize($tmp_path) === false
            ) {
                $errors[] = "Only JPG, PNG and WEBP images are allowed.";
            } else {
                $image_name = bin2hex(random_bytes(16))
                    . "." . $allowed_types[$mime];
            }
        }
    }

    // Update product
    if (empty($errors)) {

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] === UPLOAD_ERR_OK
        ) {
            $new_image_path = "../uploads/products/" . $image_name;

            if (!move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $new_image_path
            )) {
                $errors[] = "Could not save the new image.";
            }
        }

        if (empty($errors)) {

            $sql = "UPDATE products
                    SET category_id = ?,
                        name = ?,
                        description = ?,
                        price = ?,
                        stock = ?,
                        image = ?
                    WHERE id = ?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "issdisi",
                $category_id,
                $name,
                $description,
                $price,
                $stock,
                $image_name,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                // Delete old image only after successful update
                if (
                    $new_image_path !== null &&
                    !empty($product['image'])
                ) {
                    $old_image_path =
                        "../uploads/products/" . basename($product['image']);

                    if (is_file($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: products.php?updated=1");
                exit;

            } else {

                if ($new_image_path !== null && is_file($new_image_path)) {
                    unlink($new_image_path);
                }

                $errors[] = "Could not update product.";
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Preserve submitted values after validation errors
    $product['name'] = $name;
    $product['description'] = $description;
    $product['category_id'] = $category_id;
    $product['price'] = $price;
    $product['stock'] = $stock;
    $product['image'] = $image_name;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product - Global Mart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-top: 7px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 12px 20px;
            background: orange;
            color: white;
            border: none;
            cursor: pointer;
        }

        .error {
            color: red;
        }

        .current-image {
            width: 120px;
            display: block;
            margin-top: 10px;
            object-fit: contain;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Edit Product</h1>

    <a href="products.php">Back to Products</a>

    <?php foreach ($errors as $error) { ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <label>Product Name</label>
        <input type="text" name="name"
               value="<?php echo htmlspecialchars($product['name']); ?>"
               required>

        <label>Category</label>
        <select name="category_id" required>

            <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>

                <option value="<?php echo $category['id']; ?>"
                    <?php echo ($product['category_id'] == $category['id'])
                        ? 'selected' : ''; ?>>

                    <?php echo htmlspecialchars($category['name']); ?>

                </option>

            <?php } ?>

        </select>

        <label>Description</label>
        <textarea name="description" required><?php
            echo htmlspecialchars($product['description']);
        ?></textarea>

        <label>Price (₹)</label>
        <input type="number" name="price" step="0.01" min="0.01"
               value="<?php echo htmlspecialchars((string)$product['price']); ?>"
               required>

        <label>Stock Quantity</label>
        <input type="number" name="stock" min="0" step="1"
               value="<?php echo htmlspecialchars((string)$product['stock']); ?>"
               required>

        <label>Current Image</label>

        <?php if (!empty($product['image'])) { ?>
            <img class="current-image"
                 src="../uploads/products/<?php
                    echo rawurlencode(basename($product['image']));
                 ?>"
                 alt="Current product image">
        <?php } else { ?>
            <p>No image available.</p>
        <?php } ?>

        <label>Choose New Image (Optional)</label>
        <input type="file" name="image"
               accept=".jpg,.jpeg,.png,.webp">

        <button type="submit">Update Product</button>

    </form>

</div>

</body>
</html>
