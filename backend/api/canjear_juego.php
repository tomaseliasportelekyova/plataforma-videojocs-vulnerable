<?php
// Arxiu: backend/api/canjear_juego.php
session_start();
require "../funcions/db_mysqli.php";
header('Content-Type: application/json');

// 1. Comprovar si l'usuari està autenticat
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Usuari no autenticat']);
    exit;
}
$usuari_id = $_SESSION['user_id'];

// 2. Llegir les dades JSON
$data = json_decode(file_get_contents('php://input'), true);
$joc_id = intval($data['joc_id'] ?? 0);

if ($joc_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No s\'ha rebut joc_id']);
    exit;
}

// 3. Comprovar el tipus de joc
$sql_check_game = "SELECT tipus FROM jocs WHERE id = ?";
$stmt_check = $conn->prepare($sql_check_game);
$stmt_check->bind_param("i", $joc_id);
$stmt_check->execute();
$result_game = $stmt_check->get_result();

if ($result_game->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Joc no trobat']);
    $stmt_check->close();
    $conn->close();
    exit;
}

$joc_data = $result_game->fetch_assoc();
$stmt_check->close();

// 4. Lògica de "Canje"
if ($joc_data['tipus'] == 'Free') {
    // És 'Free', l'afegim a la biblioteca
    $sql_insert = "INSERT INTO usuari_jocs (usuari_id, joc_id) VALUES (?, ?) 
                   ON DUPLICATE KEY UPDATE joc_id = joc_id"; // No fa res si ja existeix
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ii", $usuari_id, $joc_id);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Joc canviat correctament!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar a la biblioteca']);
    }
    $stmt_insert->close();

} else if ($joc_data['tipus'] == 'Premium') {
    // És 'Premium', simulem que no es pot
    http_response_code(402); // Payment Required
    echo json_encode(['error' => 'Aquest és un joc Premium i requereix pagament. (Simulació)']);
}

$conn->close();
?>