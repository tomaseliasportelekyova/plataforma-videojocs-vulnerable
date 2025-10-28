<?php
session_start();
// Asegúrate que la ruta sea correcta desde validacio_login.php
require "./db_mysqli.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (empty($email) || empty($pass)) {
        $_SESSION['error'] = "Completa todos los campos.";
        header("Location: ../login.php");
        exit();
    }

    // Ampliem la consulta per agafar també la foto
    $sql = "SELECT id, nickname, email, password_hash, photo FROM usuaris WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
         $_SESSION['error'] = "Error preparing statement: " . $conn->error;
         header("Location: ../login.php");
         exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuari = $result->fetch_assoc();

    // Comprovem contrasenya (Recorda: això NO ÉS SEGUR sense password_verify)
    if ($usuari && $usuari['password_hash'] === $pass) { 
        // Guardem dades a la sessió
        $_SESSION['email'] = $usuari['email'];
        $_SESSION['user_id'] = $usuari['id'];
        $_SESSION['nickname'] = $usuari['nickname'];

        // ========= NOU: GUARDAR FOTO A LA SESSIÓ =========
        // Comprovem si té foto i si l'arxiu existeix
        $default_photo = '../frontend/imatges/users/default_user.png';
        $user_photo_path = $usuari['photo'] ?? $default_photo;
        
        // Verifiquem si la ruta guardada és vàlida i si l'arxiu existeix realment
        // Important: La comprovació file_exists necessita la ruta des del servidor,
        // però a la sessió guardem la ruta relativa que usarà l'HTML/CSS.
        // Assumim que la ruta guardada a la BBDD ja és la correcta per l'HTML.
        // Si la foto és null, buida o la default, guardem la default a la sessió.
        if (empty($user_photo_path) || $user_photo_path == $default_photo || !file_exists($user_photo_path)) {
             $_SESSION['user_photo'] = $default_photo;
        } else {
             $_SESSION['user_photo'] = $user_photo_path;
        }
        // ================================================

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
?>