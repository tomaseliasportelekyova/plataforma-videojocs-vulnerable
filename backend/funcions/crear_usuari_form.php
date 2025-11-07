<!DOCTYPE html>
<html lang="ca">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestió de Bases de Dades (Create)</title>
    <meta name="description" content="Connexió a la Base de Dades i operacions CRUD (Create)" />
  </head>
  <body>
    <h2>Operacions CRUD (Create)</h2>
    <form action="./crear_usuari.php" method="POST">
        <label>Nom d'usuari: <input type="text" name="nom_usuari"></label><br>
        <label>Email: <input type="text" name="email"></label><br>
        <label>Password: <input type="text" name="password"></label><br>
        <button type="submit">Crear usuari</button>
    </form>
  </body>
</html>