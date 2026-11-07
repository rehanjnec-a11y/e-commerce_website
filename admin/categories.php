
<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Fetch categories and their product counts
$sql = "SELECT 
            categories.id,
            categories.name,
            categories.created_at,
            COUNT(products.id) AS product_count
        FROM categories
        LEFT JOIN products
            ON categories.id = products.category_id
        GROUP BY categories.id, categories.name, categories.created_at
        ORDER BY categories.id DESC";

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

    <title>Manage Categories - Global Mart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 25px;
        }

        h1 {
            color: #222;
        }

        .add-btn {
            display: inline-block;
            background: #198754;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 15px;
        }

        .add-btn:hover {
            background: #146c43;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #222;
            color: white;
        }

        .edit-btn {
            color: #0066cc;
            text-decoration: none;
            margin-right: 10px;
        }

        .delete-btn {
            color: #dc3545;
            text-decoration: none;
        }

        button.delete-btn {
    background: none;
    border: none;
    padding: 0;
    color: #dc3545;
    font-size: 16px;
    cursor: pointer;
}

button.delete-btn:hover {
    text-decoration: underline;
}

        @media (max-width: 700px) {
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

    <h1>Manage Categories</h1>

    <p>
        <a href="index.php">← Back to Dashboard</a>
    </p>

    <a href="add_category.php" class="add-btn">
        + Add New Category
    </a>

    <div class="table-container">

        <table>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Total Products</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>

            <?php while ($category = mysqli_fetch_assoc($result)) { ?>

                <tr>
                    <td>
                        <?php echo (int)$category['id']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </td>

                    <td>
                        <?php echo (int)$category['product_count']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($category['created_at']); ?>
                    </td>

                    <td>
                        <a
                            href="edit_category.php?id=<?php echo (int)$category['id']; ?>"
                            class="edit-btn"
                        >
                            Edit
                        </a>

                        <form
    method="POST"
    action="delete_category.php"
    style="display:inline;"
    onsubmit="return confirm('Are you sure you want to delete this category?');"
>
    <input
        type="hidden"
        name="id"
        value="<?php echo (int)$category['id']; ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
    >

    <button type="submit" class="delete-btn">
        Delete
    </button>
</form>
                    </td>
                </tr>

            <?php } ?>

        </table>

    </div>

</body>
</html>
