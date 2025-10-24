<!DOCTYPE html>
<html lang="ca">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestió de Bases de Dades (Update)</title>
    <meta name="description" content="Connexió a la Base de Dades i operacions CRUD (Update)" />
  </head>
  <body>
    <h2>Operacions CRUD (Update)</h2>
    <form action="./actualitzar_usuari.php" method="POST">
      <label>ID Usuari: <input type="text" name="id"></label><br>
      <label>Nou Nom d'usuari: <input type="text" name="nom_usuari"></label><br>
      <button type="submit">Actualitzar</button>
    </form>
  </body>
</html>