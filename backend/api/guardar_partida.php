<?php
// Arxiu: backend/api/guardar_partida.php

session_start();
require "../funcions/db_mysqli.php"; 

// 1. Comprovar si l'usuari està autenticat
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Usuari no autenticat']);
    exit;
}
$usuari_id = $_SESSION['user_id'];

// 2. Llegir les dades JSON enviades des del joc
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No s\'han rebut dades']);
    exit;
}

// 3. Assignar variables (dades COMUNES)
$joc_id = intval($data['joc_id'] ?? 0);
$nivell_jugat = intval($data['nivell_jugat'] ?? 1);
$puntuacio = intval($data['puntuacio_obtinguda'] ?? 0); // Punts totals acumulats
$durada = intval($data['durada_segons'] ?? 0); // Durada NOMÉS d'aquesta sessió/nivell
$superat = boolval($data['nivell_superat'] ?? false);

// Assignar dades EXTRA (convertim a JSON per guardar)
$dades_extra_json = null;
if (isset($data['dades_extra']) && is_array($data['dades_extra'])) {
    $dades_extra_json = json_encode($data['dades_extra']); // Kills totals
}

if ($joc_id === 0) {
     http_response_code(400);
     echo json_encode(['error' => 'Joc ID invàlid']);
     exit;
}

// 4. Lògica de BBDD (transacció per seguretat)
$conn->begin_transaction();
try {

    // 4.1. Inserir a la taula 'partides' (Això SEMPRE es fa, és un registre/log)
    $sql_partida = "INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, data_partida, durada_segons, dades_partida_json)
                    VALUES (?, ?, ?, ?, NOW(), ?, ?)";
    $stmt_partida = $conn->prepare($sql_partida);
    $stmt_partida->bind_param("iiiiis", $usuari_id, $joc_id, $nivell_jugat, $puntuacio, $durada, $dades_extra_json);
    $stmt_partida->execute();

    
    // === CANVI CLAU: NOMÉS ACTUALITZEM EL PROGRÉS SI HA SUPERAT EL NIVELL ===
    if ($superat) {
        
        // 4.2. Actualitzar/Inserir a 'progres_usuari'
        $sql_select = "SELECT * FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("ii", $usuari_id, $joc_id);
        $stmt_select->execute();
        $result_progres = $stmt_select->get_result();

        if ($result_progres->num_rows === 0) {
            // No existeix progrés, el creem (INSERT)
            $nivell_actual = $nivell_jugat + 1; // Ja ha superat el nivell, passa al següent
            $partides_jugades = 1;
            
            // Afegim 'durada_total_segons'
            $sql_insert_progres = "INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, durada_total_segons, ultima_partida, dades_guardades_json)
                                   VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $stmt_insert_progres = $conn->prepare($sql_insert_progres);
            // Tipus: i,i,i,i,i,i,s
            $stmt_insert_progres->bind_param("iiiiiis", $usuari_id, $joc_id, $nivell_actual, $puntuacio, $partides_jugades, $durada, $dades_extra_json);
            $stmt_insert_progres->execute();
        
        } else {
            // Ja existeix progrés, l'actualitzem (UPDATE)
            $progres_actual = $result_progres->fetch_assoc();
            
            // Reemplacem els valors pels totals nous
            $nova_puntuacio_max = $puntuacio; 
            $noves_partides = $progres_actual['partides_jugades'] + 1;
            $nivell_actual = $nivell_jugat + 1;

            // Sumem la durada d'AQUEST nivell a la durada TOTAL
            $nova_durada_total = $progres_actual['durada_total_segons'] + $durada;

            $sql_update_progres = "UPDATE progres_usuari SET 
                                    nivell_actual = ?, 
                                    puntuacio_maxima = ?, 
                                    partides_jugades = ?, 
                                    durada_total_segons = ?,
                                    ultima_partida = NOW(),
                                    dades_guardades_json = ? 
                                   WHERE usuari_id = ? AND joc_id = ?";
            $stmt_update_progres = $conn->prepare($sql_update_progres);
            // Tipus: i,i,i,i,s,i,i
            $stmt_update_progres->bind_param("iiiisii", $nivell_actual, $nova_puntuacio_max, $noves_partides, $nova_durada_total, $dades_extra_json, $usuari_id, $joc_id);
            $stmt_update_progres->execute();
        }
    } // <-- FI DEL 'if ($superat)'

    // 5. Tot ha anat bé, fem 'commit'
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar a la BBDD', 'detalle' => $e->getMessage()]);
}

// --- Tancament segur ---
if (isset($stmt_partida)) $stmt_partida->close();
if (isset($stmt_select)) $stmt_select->close();
if (isset($stmt_insert_progres)) $stmt_insert_progres->close();
if (isset($stmt_update_progres)) $stmt_update_progres->close();
$conn->close();
?>