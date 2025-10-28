<?php
// Arxiu: backend/api/get_nivell.php

// Incluïm la connexió a la BBDD
require "../funcions/db_mysqli.php";

// 1. Recollir paràmetres de la URL (?joc_id=X&nivell=Y)
// Usem 'intval' per seguretat, assegurant que són números
$joc_id = intval($_GET['joc_id'] ?? 0);
$nivell = intval($_GET['nivell'] ?? 0);

if ($joc_id === 0 || $nivell === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Falten paràmetres joc_id o nivell']);
    exit;
}

// 2. Consulta PREPARADA (Clau per la seguretat)
$sql = "SELECT configuracio_json FROM nivells_joc WHERE joc_id = ? AND nivell = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $joc_id, $nivell); // "ii" = dos integers

$stmt->execute();
$resultado = $stmt->get_result();
$nivell_data = $resultado->fetch_assoc();

// 3. Retornar el JSON
if ($nivell_data) {
    // Aquesta és la màgia: diem al navegador que això és JSON
    header('Content-Type: application/json');
    
    // Imprimim directament el text JSON que tenim a la BBDD
    echo $nivell_data['configuracio_json']; 
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Nivell no trobat']);
}

$stmt->close();
$conn->close();
?>