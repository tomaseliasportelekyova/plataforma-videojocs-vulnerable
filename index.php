<?php
session_start();

$valid_email = "admin@example.com";
$valid_pass = "12345";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if ($email === $valid_email && $pass === $valid_pass) {
        $_SESSION['email'] = $email;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Email o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <!-- TÍTULO CENTRADO -->
    <h1 class="login-title">Login</h1>

    <div class="imgcontainer">
        <img src="img_avatar2.png" alt="Avatar" class="avatar">
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <label for="email"><b>Email</b></label>
    <input type="text" placeholder="Enter email" name="email" required>
    <br>
    <label for="pass"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="pass" required>

    <label class="remember-label">
        <input type="checkbox" name="remember" checked> Remember me
    </label>

    <button type="submit">Login</button>

    <div class="bottom-container">
        <a href="#">Forgot password?</a> | 
        <a href="register.php">Create account</a>
    </div>
</form>

</body>
</html>