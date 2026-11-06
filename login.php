<?php

session_start();

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Email and password are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } else {

        $sql = "SELECT id, name, password, role
                FROM users
                WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Statement preparation failed.");
        }

        mysqli_stmt_bind_param($stmt, "s", $email);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
            }
            else {
            header("Location: index.php");
}
exit;

        } else {

            $message = "Invalid email or password.";

        }

        mysqli_stmt_close($stmt);
    }
}

require_once "includes/header.php";

?>

<h2>Login</h2>

<?php if (!empty($message)) { ?>

    <p>
        <?php echo $message; ?>
    </p>

<?php } ?>

<form method="POST">

    <label>Email</label>
    <br>

    <input
        type="email"
        name="email"
        required
    >

    <br><br>

    <label>Password</label>
    <br>

    <input
        type="password"
        name="password"
        required
    >

    <br><br>

    <button type="submit">
        Login
    </button>

</form>

<p>
    Don't have an account?
    <a href="register.php">Register</a>
</p>

<?php

require_once "includes/footer.php";

?>