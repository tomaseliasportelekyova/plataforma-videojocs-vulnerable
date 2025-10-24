<?php
session_start();
require "./db_mysqli.php"; // Asegúrate de que $conn esté definido

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (empty($email) || empty($pass)) {
        $_SESSION['error'] = "Completa todos los campos.";
        header("Location: ../login.php");
        exit();
    }

    $sql = "SELECT * FROM usuaris WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuari = $result->fetch_assoc();

    if ($usuari && $usuari['password_hash'] === $pass) {
        $_SESSION['email'] = $usuari['email'];
        $_SESSION['user_id'] = $usuari['id'];
        $_SESSION['nickname'] = $usuari['nickname'];

        // Redirigir al dashboard
        header("Location: ../dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Email o contraseña incorrectos.";
        header("Location: ../login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    $_SESSION['error'] = "Acceso no permitido.";
    header("Location: ../login.php");
    exit();
}
