<?php
// En /backend/funcions/validacio_login.php

session_start();
// Incluimos nuestro archivo de conexión a la base de datos
require "./db_mysqli.php";

// 1. Verificamos que los datos lleguen por el método POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['pass'] ?? '';

    // 2. Creamos la consulta SQL vulnerable a inyección
    // Esta consulta busca un usuario cuyo email Y contraseña coincidan exactamente.
    // Un atacante podría usar técnicas de inyección para saltarse esta comprobación.
    $sql = "SELECT * FROM usuaris WHERE email = '$email' AND password_hash = '$pass'";

    // 3. Ejecutamos la consulta
    $result = $conn->query($sql);

    // 4. Procesamos el resultado
    if ($result && $result->num_rows > 0) {
        // ¡Éxito! El usuario y la contraseña son correctos
        $usuari = $result->fetch_assoc();
        
        // Guardamos los datos importantes del usuario en la sesión
        $_SESSION['user_id'] = $usuari['id'];
        $_SESSION['nickname'] = $usuari['nickname'];
        
        // Redirigimos al usuario al panel principal
        header("Location: ../dashboard.php");
        exit(); // Detenemos el script para asegurar la redirección
    } else {
        // ¡Fallo! El email o la contraseña son incorrectos
        $_SESSION['error'] = "Email o contraseña incorrectos.";
        
        // Devolvemos al usuario a la página de login para que lo intente de nuevo
        header("Location: ../login.php");
        exit();
    }
    $conn->close();
} else {
    // Si alguien intenta acceder a este archivo directamente sin enviar datos, lo echamos.
    $_SESSION['error'] = "Acceso no permitido.";
    header("Location: ../login.php");
    exit();
}
?>