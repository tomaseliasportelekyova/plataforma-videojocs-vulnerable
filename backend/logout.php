<?php
// Arxiu: backend/logout.php

session_start(); // Inicia la sessió per poder accedir-hi

// 1. Neteja la cookie de canvis de nickname (bona pràctica)
if (isset($_SESSION['user_id'])) {
     $cookie_name = "nickname_changes_count_" . $_SESSION['user_id'];
     // Envia una cookie caducada per esborrar-la del navegador
     setcookie($cookie_name, '', time() - 3600, "/");
}

// 2. Elimina totes les variables de la sessió (com user_id, nickname, etc.)
session_unset();

// 3. Destrueix la sessió completament
session_destroy();

// 4. Redirigeix a la pàgina de login
header("Location: login.php");
exit(); // Assegura que el script s'atura aquí
?>