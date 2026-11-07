
<?php

session_start();

require_once "../config/database.php";

// Admin access check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate category ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Invalid category ID.");
}

$category_id = (int) $_GET['id'];

// Fetch existing category
$sql = "SELECT id, name FROM categories WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$category) {
    die("Category not found.");
}

$error = "";
$category_name = $category['name'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request.");
    }

    $category_name = trim($_POST['category_name'] ?? '');

    if ($category_name === "") {
        $error = "Category name is required.";
    } elseif (strlen($category_name) > 100) {
        $error = "Category name cannot exceed 100 characters.";
    } else {

        // Check duplicate name, excluding current category
        $check_sql = "SELECT id FROM categories
                      WHERE name = ? AND id != ?";

        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param(
            $check_stmt,
            "si",
            $category_name,
            $category_id
        );

        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "Another category already uses this name.";
        } else {

            // Update category
            $update_sql = "UPDATE categories SET name = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);

            mysqli_stmt_bind_param(
                $update_stmt,
                "si",
                $category_name,
                $category_id
            );

            if (mysqli_stmt_execute($update_stmt)) {
                header("Location: categories.php");
                exit;
            } else {
                $error = "Failed to update category.";
            }

            mysqli_stmt_close($update_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Category - Global Mart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            box-sizing: border-box;
        }

        button {
            background: #0066cc;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Edit Category</h1>

        <p><a href="categories.php">← Back to Categories</a></p>

        <?php if ($error !== "") { ?>
            <p class="error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php } ?>

        <form method="POST">

            <label for="category_name">Category Name</label>

            <input
                type="text"
                id="category_name"
                name="category_name"
                maxlength="100"
                value="<?php echo htmlspecialchars($category_name); ?>"
                required
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">Update Category</button>

        </form>

    </div>

</body>
</html>
