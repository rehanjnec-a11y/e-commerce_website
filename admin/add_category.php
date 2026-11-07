
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

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$category_name = "";

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

        // Check for duplicate category
        $check_sql = "SELECT id FROM categories WHERE name = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param($check_stmt, "s", $category_name);
        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "This category already exists.";
        } else {

            // Insert category
            $sql = "INSERT INTO categories (name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "s", $category_name);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: categories.php");
                exit;
            } else {
                $error = "Failed to add category.";
            }

            mysqli_stmt_close($stmt);
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

    <title>Add Category - Global Mart</title>

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
            background: #198754;
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

        <h1>Add New Category</h1>

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
                placeholder="Enter category name"
                required
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">Add Category</button>

        </form>

    </div>

</body>
</html>
