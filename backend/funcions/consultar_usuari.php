<?php
require "./db_mysqli.php";

if (isset($_GET['id'])) {
  $id = $_GET['id'];
  $sql = "SELECT * FROM usuaris WHERE id = $id";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    $usuari = $result->fetch_assoc();
    echo "<p>Nom d'usuari: " . $usuari['nom_usuari'] . "</p>";
    echo "<p>Email: " . $usuari['email'] . "</p>";
  } else {
    echo "<p>No s'ha trobat cap usuari.</p>";
  }
  $conn->close();
}
?>