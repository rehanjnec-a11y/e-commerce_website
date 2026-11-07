
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

// Fetch all users (never fetch password hashes for display)
$sql = "SELECT id, name, email, role, created_at
        FROM users
        ORDER BY id DESC";

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

    <title>Manage Users - Global Mart</title>

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

        .role {
            padding: 6px 10px;
            border-radius: 5px;
            background: #eee;
            display: inline-block;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
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

    <h1>Manage Users</h1>

    <p>
        <a href="index.php">← Back to Dashboard</a>
    </p>

    <div class="table-container">

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Registration Date</th>
            </tr>

            <?php while ($user = mysqli_fetch_assoc($result)) { ?>

                <tr>
                    <td><?php echo (int)$user['id']; ?></td>

                    <td>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>

                    <td>
                        <span class="role">
                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($user['created_at']); ?>
                    </td>
                </tr>

            <?php } ?>

        </table>

    </div>

</body>
</html>
