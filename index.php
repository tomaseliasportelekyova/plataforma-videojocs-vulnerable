<?php
session_start();

// Credenciales de ejemplo — en producción deberían venir de una base de datos
$valid_user = "admin";
$valid_pass = "12345";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if ($user === $valid_user && $pass === $valid_pass) {
        $_SESSION['username'] = $user;
        header("Location: dashboard.php"); // Redirige al panel principal
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- 👇 Importa tu archivo CSS -->
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div class="imgcontainer">
        <img src="img_avatar2.png" alt="Avatar" class="avatar">
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <label for="user"><b>Username</b></label>
    <input type="text" placeholder="Enter Username" name="user" required>

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
