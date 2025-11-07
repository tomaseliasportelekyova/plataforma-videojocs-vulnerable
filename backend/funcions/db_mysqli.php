<?php
//db_mysqli.php
// Dades de connexió
$host = "localhost"; // O también puedes usar "localhost"
$user = "plataforma_user";
$password = "123456789a";
$database = "plataforma_videojocs";

// Connexió MySQLi
$conn = new mysqli($host, $user, $password, $database);

// Comprovació de la connexió
if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}
