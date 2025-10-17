<?php
//db_mysqli.php
// Dades de connexió
$host = "IP_DE_LA_VM";
$user = "plataforma_user";
$password = "123456789a";
$database = "plataforma_videojocs";

// Connexió MySQLi
$conn = new mysqli($host, $user, $password, $database);

// Comprovació de la connexió
if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}

echo "La connexió a la BD s'ha realitzat amb èxit! ";