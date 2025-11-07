<?php
require "./db_mysqli.php";

if (isset($_POST['id'])) {
  $id = $_POST['id'];
  $sql = "DELETE FROM usuaris WHERE id = $id";
  $conn->query($sql);
  echo "<p>Usuari eliminat!</p>";
  $conn->close();
}
?>