<?php
// Arxiu: backend/api/toggle_wishlist.php
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

// 2. Llegir les dades JSON enviades des del JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$joc_id = intval($data['joc_id'] ?? 0);

if ($joc_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No s\'ha rebut joc_id']);
    exit;
}

$conn->begin_transaction();
try {
    // 3. Comprovar si ja existeix a la wishlist
    $sql_check = "SELECT id FROM wishlist WHERE usuari_id = ? AND joc_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $usuari_id, $joc_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Si existeix, l'eliminem (toggle OFF)
        $sql_delete = "DELETE FROM wishlist WHERE usuari_id = ? AND joc_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $usuari_id, $joc_id);
        $stmt_delete->execute();
        $inWishlist = false;
        if (isset($stmt_delete)) $stmt_delete->close();

    } else {
        // Si NO existeix, l'afegim (toggle ON)
        $sql_insert = "INSERT INTO wishlist (usuari_id, joc_id) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $usuari_id, $joc_id);
        $stmt_insert->execute();
        $inWishlist = true;
        if (isset($stmt_insert)) $stmt_insert->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'inWishlist' => $inWishlist]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualitzar la wishlist', 'detalle' => $e->getMessage()]);
}

if (isset($stmt_check)) $stmt_check->close();
$conn->close();
?>