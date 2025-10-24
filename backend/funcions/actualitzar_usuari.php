<?php
require "./db_mysqli.php";

if (isset($_POST['id'], $_POST['nom_usuari'])) {
  $id = $_POST['id'];
  $nom_usuari = $_POST['nom_usuari'];
  $sql = "UPDATE usuaris SET nom_usuari = '$nom_usuari' WHERE id = $id";
  $conn->query($sql);
  echo "<p>Nom d'usuari actualitzat!</p>";
  $conn->close();
}
?>