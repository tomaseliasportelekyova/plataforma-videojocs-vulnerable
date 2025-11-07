<?php
// Arxiu: backend/api/set_rating.php
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
$valoracio = intval($data['rating'] ?? 0);

if ($joc_id === 0 || $valoracio < 1 || $valoracio > 5) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Dades invàlides (joc_id o valoració)']);
    exit;
}

// 3. Inserir o Actualitzar la valoració
// Gràcies al UNIQUE KEY (usuari_id, joc_id) que vam crear a la BBDD,
// podem fer un "INSERT ... ON DUPLICATE KEY UPDATE".
// Això insereix una nova fila, o si ja existeix, només actualitza la columna 'valoracio'.
$sql = "INSERT INTO usuari_valoracions (usuari_id, joc_id, valoracio) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE valoracio = ?";

$stmt = $conn->prepare($sql);
// Passem les 4 variables: (usuari_id, joc_id, valoracio, valoracio_per_update)
$stmt->bind_param("iiii", $usuari_id, $joc_id, $valoracio, $valoracio);

if ($stmt->execute()) {
    // Èxit
    echo json_encode(['success' => true, 'newRating' => $valoracio]);
    
    // Opcional: Recalcular la mitjana del joc a la taula 'jocs'
    // (Això es podria fer amb un TRIGGER a la BBDD per ser més eficient)
    $sql_avg = "UPDATE jocs SET valoracio = (
                    SELECT AVG(valoracio) FROM usuari_valoracions WHERE joc_id = ?
                ) WHERE id = ?";
    $stmt_avg = $conn->prepare($sql_avg);
    $stmt_avg->bind_param("ii", $joc_id, $joc_id);
    $stmt_avg->execute();
    $stmt_avg->close();

} else {
    http_response_code(500);
    echo json_encode(['error' => 'No s\'ha pogut guardar la valoració']);
}

$stmt->close();
$conn->close();
?>