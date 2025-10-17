<?php
require "./db_mysqli.php";

if (isset($_POST['nom_usuari'], $_POST['email'], $_POST['password'])) {
  $nom_usuari = $_POST['nom_usuari'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $sql = "INSERT INTO usuaris (nom_usuari, email, password_hash) VALUES ('$nom_usuari', '$email', '$password')";
  $conn->query($sql);
  echo "<p>Usuari afegit a la base de dades!</p>";
  $conn->close();
}
?>