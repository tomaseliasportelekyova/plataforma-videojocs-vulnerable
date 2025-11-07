<?php
// Arxiu: backend/juego.php
session_start();
$nickname = $_SESSION['nickname'] ?? null;
$paginaActual = ''; 
$joc_id = $_GET['joc_id']; 
require "./funcions/db_mysqli.php";

$sql = "SELECT nom_joc FROM jocs WHERE id = " . $joc_id; // (Vulnerable)
$result = $conn->query($sql);
$nom_joc = 'Joc no trobat';
if ($result && $result->num_rows > 0) {
    $joc_data = $result->fetch_assoc();
    $nom_joc = $joc_data['nom_joc'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($nom_joc); ?> - Shit Games</title>
    
    <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
    <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
    <link rel="stylesheet" href="../frontend/assets/css/style_game_common.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php
        // Càrrega del CSS específic de CADA joc
        switch ($joc_id) {
            case 1: echo '<link rel="stylesheet" href="../frontend/jocs/joc_naus/style.css" />'; break;
            case 2: echo '<link rel="stylesheet" href="../frontend/jocs/joc_ping_pong/style.css" />'; break; 
            case 23: echo '<link rel="stylesheet" href="../frontend/jocs/joc_tower_defense/style.css" />'; break;
            // === AFEGIT SNAKE ===
            case 24: echo '<link rel="stylesheet" href="../frontend/jocs/joc_snake_tron/style.css" />'; break;
        }
    ?>
</head>
<body class="dark-theme page-joc">
    
    <?php include './includes/_header.php'; ?>

    <main class="game-main-container">
        <h1 class="game-title"><?php echo htmlspecialchars($nom_joc); ?></h1>
        
        <div class="game-area-wrapper">
            
            <?php
            // Contenidor que centra el tauler + botiga
            echo '<div class="game-screen-container">';
            
            // Càrrega del HTML específic (el tauler)
            switch ($joc_id) {
                case 1:
                    include "../frontend/jocs/joc_naus/game_content.html";
                    break;
                case 2:
                    include "../frontend/jocs/joc_ping_pong/game_content.html";
                    break;
                case 23:
                    include "../frontend/jocs/joc_tower_defense/game_content.html";
                    break;
                // === AFEGIT SNAKE ===
                case 24:
                    include "../frontend/jocs/joc_snake_tron/game_content.html";
                    break;
                default:
                    echo '<h2 style="color: white; text-align: center;">Joc no trobat.</h2>';
                    break;
            }
            ?>
            
            <div id="instructions-overlay" class="hidden">
                <h2>Instruccions</h2>
                <p class="instructions-text">
                    <?php
                    switch ($joc_id) {
                        case 1: echo "Mou la nau amb les fletxes (← →) i dispara amb la tecla Espai."; break;
                        case 2: echo "Mou la teva pala (Dreta) amb les fletxes '↑' (amunt) i '↓' (avall). Guanya 5 punts per passar de nivell."; break;
                        case 23: echo "Compra torretes per defensar el Nucli. Fes clic al tauler per col·locar-les (excepte al camí). Fes clic en una torreta per millorar-la o vendre-la."; break;
                        // === AFEGIT SNAKE ===
                        case 24: echo "Mou la serp amb les fletxes (↑ ↓ ← →) o (W A S D). Aconsegueix el menjar sense xocar."; break;
                        default: echo "Comença a jugar!";
                    }
                    ?>
                </p>
                <div class="countdown-container">
                    El joc comença en <span id="countdown-timer">10</span>s
                </div>
                <button id="skip-instructions">Omitir</button>
            </div>
            
            <div id="pause-overlay" class="hidden">
                <h2>Pausa</h2>
                <div class="pause-menu-buttons">
                    <button id="pause-continue-btn" class="pause-button primary">
                        <i class="fas fa-play"></i> Continuar
                    </button>
                    <button id="pause-restart-btn" class="pause-button secondary">
                        <i class="fas fa-redo"></i> Reiniciar Nivel
                    </button>
                    <button id="pause-sound-btn" class="pause-button secondary">
                        <i class="fas fa-volume-up"></i> Sound On
                    </button>
                    <button id="pause-exit-btn" class="pause-button danger">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </button>
                </div>
            </div>
            <?php
            echo '</div>'; // Tanquem 'game-screen-container'
            ?>
            
            
            <?php
            switch ($joc_id) {
                // Cas 1: Naus vs Ovnis
                case 1:
                    echo '
                    <div id="infoPartida">
                        <h2><i class="fas fa-info-circle"></i> Partida</h2>
                        <div class="info-item">
                            <span>Nivell</span>
                            <strong id="info-nivell">1</strong>
                        </div>
                        <div class="info-item">
                            <span>Punts</span>
                            <strong id="info-punts">0</strong>
                        </div>
                         <div class="info-item">
                            <span>Kills</span>
                            <strong id="info-kills">0</strong>
                        </div>
                        <div class="info-item">
                            <span>Vides</span>
                            <strong id="info-vides">--</strong>
                        </div>
                         <div class="info-item">
                            <span>Temps</span>
                            <strong id="info-temps">00:00</strong>
                        </div>
                    </div>';
                    break;

                // Cas 2: Panell pel PONG
                case 2:
                    echo '
                    <div id="infoPartida">
                        <h2><i class="fas fa-info-circle"></i> Partida</h2>
                        <div class="info-item">
                            <span>Nivell</span>
                            <strong id="info-nivell">1 / 3</strong>
                        </div>
                        <div class="info-item">
                            <span>Puntuació Total</span>
                            <strong id="info-punts">0</strong>
                        </div>
                         <div class="info-item">
                            <span>Marcador</span>
                            <strong id="info-marcador">0 - 0</strong>
                        </div>
                        <div class="info-item">
                            <span>Vides</span>
                            <strong id="info-vides">5</strong>
                        </div>
                         <div class="info-item">
                            <span>Temps</span>
                            <strong id="info-temps">00:00</strong>
                        </div>
                    </div>';
                    break;
                
                // Cas 23: Pixel Sentinel (Tower Defense)
                case 23:
                    echo '
                    <div id="infoPartida">
                        <h2><i class="fas fa-info-circle"></i> Partida</h2>
                        <div class="info-item">
                            <span>Onada</span>
                            <strong id="info-onada">0 / 0</strong>
                        </div>
                        <div class="info-item">
                            <span>Crèdits</span>
                            <strong id="info-credits">0</strong>
                        </div>
                        <div class="info-item">
                            <span>Puntuació</span>
                            <strong id="info-puntuacio">0</strong>
                        </div>
                         <div class="info-item">
                            <span>Destruïts</span>
                            <strong id="info-kills">0</strong>
                        </div>
                        <div class="info-item">
                            <span>Integritat Nucli</span>
                            <strong id="info-vides">0</strong>
                        </div>
                         <div class="info-item">
                            <span>Següent Onada</span>
                            <strong id="info-temps">00:00</strong>
                        </div>
                    </div>';
                    break;

                // === AFEGIT SNAKE (ID 24) ===
                // Utilitza el mateix panell que Naus (ID 1)
                case 24:
                    echo '
                    <div id="infoPartida">
                        <h2><i class="fas fa-info-circle"></i> Partida</h2>
                        <div class="info-item">
                            <span>Nivell</span>
                            <strong id="info-nivell">1</strong>
                        </div>
                        <div class="info-item">
                            <span>Punts</span>
                            <strong id="info-punts">0</strong>
                        </div>
                         <div class="info-item">
                            <span>Pomes</span>
                            <strong id="info-kills">0</strong>
                        </div>
                        <div class="info-item">
                            <span>Vides</span>
                            <strong id="info-vides">1</strong>
                        </div>
                         <div class="info-item">
                            <span>Temps</span>
                            <strong id="info-temps">00:00</strong>
                        </div>
                    </div>';
                    break;
            }
            ?>
            
        </div> </main>
  
    <?php include './includes/_footer.php'; ?>

    <?php
    // --- CÀRREGA DELS SCRIPTS ---
    echo '<script src="../frontend/assets/js/game_flow.js"></script>';
    echo '<script src="../frontend/assets/js/game_pause_manager.js"></script>';
    
    switch ($joc_id) {
        case 1:
            echo '<script src="../frontend/jocs/joc_naus/classes.js"></script>';
            echo '<script src="../frontend/jocs/joc_naus/main.js"></script>';
            break;
        case 2:
            echo '<script src="../frontend/jocs/joc_ping_pong/main.js"></script>';
            break;
        case 23:
            echo '<script src="../frontend/jocs/joc_tower_defense/classes.js"></script>';
            echo '<script src="../frontend/jocs/joc_tower_defense/main.js"></script>';
            break;
        // === AFEGIT SNAKE ===
        case 24:
            echo '<script src="../frontend/jocs/joc_snake_tron/main.js"></script>';
            break;
    }
    ?>

</body>
</html>