<?php
// Conexión a la base de datos
$conexion = new mysqli("172.18.33.241", "plataforma_user", "123456789a", "plataforma_videojocs");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener el nombre del juego desde la URL
$juego = $_GET['juego'] ?? '';
$juego = trim($juego);

// Validar entrada
if ($juego === '') {
    die("Juego no especificado.");
}

// Consulta de ranking
$sql = "SELECT jugador, puntos, fecha FROM ranking WHERE juego = ? ORDER BY puntos DESC LIMIT 10";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $juego);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ranking - <?php echo htmlspecialchars($juego); ?></title>
  <link rel="stylesheet" href="../frontend/assets/css/style_ranking.css" />
</head>
<body>
  <header class="ranking-header">
    <h1>Ranking de jugadores - <?php echo htmlspecialchars($juego); ?></h1>
  </header>

  <main class="ranking-container">
    <section class="game-info">
      <img src="../frontend/imatges/<?php echo strtolower(str_replace(' ', '-', $juego)); ?>.jpg" alt="<?php echo htmlspecialchars($juego); ?>" class="game-banner" />
      <div class="game-details">
        <h2><?php echo htmlspecialchars($juego); ?></h2>
        <p><strong>Plataforma:</strong> Información no disponible</p>
        <p><strong>Género:</strong> Información no disponible</p>
        <p><strong>Puntuación media:</strong> Información no disponible</p>
      </div>
    </section>

    <section class="ranking-table">
      <h3>Top jugadores</h3>
      <table>
        <thead>
          <tr>
            <th>Posición</th>
            <th>Jugador</th>
            <th>Puntos</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $posicion = 1;
          while ($fila = $resultado->fetch_assoc()) {
              echo "<tr>
                      <td>{$posicion}</td>
                      <td>" . htmlspecialchars($fila['jugador']) . "</td>
                      <td>{$fila['puntos']}</td>
                      <td>{$fila['fecha']}</td>
                    </tr>";
              $posicion++;
          }
          if ($posicion === 1) {
              echo "<tr><td colspan='4'>No hay datos de ranking para este juego.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
