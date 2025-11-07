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

        if (!empty($user_photo_db)) {
             // Construïm la ruta del sistema des d'aquest script per comprovar
             // __DIR__ és /.../backend/funcions
             // Volem anar a /.../backend/ i llavors aplicar la ruta de la BBDD
             
             // === CANVI: Hem tret el ltrim() ===
             $path_on_server = __DIR__ . '/../' . $user_photo_db;
             // Això ara resol a:
             // /backend/funcions/../ -> /backend/
             // /backend/ + ../frontend/imatges/users/... -> /frontend/imatges/users/... (CORRECTE!)

             if (file_exists($path_on_server)) {
                 $_SESSION['user_photo'] = $user_photo_db; // Guardem la ruta relativa per l'HTML
             } else {
                 // Si el fitxer no existeix al servidor, posem la default
                 $_SESSION['user_photo'] = $default_photo; 
             }
        } else {
             $_SESSION['user_photo'] = $default_photo; // Si no hi ha res a la BBDD, posem la default
        }
        // =====================================================================

        // Redirigir al dashboard (ara és juegos.php, segons el nostre últim canvi)
        header("Location: ../dashboard.php"); // O 'juegos.php' si ja ho has canviat
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