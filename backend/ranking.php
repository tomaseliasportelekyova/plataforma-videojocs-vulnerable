<?php
session_start();
require "./funcions/db_mysqli.php";

$paginaActual = 'ranking';

$nickname = $_SESSION['nickname'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($nickname, $user_id);

// === LÒGICA DE FILTRES ===

// 1. Obtenir filtres de la URL
$filtre_joc_id = isset($_GET['joc_id']) ? intval($_GET['joc_id']) : 0;
$filtre_temps = $_GET['temps'] ?? 'all'; // 'all', 'mes', 'setmana', 'dia'

// 2. Obtenir TOTS els jocs per al panell de filtre
$llista_jocs = [];
$sql_jocs = "SELECT id, nom_joc FROM jocs WHERE actiu = 1 ORDER BY nom_joc ASC";
$result_jocs = $conn->query($sql_jocs);
if ($result_jocs) {
    while ($row = $result_jocs->fetch_assoc()) {
        $llista_jocs[] = $row;
    }
}

// 3. Construir la consulta SQL dinàmica
$ranking_data = []; // Array per desar els resultats
$sql_query = "";
$params = [];
$types = "";

if ($filtre_temps == 'all') {
    // --- LÒGICA PER "TOTS ELS TEMPS" (Basat en progres_usuari) ---
    $ranking_subtitle = "Tots els Temps";
    if ($filtre_joc_id > 0) {
        // Rànquing d'UN JOC (agafem puntuacio_maxima)
        $ranking_title = "Ranking per Joc"; // El títol es posarà més avall
        $sql_query = "SELECT u.id AS user_id, u.nickname, pr.puntuacio_maxima AS total_puntos, pr.ultima_partida
                      FROM progres_usuari pr
                      JOIN usuaris u ON pr.usuari_id = u.id
                      WHERE pr.joc_id = ? AND pr.puntuacio_maxima > 0
                      ORDER BY total_puntos DESC, pr.ultima_partida ASC LIMIT 50";
        $params[] = $filtre_joc_id;
        $types = "i";
    } else {
        // Rànquing GLOBAL (sumem les puntuacions màximes de tots els jocs)
        $ranking_title = "Ranking Global";
        $sql_query = "SELECT u.id AS user_id, u.nickname, SUM(pr.puntuacio_maxima) AS total_puntos, MAX(pr.ultima_partida) AS ultima_partida
                      FROM progres_usuari pr
                      JOIN usuaris u ON pr.usuari_id = u.id
                      WHERE pr.puntuacio_maxima > 0
                      GROUP BY u.id, u.nickname
                      ORDER BY total_puntos DESC LIMIT 50";
    }
} else {
    // --- LÒGICA PER TEMPS (Dia, Setmana, Mes) (Basat en partides) ---
    $ranking_title = ($filtre_joc_id > 0) ? "Ranking per Joc" : "Ranking Global";
    
    $sql_query = "SELECT u.id AS user_id, u.nickname, SUM(p.puntuacio_obtinguda) AS total_puntos, MAX(p.data_partida) AS ultima_partida
                  FROM partides p
                  JOIN usuaris u ON p.usuari_id = u.id";
    $where_clauses = [];

    // Afegim filtre de TEMPS
    if ($filtre_temps == 'dia') {
        $ranking_subtitle = "Avui";
        $where_clauses[] = "DATE(p.data_partida) = CURDATE()";
    } elseif ($filtre_temps == 'setmana') {
        $ranking_subtitle = "Aquesta Setmana";
        // YEARWEEK(..., 1) -> El mode 1 fa que la setmana comenci en Dilluns
        $where_clauses[] = "YEARWEEK(p.data_partida, 1) = YEARWEEK(NOW(), 1)";
    } elseif ($filtre_temps == 'mes') {
        $ranking_subtitle = "Aquest Mes";
        $where_clauses[] = "MONTH(p.data_partida) = MONTH(NOW()) AND YEAR(p.data_partida) = YEAR(NOW())";
    }

    // Afegim filtre de JOC
    if ($filtre_joc_id > 0) {
        $where_clauses[] = "p.joc_id = ?";
        $params[] = $filtre_joc_id;
        $types = "i";
    }

    if (!empty($where_clauses)) {
        $sql_query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql_query .= " GROUP BY u.id, u.nickname ORDER BY total_puntos DESC LIMIT 50";
}

// 4. Executar la consulta
$stmt = $conn->prepare($sql_query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result_ranking = $stmt->get_result();
} else {
    $result_ranking = false;
    error_log("Error preparing ranking statement: " . $conn->error);
}

// 5. Ajustar títol per al joc seleccionat
if ($filtre_joc_id > 0) {
    foreach ($llista_jocs as $joc) {
        if ($joc['id'] == $filtre_joc_id) {
            $ranking_title = "Ranking: " . htmlspecialchars($joc['nom_joc']);
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ranking - Shit Games</title>
  
  <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_ranking.css" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>

<main class="ranking-main-content">
  
    <aside class="ranking-filters">
        
        <div class="filter-group">
            <h3><i class="fas fa-gamepad"></i> Filtrar per Joc</h3>
            
            <div class="filter-search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="game-filter-search" class="filter-search-input" placeholder="Buscar joc...">
            </div>
            
            <ul class="filter-list" id="game-filter-list">
                <li>
                    <a href="ranking.php?temps=<?php echo $filtre_temps; ?>" 
                       class="<?php echo ($filtre_joc_id == 0) ? 'active' : ''; ?>">
                       Global (Tots els Jocs)
                    </a>
                </li>
                <?php foreach ($llista_jocs as $joc): ?>
                <li>
                    <a href="ranking.php?joc_id=<?php echo $joc['id']; ?>&temps=<?php echo $filtre_temps; ?>"
                       class="<?php echo ($filtre_joc_id == $joc['id']) ? 'active' : ''; ?>">
                       <?php echo htmlspecialchars($joc['nom_joc']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="filter-group">
            <h3><i class="fas fa-clock"></i> Filtrar per Temps</h3>
            <ul class="filter-list">
                <li>
                    <a href="ranking.php?joc_id=<?php echo $filtre_joc_id; ?>&temps=all"
                       class="<?php echo ($filtre_temps == 'all') ? 'active' : ''; ?>">
                       Tots els Temps
                    </a>
                </li>
                <li>
                    <a href="ranking.php?joc_id=<?php echo $filtre_joc_id; ?>&temps=setmana"
                       class="<?php echo ($filtre_temps == 'setmana') ? 'active' : ''; ?>">
                       Aquesta Setmana
                    </a>
                </li>
                <li>
                    <a href="ranking.php?joc_id=<?php echo $filtre_joc_id; ?>&temps=mes"
                       class="<?php echo ($filtre_temps == 'mes') ? 'active' : ''; ?>">
                       Aquest Mes
                    </a>
                </li>
                <li>
                    <a href="ranking.php?joc_id=<?php echo $filtre_joc_id; ?>&temps=dia"
                       class="<?php echo ($filtre_temps == 'dia') ? 'active' : ''; ?>">
                       Avui
                    </a>
                </li>
            </ul>
        </div>
        
    </aside>

    <section class="ranking-results">
        <div class="ranking-header">
            <h1><?php echo $ranking_title; ?></h1>
            <h2><?php echo $ranking_subtitle; ?></h2>
        </div>
        
        <div class="ranking-table-section">
          <table class="ranking-table">
            <thead>
              <?php if ($filtre_temps == 'all'): ?>
                  <tr><th>#</th><th>Jugador</th><th>Puntos Totales</th><th>Última Partida</th></tr>
              <?php else: ?>
                  <tr><th>#</th><th>Jugador</th><th>Puntos (<?php echo $ranking_subtitle; ?>)</th><th>Data</th></tr>
              <?php endif; ?>
            </thead>
            <tbody>
              <?php
              if ($result_ranking && $result_ranking->num_rows > 0):
                  $pos = 1;
                  while ($row = $result_ranking->fetch_assoc()) {
                      $is_current_user = ($is_logged_in && isset($row['user_id']) && $row['user_id'] == $user_id);
                      $row_class = $is_current_user ? 'current-user-rank' : '';
                      
                      $medalla = $pos == 1 ? '🥇' : ($pos == 2 ? '🥈' : ($pos == 3 ? '🥉' : $pos));
                      $fecha = $row['ultima_partida'] ?? 'N/A';
                      if ($fecha != 'N/A') $fecha = date("d/m/Y", strtotime($fecha));
                      
                      $puntos = $row['total_puntos'] ?? 0;
                      
                      echo "<tr class='{$row_class}'>
                              <td>{$medalla}</td>
                              <td class='player-cell'>{$row['nickname']}</td>
                              <td class='score-cell'>{$puntos}</td>
                              <td class='date-cell'>{$fecha}</td>
                            </tr>";
                      $pos++;
                  }
              else:
                  echo '<tr><td colspan="4" class="placeholder-text">No hi ha dades per a aquests filtres.</td></tr>';
              endif;
              
              if ($stmt) $stmt->close();
              if ($conn) $conn->close();
              ?>
            </tbody>
          </table>
        </div>
    </section>

</main>

<?php include './includes/_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('game-filter-search');
    const gameList = document.getElementById('game-filter-list');
    
    if (searchInput && gameList) {
        // Obtenim tots els 'li' excepte el primer ("Global")
        const gameItems = gameList.querySelectorAll('li:not(:first-child)');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            gameItems.forEach(item => {
                const gameName = item.textContent.toLowerCase();
                if (gameName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>

</body>
</html>