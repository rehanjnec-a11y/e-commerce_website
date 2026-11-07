
<?php

session_start();

require_once "../config/database.php";

// 1. Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// 3. Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

// 4. Fetch categories for dropdown
$category_result = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = "Invalid form submission.";
    }

    // Get form values
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';

    // Validate product name
    if ($name === '') {
        $errors[] = "Product name is required.";
    }

    // Validate description
    if ($description === '') {
        $errors[] = "Description is required.";
    }

    // Validate category
    $category_id = filter_var($category_id, FILTER_VALIDATE_INT);

    if (!$category_id || $category_id <= 0) {
        $errors[] = "Please select a valid category.";
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
    if (!is_numeric($price) || !is_finite((float)$price) || (float)$price <= 0) {
        $errors[] = "Enter a valid price greater than zero.";
    }

    // Validate stock
    if (
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0
    ) {
        $errors[] = "Enter a valid stock quantity.";
    }

    // 5. Handle image upload
    $image_name = null;

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

            if (!isset($allowed_types[$mime]) || getimagesize($tmp_path) === false) {
                $errors[] = "Only JPG, PNG and WEBP images are allowed.";
            } else {
                $image_name = bin2hex(random_bytes(16))
                    . "." . $allowed_types[$mime];
            }
        }
    }

    // 6. Insert product if validation passed
    if (empty($errors)) {

        if ($image_name !== null) {
            $upload_path = "../uploads/products/" . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Could not save uploaded image.";
            }
        }

        if (empty($errors)) {
            $sql = "INSERT INTO products
                    (category_id, name, description, price, stock, image)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "issdis",
                $category_id,
                $name,
                $description,
                $price,
                $stock,
                $image_name
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                mysqli_stmt_close($stmt);
                header("Location: products.php?added=1");
                exit;
            } else {
                if ($image_name !== null) {
                    unlink("../uploads/products/" . $image_name);
                }

                $errors[] = "Failed to add product.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product - Global Mart</title>

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
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Add New Product</h1>

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
               value="<?php echo htmlspecialchars($name ?? ''); ?>" required>

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select Category</option>

            <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>
                <option value="<?php echo $category['id']; ?>"
                    <?php echo (($category_id ?? '') == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php } ?>
        </select>

        <label>Description</label>
        <textarea name="description" required><?php
            echo htmlspecialchars($description ?? '');
        ?></textarea>

        <label>Price (₹)</label>
        <input type="number" name="price" step="0.01" min="0.01"
               value="<?php echo htmlspecialchars($price ?? ''); ?>" required>

        <label>Stock Quantity</label>
        <input type="number" name="stock" min="0" step="1"
               value="<?php echo htmlspecialchars($stock ?? ''); ?>" required>

        <label>Product Image (Optional)</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

        <button type="submit">Add Product</button>

    </form>

</div>

</body>
</html>
