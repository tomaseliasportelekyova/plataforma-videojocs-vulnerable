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

        // ========= CORRECCIÓ: GUARDAR FOTO A LA SESSIÓ (SIMPLIFICAT) =========
        $default_photo = '../frontend/imatges/users/default_user.png';
        $user_photo_db = $usuari['photo'] ?? null; // Agafem el valor de la BBDD
        
        // Si la BBDD té una ruta i NO és buida, la usem. Sinó, la default.
        // Assumim que la ruta a la BBDD és la correcta per l'HTML (relativa des del backend/).
        if (!empty($user_photo_db)) {
             $_SESSION['user_photo'] = $user_photo_db; 
        } else {
             $_SESSION['user_photo'] = $default_photo;
        }
        // Hem tret el file_exists() d'aquí perquè pot donar problemes amb rutes relatives.
        // El navegador ja intentarà carregar la imatge; si no existeix, mostrarà l'alt text o res.
        // =====================================================================

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