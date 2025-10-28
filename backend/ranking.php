<?php
session_start();

// Mantenemos la lógica de sesión
if (!isset($_SESSION['nickname'], $_SESSION['user_id'])) {
    $nickname = null;
    $user_id = null;
} else {
    $nickname = $_SESSION['nickname'];
    $user_id = $_SESSION['user_id'];
}

// Incluir conexión BBDD
require "./funcions/db_mysqli.php";

// ========= DEFINIM LA PÀGINA ACTUAL PEL HEADER =========
$paginaActual = 'ranking';
// =======================================================

// --- Obtener Ranking GLOBAL ---
$sql_ranking_global = "SELECT u.id as user_id, u.nickname, SUM(p.puntuacio_obtinguda) as total_puntos, MAX(p.data_partida) as ultima_partida_fecha
                       FROM partides p
                       JOIN usuaris u ON p.usuari_id = u.id
                       GROUP BY u.id, u.nickname
                       ORDER BY total_puntos DESC
                       LIMIT 20";

$stmt_ranking_global = $conn->prepare($sql_ranking_global);
$stmt_ranking_global->execute();
$result_ranking = $stmt_ranking_global->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ranking Global - Shit Games</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_ranking_dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>
  <main class="ranking-main-content">
      <div class="ranking-panel">
          <h1 class="ranking-titulo"><i class="fas fa-globe-europe"></i> Ranking Global</h1>

          <section class="ranking-table-section">
              <h2><i class="fas fa-star" style="color: #FFD700;"></i> Top Jugadores Globales</h2>
              <div class="table-responsive">
                  <table class="ranking-table">
                      <thead>
                          <tr>
                              <th>#</th>
                              <th>Jugador</th>
                              <th>Puntuación Total</th>
                              <th>Última Partida</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php
                          $posicion = 1;
                          if ($result_ranking->num_rows > 0) {
                              while ($fila = $result_ranking->fetch_assoc()) {
                                  $fecha_formateada = $fila['ultima_partida_fecha'] ? date("d/m/Y", strtotime($fila['ultima_partida_fecha'])) : 'N/A';
                                  
                                  // --- NICKNAME CLICABLE ---
                                  // Enllaç provisional a '#'
                                  $nickname_link = '<a href="#" class="nickname-link" title="Ver perfil de ' . htmlspecialchars($fila['nickname']) . '">' . htmlspecialchars($fila['nickname']) . '</a>';
                                  
                                  echo "<tr>
                                          <td>{$posicion}</td>
                                          <td><div class='player-rank'>" . $nickname_link . "</div></td>
                                          <td>{$fila['total_puntos']}</td>
                                          <td>{$fecha_formateada}</td>
                                        </tr>";
                                  $posicion++;
                              }
                          } else {
                              echo "<tr><td colspan='4'>Todavía no hay puntuaciones globales registradas.</td></tr>";
                          }
                          $stmt_ranking_global->close();
                          $conn->close();
                          ?>
                      </tbody>
                  </table>
              </div>
          </section>

          <div class="ranking-actions">
              <a href="dashboard.php" class="boton-volver">Volver al Dashboard</a>
          </div>
      </div>
  </main>

</body>
</html>