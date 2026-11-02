<?php

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($name) || empty($email) || empty($password)) {

        $message = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";

    } else {

        $check_sql = "SELECT id FROM users WHERE email = ?";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param($check_stmt, "s", $email);

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {

            $message = "An account with this email already exists.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "INSERT INTO users (name, email, password)
                    VALUES (?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name,
                $email,
                $password_hash
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Registration successful!";

            } else {

                $message = "Registration failed.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}

require_once "includes/header.php";

?>

<h2>Create Account</h2>

<?php if (!empty($message)) { ?>

    <p>
        <?php echo $message; ?>
    </p>

<?php } ?>

<form method="POST">

    <label>Name</label>
    <br>

    <input
        type="text"
        name="name"
        required
    >

    <br><br>

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
        Register
    </button>

</form>

<?php

require_once "includes/footer.php";

?>