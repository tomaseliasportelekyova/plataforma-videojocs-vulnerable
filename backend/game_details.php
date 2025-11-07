<?php
session_start();
require "./funcions/db_mysqli.php";

$paginaActual = ''; 
$joc_id = $_GET['joc_id'] ?? 0;
$joc_id = intval($joc_id);

$nickname = $_SESSION['nickname'] ?? null; 
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 0;

$joc_data = null;
$progres_data = null;
$joc_trobat = false;
$te_progres = false;
$user_owns_game = false; 
$nivell_a_continuar = 1;
$screenshots = []; 
$user_wishlist = false; 
$user_rating = 0; 

// 1. Buscar les dades del joc
if ($joc_id > 0) {
    $sql_joc = "SELECT nom_joc, descripcio, cover_image_url, 
                       categoria, temps_aprox_min, num_jugadors, 
                       screenshots_json, valoracio, tipus 
                FROM jocs WHERE id = ? AND actiu = 1";
    $stmt_joc = $conn->prepare($sql_joc);
    $stmt_joc->bind_param("i", $joc_id);
    $stmt_joc->execute();
    $result_joc = $stmt_joc->get_result();
    
    if ($result_joc->num_rows > 0) {
        $joc_data = $result_joc->fetch_assoc();
        $joc_trobat = true;
        if (!empty($joc_data['screenshots_json'])) {
            $screenshots = json_decode($joc_data['screenshots_json'], true);
        }
    }
    $stmt_joc->close();
}

// 2. Si el joc existeix I l'usuari està loguejat...
if ($joc_trobat && $is_logged_in) {
    
    // a) Mirem si "posseeix" el joc
    $sql_own = "SELECT id FROM usuari_jocs WHERE usuari_id = ? AND joc_id = ?";
    $stmt_own = $conn->prepare($sql_own);
    $stmt_own->bind_param("ii", $user_id, $joc_id);
    $stmt_own->execute();
    $user_owns_game = ($stmt_own->get_result()->num_rows > 0);
    $stmt_own->close();

    // b) Si el posseeix, mirem si té progrés
    if ($user_owns_game) {
        // === CANVI: Demanem les noves dades ===
        $sql_progres = "SELECT nivell_actual, puntuacio_maxima, dades_guardades_json, durada_total_segons 
                        FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?";
        $stmt_progres = $conn->prepare($sql_progres);
        $stmt_progres->bind_param("ii", $user_id, $joc_id);
        $stmt_progres->execute();
        $result_progres = $stmt_progres->get_result();
        if ($result_progres->num_rows > 0) {
            $progres_data = $result_progres->fetch_assoc();
            $te_progres = true;
            $nivell_a_continuar = $progres_data['nivell_actual'] ?? 1;
        }
        $stmt_progres->close();
    }

    // c) Mirem Wishlist
    $sql_wish = "SELECT id FROM wishlist WHERE usuari_id = ? AND joc_id = ?";
    $stmt_wish = $conn->prepare($sql_wish);
    $stmt_wish->bind_param("ii", $user_id, $joc_id);
    $stmt_wish->execute();
    $user_wishlist = ($stmt_wish->get_result()->num_rows > 0);
    $stmt_wish->close();
    
    // d) Mirem Valoració
    $sql_rating = "SELECT valoracio FROM usuari_valoracions WHERE usuari_id = ? AND joc_id = ?";
    $stmt_rating = $conn->prepare($sql_rating);
    $stmt_rating->bind_param("ii", $user_id, $joc_id);
    $stmt_rating->execute();
    $result_rating = $stmt_rating->get_result();
    if($rating_data = $result_rating->fetch_assoc()) {
        $user_rating = $rating_data['valoracio'];
    }
    $stmt_rating->close();
}

$conn->close();

if (!$joc_trobat) {
    header("Location: dashboard.php"); // Canviat a dashboard.php
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($joc_data['nom_joc']); ?> - Shit Games</title>
    
    <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
    <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
    <link rel="stylesheet" href="../frontend/assets/css/style_game_details.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
    
    <?php include './includes/_header.php'; ?>

    <main class="details-main-content">
        
        <div class="game-details-container"> 

            <section class="game-hero-section">
                <img class="hero-background-image" src="<?php echo htmlspecialchars($joc_data['cover_image_url'] ?? '../frontend/imatges/covers/default_cover.png'); ?>" alt="Fondo de <?php echo htmlspecialchars($joc_data['nom_joc']); ?>">
                <div class="hero-gradient"></div>
                
                <div class="hero-content-overlay">
                    <h1><?php echo htmlspecialchars($joc_data['nom_joc']); ?></h1>
                    
                    <div class="hero-actions" data-joc-id="<?php echo $joc_id; ?>">
                        <?php if ($is_logged_in): ?>
                        
                            <?php if ($user_owns_game): ?>
                                <?php if ($te_progres): ?>
                                    <?php
                                    // Preparem les dades per carregar
                                    $start_score = $progres_data['puntuacio_maxima'] ?? 0;
                                    $start_time = $progres_data['durada_total_segons'] ?? 0;
                                    $start_kills = 0;
                                    if (!empty($progres_data['dades_guardades_json'])) {
                                        $progress_json = json_decode($progres_data['dades_guardades_json'], true);
                                        $start_kills = $progress_json['kills'] ?? 0;
                                    }
                                    // Construïm la URL amb les dades
                                    $continue_url = sprintf(
                                        "juego.php?joc_id=%d&nivell=%d&score=%d&kills=%d&time=%d",
                                        $joc_id,
                                        $nivell_a_continuar,
                                        $start_score,
                                        $start_kills,
                                        $start_time
                                    );
                                    ?>
                                    <a href="<?php echo $continue_url; ?>" class="play-button">
                                        <i class="fas fa-play"></i> Continuar (Nivell <?php echo $nivell_a_continuar; ?>)
                                    </a>
                                    <a href="juego.php?joc_id=<?php echo $joc_id; ?>&nivell=1" class="play-button secondary">
                                        <i class="fas fa-redo"></i> Comenzar de Nuevo
                                    </a>
                                <?php else: ?>
                                    <a href="juego.php?joc_id=<?php echo $joc_id; ?>&nivell=1" class="play-button">
                                        <i class="fas fa-play"></i> Jugar
                                    </a>
                                <?php endif; ?>

                            <?php else: ?>
                                <?php if ($joc_data['tipus'] == 'Free'): ?>
                                    <button id="redeem-button" class="play-button redeem">
                                        <i class="fas fa-gift"></i> Canjear (Gratis)
                                    </button>
                                <?php else: // 'Premium' ?>
                                    <button id="premium-button" class="play-button premium">
                                        <i class="fas fa-gem"></i> Obtener Premium
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>

                        <?php else: ?>
                            <a href="login.php" class="play-button">
                                <i class="fas fa-sign-in-alt"></i> Inicia sesión para Jugar
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="game-meta-info">
                        <div class="meta-item">
                            Categoría
                            <strong><?php echo htmlspecialchars($joc_data['categoria'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="meta-item">
                            Jugadores
                            <strong><?php echo htmlspecialchars($joc_data['num_jugadors'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="meta-item">
                            Tiempo aprox.
                            <strong><?php echo ($joc_data['temps_aprox_min'] > 0) ? $joc_data['temps_aprox_min'] . ' min' : 'N/A'; ?></strong>
                        </div>
                    </div>
                    <div class="hero-secondary-actions">
                        <button id="wishlist-button" class="wishlist-button <?php echo $user_wishlist ? 'in-wishlist' : ''; ?>" <?php echo !$is_logged_in ? 'disabled' : ''; ?>>
                            <i class="fas <?php echo $user_wishlist ? 'fa-check' : 'fa-heart'; ?>"></i> 
                            <?php echo $user_wishlist ? 'A la teva llista' : 'Añadir a Wishlist'; ?>
                        </button>
                        <button id="rating-button-modal" class="rating-button" <?php echo !$is_logged_in ? 'disabled' : ''; ?>>
                            <i class="fas fa-star"></i> 
                            <?php echo ($user_rating > 0) ? 'Has valorat: ' . $user_rating . '★' : 'Valorar'; ?>
                        </button>
                    </div>
                </div>
            </section>
            
            <?php if (!empty($screenshots)): ?>
            <section class="game-details-section">
                <h2>Capturas de pantalla</h2>
                <div class="screenshots-gallery">
                    <?php foreach ($screenshots as $img_path): ?>
                        <div class="screenshot-item">
                            <img src="../frontend/<?php echo htmlspecialchars($img_path); ?>" alt="Screenshot de <?php echo htmlspecialchars($joc_data['nom_joc']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            <section class="game-details-section">
                <h2>Descripción</h2>
                <p><?php echo nl2br(htmlspecialchars($joc_data['descripcio'] ?? 'Este juego aún no tiene descripción.')); ?></p>
            </section>
            
        </div>
    </main>

    <?php include './includes/_footer.php'; ?>

    <div class="rating-modal-backdrop" id="rating-modal-backdrop">
        <div class="rating-modal-content">
            <h3>Valora <?php echo htmlspecialchars($joc_data['nom_joc']); ?></h3>
            <div class="rating-stars" data-joc-id="<?php echo $joc_id; ?>">
                <span class="star <?php echo ($user_rating >= 1) ? 'selected' : ''; ?>" data-value="1"><i class="fas fa-star"></i></span>
                <span class="star <?php echo ($user_rating >= 2) ? 'selected' : ''; ?>" data-value="2"><i class="fas fa-star"></i></span>
                <span class="star <?php echo ($user_rating >= 3) ? 'selected' : ''; ?>" data-value="3"><i class="fas fa-star"></i></span>
                <span class="star <?php echo ($user_rating >= 4) ? 'selected' : ''; ?>" data-value="4"><i class="fas fa-star"></i></span>
                <span class="star <?php echo ($user_rating >= 5) ? 'selected' : ''; ?>" data-value="5"><i class="fas fa-star"></i></span>
            </div>
            <button id="modal-close-button" class="play-button secondary" style="padding: 10px 20px;">Tancar</button>
        </div>
    </div>

    <script src="../frontend/assets/js/game_details.js"></script>
</body>
</html>