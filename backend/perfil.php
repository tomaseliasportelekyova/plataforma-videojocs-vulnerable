<?php
session_start();
// ... (tot el PHP de dalt per obtenir dades de l'usuari és el mateix) ...
if (!isset($_SESSION['email'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'];
require "./funcions/db_mysqli.php";
$error_message = '';
$success_message = '';
$default_photo_path = '../frontend/imatges/users/default_user.png';
$current_photo = $default_photo_path; 
$current_nickname = $nickname; 
$current_email = $_SESSION['email'];
$current_nom = '';
$current_cognom = '';
$current_data_registre = 'N/A';
$global_rank = 'N/A';
$played_games = [];
$wishlist_games = []; // <-- Variable que omplirem

// --- Cookie Nickname (sense canvis) ---
$cookie_name = "nickname_changes_count_" . $user_id;
$nickname_changes_count = isset($_COOKIE[$cookie_name]) ? intval($_COOKIE[$cookie_name]) : 0;
$max_nickname_changes = 3;
$can_change_nickname = ($nickname_changes_count < $max_nickname_changes);

// --- Recuperar Dades de la BBDD (sense canvis) ---
$sql_select_user = "SELECT nom, cognom, data_registre, photo FROM usuaris WHERE id = ?";
$stmt_select_user = $conn->prepare($sql_select_user);
if ($stmt_select_user) {
    $stmt_select_user->bind_param("i", $user_id);
    $stmt_select_user->execute();
    $resultado_user = $stmt_select_user->get_result();
    $user_db_data = $resultado_user->fetch_assoc();
    $stmt_select_user->close();
    if ($user_db_data) {
        $current_nom = $user_db_data['nom'];
        $current_cognom = $user_db_data['cognom'];
        $current_data_registre = $user_db_data['data_registre'] ? date("d/m/Y", strtotime($user_db_data['data_registre'])) : 'N/A';
        $photo_from_session = $_SESSION['user_photo'] ?? $default_photo_path;
        $current_photo = $photo_from_session;
    } else {
        session_destroy();
        header("Location: login.php?error=user_not_found_in_db");
        exit();
    }
} else {
    $error_message = "Error recuperant dades d'usuari: " . $conn->error;
}

// --- Calcular Ranking Global (sense canvis) ---
$sql_rank = "SELECT user_rank FROM (SELECT u.id as user_id, RANK() OVER (ORDER BY SUM(COALESCE(p.puntuacio_obtinguda, 0)) DESC) as user_rank FROM usuaris u LEFT JOIN partides p ON u.id = p.usuari_id GROUP BY u.id) as ranked_users WHERE user_id = ?";
$stmt_rank = $conn->prepare($sql_rank);
if ($stmt_rank) {
    $stmt_rank->bind_param("i", $user_id); $stmt_rank->execute(); $result_rank = $stmt_rank->get_result();
    if ($rank_data = $result_rank->fetch_assoc()) { $global_rank = '#' . $rank_data['user_rank']; } else { $global_rank = 'Sense Rank'; }
    $stmt_rank->close();
} else { $global_rank = 'N/A'; }


// --- Recuperar Juegos Jugados (sense canvis) ---
$sql_played = "SELECT j.id as joc_id, j.nom_joc, j.cover_image_url, COALESCE(SUM(p.durada_segons), 0) as total_temps_jugat_segons, MAX(p.data_partida) as ultima_vegada_jugat, COALESCE((SELECT SUM(p_user.puntuacio_obtinguda) FROM partides p_user WHERE p_user.joc_id = j.id AND p_user.usuari_id = ?), 0) as current_user_total_score, (SELECT COUNT(*) + 1 FROM (SELECT usuari_id, SUM(puntuacio_obtinguda) as total_score FROM partides WHERE joc_id = j.id GROUP BY usuari_id) as game_scores WHERE game_scores.total_score > COALESCE((SELECT SUM(p_user2.puntuacio_obtinguda) FROM partides p_user2 WHERE p_user2.joc_id = j.id AND p_user2.usuari_id = ?), 0) ) as rank_en_joc FROM jocs j LEFT JOIN partides p ON j.id = p.joc_id AND p.usuari_id = ? WHERE p.usuari_id = ? GROUP BY j.id, j.nom_joc, j.cover_image_url ORDER BY ultima_vegada_jugat DESC, j.nom_joc ASC";
$stmt_played = $conn->prepare($sql_played);
if ($stmt_played) {
    $stmt_played->bind_param("iiii", $user_id, $user_id, $user_id, $user_id); $stmt_played->execute(); $result_played = $stmt_played->get_result();
    while ($game = $result_played->fetch_assoc()) {
        $total_segons = $game['total_temps_jugat_segons']; $hores = floor($total_segons / 3600); $minuts = floor(($total_segons % 3600) / 60);
        $game['temps_format'] = sprintf('%dh %dm', $hores, $minuts);
        $game['ultima_vegada_format'] = $game['ultima_vegada_jugat'] ? date("d/m/y", strtotime($game['ultima_vegada_jugat'])) : 'Mai';
        $cover_path = $game['cover_image_url'] ?? '';
        $game['cover_image_url'] = (!empty($cover_path)) ? $cover_path : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($game['nom_joc']);
        $played_games[] = $game;
    }
    $stmt_played->close();
} else { $error_message .= " Error recuperant jocs jugats: " . $conn->error; }

// --- Recuperar Jocs de la Wishlist (sense canvis) ---
$sql_wishlist = "SELECT j.id, j.nom_joc, j.cover_image_url, j.valoracio, j.tipus
                 FROM jocs j
                 JOIN wishlist w ON j.id = w.joc_id
                 WHERE w.usuari_id = ? AND j.actiu = 1
                 ORDER BY w.data_added DESC";
$stmt_wishlist = $conn->prepare($sql_wishlist);
if ($stmt_wishlist) {
    $stmt_wishlist->bind_param("i", $user_id);
    $stmt_wishlist->execute();
    $result_wishlist = $stmt_wishlist->get_result();
    while ($game = $result_wishlist->fetch_assoc()) {
        $game['cover_image_url'] = (!empty($game['cover_image_url'])) 
               ? $game['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($game['nom_joc']);
        $wishlist_games[] = $game;
    }
    $stmt_wishlist->close();
} else {
    $error_message .= " Error recuperant la wishlist: " . $conn->error;
}
// ==========================================


// --- Lógica de Actualización (POST) (sense canvis) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // ... (lògica d'actualització de perfil és la mateixa) ...
    $new_nickname = trim($_POST['nickname'] ?? $current_nickname);
    $new_email = trim($_POST['email'] ?? $current_email);
    $new_password = $_POST['password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;
    $new_nom = trim($_POST['nom'] ?? $current_nom);
    $new_cognom = trim($_POST['cognom'] ?? $current_cognom);
    $new_photo_path = $current_photo; 
    $nickname_changed = ($new_nickname !== $current_nickname);
    $photo_updated = false;
    if ($nickname_changed && !$can_change_nickname) {
        $error_message = "Has alcanzado el límite de cambios de nickname.";
        $new_nickname = $current_nickname; $nickname_changed = false;
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = "Las contraseñas nuevas no coinciden.";
    } else if (empty($new_nickname) || empty($new_email) || empty($new_nom) || empty($new_cognom)) {
        $error_message = "Nickname, Email, Nombre y Apellido no pueden estar vacíos.";
    } else {
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK && $_FILES['nueva_foto']['size'] > 0) {
            $upload_dir_relative = '../frontend/imatges/users/'; 
            $upload_dir_server = realpath(__DIR__ . '/../frontend/imatges/users/');
            if (!$upload_dir_server) { $upload_dir_server = __DIR__ . '/../frontend/imatges/users/'; }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; $max_size = 5 * 1024 * 1024;
            if (in_array($_FILES['nueva_foto']['type'], $allowed_types) && $_FILES['nueva_foto']['size'] <= $max_size) {
                $file_extension = strtolower(pathinfo($_FILES['nueva_foto']['name'], PATHINFO_EXTENSION));
                $filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
                $target_relative_path = $upload_dir_relative . $filename;
                $target_server_path_final = rtrim($upload_dir_server, '/') . '/' . $filename;
                if (!file_exists($upload_dir_server)) {
                   if (!mkdir($upload_dir_server, 0775, true)) { $error_message .= " Error al crear directori."; }
                }
                if (empty($error_message) && move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_server_path_final)) {
                    $new_photo_path = $target_relative_path;
                    $photo_updated = true;
                } else if (empty($error_message)){ $error_message .= " Error al mover la foto."; }
            } else { $error_message .= " Format/mida imatge invàlid."; }
        } elseif (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
             $error_message .= " Error pujant foto (codi: ".$_FILES['nueva_foto']['error'].").";
        }
        if(empty($error_message)) {
            $fields_to_update = []; $types = ""; $params = [];
            if ($new_nickname !== $current_nickname) { $fields_to_update[] = "nickname = ?"; $types .= "s"; $params[] = $new_nickname; }
            if ($new_email !== $current_email) { $fields_to_update[] = "email = ?"; $types .= "s"; $params[] = $new_email; }
            if ($new_nom !== $current_nom) { $fields_to_update[] = "nom = ?"; $types .= "s"; $params[] = $new_nom; }
            if ($new_cognom !== $current_cognom) { $fields_to_update[] = "cognom = ?"; $types .= "s"; $params[] = $new_cognom; }
            if ($photo_updated) { $fields_to_update[] = "photo = ?"; $types .= "s"; $params[] = $new_photo_path; }
            if (!empty($new_password)) {
                 $password_hash = $new_password; 
                 $fields_to_update[] = "password_hash = ?"; $types .= "s"; $params[] = $password_hash;
            }
            if(!empty($fields_to_update)) {
                $types .= "i"; $params[] = $user_id;
                $sql_update = "UPDATE usuaris SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param($types, ...$params);
                    if ($stmt_update->execute()) {
                        $success_message = "Perfil actualizado correctamente.";
                        $current_nickname = $new_nickname; $current_email = $new_email; $current_nom = $new_nom; $current_cognom = $new_cognom;
                        if($photo_updated) $current_photo = $new_photo_path;
                        $_SESSION['email'] = $new_email;
                        if ($photo_updated) { $_SESSION['user_photo'] = $new_photo_path; }
                        if ($nickname_changed) {
                            $_SESSION['nickname'] = $new_nickname; $nickname = $new_nickname; 
                            $nickname_changes_count++;
                            setcookie($cookie_name, $nickname_changes_count, time() + (365 * 24 * 60 * 60), "/");
                            $can_change_nickname = ($nickname_changes_count < $max_nickname_changes);
                        }
                    } else { 
                         if ($conn->errno == 1062) { $error_message = "Error: El email o nickname ya está en uso."; }
                         else { $error_message = "Error al actualizar: " . $stmt_update->error; }
                    }
                    $stmt_update->close();
                } else { $error_message = "Error preparing update: " . $conn->error; }
            } else { if(empty($error_message)) $success_message = "No se realizaron cambios."; }
        }
    }
}
// --- Lógica de Eliminación (POST) (sense canvis) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $sql_delete = "DELETE FROM usuaris WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
     if (!$stmt_delete) {
        $error_message = "Error preparing delete statement: " . $conn->error;
    } else {
        $stmt_delete->bind_param("i", $user_id);
        if ($stmt_delete->execute()) {
            setcookie($cookie_name, '', time() - 3600, "/");
            session_destroy();
            exit(); 
        } else {
            $error_message = "Error al eliminar la cuenta: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    }
}
$paginaActual = ''; 
if ($conn && $conn->ping()) {
   $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Perfil de <?php echo htmlspecialchars($current_nickname); ?> - Shit Games</title>
  
  <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
  
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />
  
  <link rel="stylesheet" href="../frontend/assets/css/style_perfil.css" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <script>
    // ... (El JS de 'toggleEditMode' i 'previsualizarFoto' es queda igual) ...
    function toggleEditMode(enable) {
      const form = document.getElementById('perfil-form');
      const inputs = form.querySelectorAll('input[data-editable]');
      const editButton = document.getElementById('edit-button');
      const saveButton = document.getElementById('save-button');
      const cancelButton = document.getElementById('cancel-button');
      const passwordFields = form.querySelectorAll('.password-field');
      const fileInputLabel = document.querySelector('.boton-foto.edit-mode-item');

      if (enable) {
        form.classList.add('edit-mode');
        form.classList.remove('view-mode');
        inputs.forEach(input => {
            if(input.id === 'nickname' && !<?php echo json_encode($can_change_nickname); ?>) {
                input.setAttribute('readonly', true); input.style.cursor = 'not-allowed';
            } else if (input.type !== 'file') {
                input.removeAttribute('readonly'); input.style.cursor = 'text';
                input.style.backgroundColor = '#0F0F1A'; input.style.borderColor = 'rgba(255, 255, 255, 0.15)';
            }
        });
        passwordFields.forEach(field => field.style.display = 'block');
        if(fileInputLabel) fileInputLabel.style.display = 'inline-block';
        if(document.getElementById('nickname') && <?php echo json_encode($can_change_nickname); ?>) { document.getElementById('nickname').focus(); }
        else if (document.getElementById('nom')) { document.getElementById('nom').focus(); }
        else if (document.getElementById('email')) { document.getElementById('email').focus(); }
        editButton.style.display = 'none';
        saveButton.style.display = 'inline-block';
        cancelButton.style.display = 'inline-block';
      } else {
        form.classList.remove('edit-mode');
        form.classList.add('view-mode');
        inputs.forEach(input => {
            if (input.type !== 'file') {
                 input.setAttribute('readonly', true);
                 if (!document.querySelector('.form-message.error')) {
                    input.value = input.defaultValue;
                 }
                 input.style.backgroundColor = 'transparent'; input.style.borderColor = 'transparent'; input.style.cursor = 'default';
             } else { input.value = null; }
        });
        document.getElementById('password').value = ''; document.getElementById('confirm_password').value = '';
        passwordFields.forEach(field => field.style.display = 'none');
        if(fileInputLabel) fileInputLabel.style.display = 'none';
        editButton.style.display = 'inline-block';
        saveButton.style.display = 'none'; cancelButton.style.display = 'none';
        if (!document.querySelector('.form-message.error')) {
            const currentPhotoSrc = '<?php echo htmlspecialchars($current_photo); ?>'; 
            const photoPreview = document.getElementById('perfil-foto-img');
            if (photoPreview) photoPreview.src = currentPhotoSrc;
        }
        const errorMsg = form.querySelector('.form-message.error');
        if (errorMsg && !document.querySelector('.form-message.success')) { /* No fem res si hi ha error */ }
        else if (errorMsg) { errorMsg.remove(); }
      }
    }
     function previsualizarFoto(event) {
        const reader = new FileReader(); const output = document.getElementById('perfil-foto-img');
        reader.onload = function(){ if(output) output.src = reader.result; };
        if(event.target.files && event.target.files[0]){ reader.readAsDataURL(event.target.files[0]); }
        else { if(output) output.src = '<?php echo htmlspecialchars($current_photo); ?>'; } 
    }
  </script>
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>

  <main class="perfil-main-content">
      <form id="perfil-form" class="perfil-panel view-mode" method="post" enctype="multipart/form-data">
          
          <div class="perfil-edit-buttons">
              <button type="button" id="edit-button" class="boton-editar" onclick="toggleEditMode(true)">
                  <i class="fas fa-pencil-alt"></i> Editar Perfil
              </button>
              <button type="submit" id="save-button" name="update_profile" class="boton-principal" style="display: none;">
                  <i class="fas fa-save"></i> Guardar Cambios
              </button>
              <button type="button" id="cancel-button" class="boton-cancelar" onclick="toggleEditMode(false)" style="display: none;">
                   Cancelar
              </button>
          </div>
          <h1 class="perfil-titulo">Tu Perfil</h1>
          <?php if ($error_message): ?><p class="form-message error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>
          <?php if ($success_message): ?><p class="form-message success"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>
          
          <div class="perfil-header-section">
              <div class="perfil-foto-container">
                  <img id="perfil-foto-img" class="perfil-foto" src="<?php echo htmlspecialchars($current_photo); ?>" alt="Foto de perfil" />
                  <label for="foto-input" class="boton-foto edit-mode-item" style="display: none;">Cambiar Foto</label>
                  <input type="file" id="foto-input" name="nueva_foto" accept="image/*" hidden onchange="previsualizarFoto(event)" data-editable />
              </div>
              <div class="perfil-info-basica">
                  <div class="perfil-info-row">
                      <div class="perfil-campo nickname-field">
                          <label for="nickname">Nickname</label>
                          <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($current_nickname); ?>" readonly data-editable <?php echo !$can_change_nickname ? 'title="Límite de cambios alcanzado"' : ''; ?>/>
                           <?php if (!$can_change_nickname): ?>
                              <small class="edit-mode-item" style="color: #d23b3b;">(Límite alcanzado)</small>
                           <?php else: ?>
                              <small class="edit-mode-item">(Restantes: <?php echo $max_nickname_changes - $nickname_changes_count; ?>)</small>
                           <?php endif; ?>
                      </div>
                      <div class="perfil-info-item rank-global">
                          <strong>Rank Global:</strong> <?php echo $global_rank; ?>
                      </div>
                  </div>
                  <div class="perfil-info-row">
                       <div class="perfil-campo">
                          <label for="nom">Nombre</label>
                          <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($current_nom); ?>" readonly data-editable />
                      </div>
                       <div class="perfil-campo">
                          <label for="cognom">Apellido</label>
                          <input type="text" id="cognom" name="cognom" value="<?php echo htmlspecialchars($current_cognom); ?>" readonly data-editable />
                      </div>
                  </div>
                   <div class="perfil-campo">
                      <label for="email">Email</label>
                      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" readonly data-editable />
                  </div>
                   <div class="perfil-campo password-field" style="display: none;">
                      <label for="password">Nueva Contraseña</label>
                      <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" data-editable autocomplete="new-password"/>
                  </div>
                   <div class="perfil-campo password-field" style="display: none;">
                      <label for="confirm_password">Confirmar Contraseña</label>
                      <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la nueva contraseña" data-editable autocomplete="new-password"/>
                  </div>
              </div>
          </div>
          
          <div class="perfil-seccion">
              <h2><i class="fas fa-gamepad"></i> Mis Juegos</h2>
              
              <div class="played-games-grid">
                  <?php if (!empty($played_games)): ?>
                      <?php foreach ($played_games as $game): ?>
                          <a href="game_details.php?joc_id=<?php echo $game['joc_id']; ?>" class="game-card-link">
                              <div class="played-game-card">
                                  <img src="<?php echo htmlspecialchars($game['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($game['nom_joc']); ?>" />
                                  <div class="game-card-info">
                                      <h3><?php echo htmlspecialchars($game['nom_joc']); ?></h3>
                                      <p><i class="fas fa-clock"></i> <?php echo $game['temps_format']; ?></p>
                                      <p><i class="fas fa-calendar-alt"></i> Última: <?php echo $game['ultima_vegada_format']; ?></p>
                                      <p><i class="fas fa-trophy"></i> Rank: #<?php echo $game['rank_en_joc']; ?></p>
                                  </div>
                              </div>
                          </a>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <p class="placeholder-text" style="grid-column: 1 / -1;">Aún no has jugado ninguna partida.</p>
                  <?php endif; ?>
              </div>
          </div>
          
          <div class="perfil-seccion">
                <h2><i class="fas fa-heart"></i> Mi Lista de Deseos</h2>
                
                <div class="played-games-grid">
                    <?php if (!empty($wishlist_games)): ?>
                        <?php foreach ($wishlist_games as $joc): ?>
                        <a href="game_details.php?joc_id=<?php echo $joc['id']; ?>" class="game-card-link">
                            <div class="played-game-card">
                                <img src="<?php echo htmlspecialchars($joc['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($joc['nom_joc']); ?>">
                                <div class="game-card-info"> <h3><?php echo htmlspecialchars($joc['nom_joc']); ?></h3>
                                </div>
                                <div class="game-card-footer">
                                  <?php if ($joc['tipus'] == 'Premium'): ?>
                                    <div class="game-card-premium"><i class="fas fa-gem"></i></div>
                                  <?php else: ?>
                                    <div></div> 
                                  <?php endif; ?>
                                  <div class="game-card-rating">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo number_format($joc['valoracio'], 1); ?></span>
                                  </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="placeholder-text" style="grid-column: 1 / -1;">Aún no has añadido juegos a tu lista.</p>
                    <?php endif; ?>
                </div>
          </div>
          
          <div class="perfil-seccion">
              <h2><i class="fas fa-info-circle"></i> Información de la Cuenta</h2>
              <div class="perfil-info-item">
                  <strong>Miembro desde:</strong> <?php echo $current_data_registre; ?>
              </div>
          </div>
          
          <div class="perfil-acciones">
              <button type="button" id="logout-button" class="perfil-salida">Cerrar Sesión</button>
              <button type="button" id="open-delete-modal-button" class="boton-peligro">Eliminar Cuenta</button>
          </div>
      </form>
  </main>

  <div class="logout-modal-backdrop" id="logout-modal">
      <div class="logout-modal-content">
          <div class="spinner"></div>
          <h3>Cerrando sesión...</h3>
          <p>¡Hasta pronto!</p>
      </div>
  </div>
  <div class="delete-modal-backdrop" id="delete-modal">
      <div class="delete-modal-content">
          <h3>¿Estás seguro?</h3>
          <p>Esta acción es irreversible. Se eliminarán todos tus datos, partidas y progreso. Para confirmar, escribe tu nickname: 
             <strong><?php echo htmlspecialchars($current_nickname); ?></strong>
          </p>
          <input type="text" id="delete-confirm-input" class="delete-modal-input" placeholder="Escribe tu nickname aquí" autocomplete="off">
          <div class="delete-modal-actions">
              <button type="button" id="delete-cancel-button" class="boton-cancelar">Cancelar</button>
              <button type="button" id="delete-confirm-button" class="boton-peligro" disabled>Eliminar permanentemente</button>
          </div>
      </div>
  </div>

  <?php include './includes/_footer.php'; ?>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
      // (Mantenim la lògica d'edició que ja teníem)
      const inputs = document.querySelectorAll('#perfil-form input[data-editable]');
      inputs.forEach(input => { 
          if (input.type !== 'file') input.defaultValue = input.value; 
      });
      const hasSuccessMessage = document.querySelector('.form-message.success');
      const hasErrorMessage = document.querySelector('.form-message.error');
      if(hasSuccessMessage) { setTimeout(() => toggleEditMode(false), 50); } 
      else if (hasErrorMessage) { toggleEditMode(true); } 
      else { toggleEditMode(false); } 

      // (Lògica del Logout Modal - ja la teníem)
      const logoutButton = document.getElementById('logout-button');
      const logoutModal = document.getElementById('logout-modal');
      if (logoutButton && logoutModal) {
          logoutButton.addEventListener('click', (e) => {
              e.preventDefault(); 
              logoutModal.classList.add('visible');
              setTimeout(() => {
                  window.location.href = 'logout.php';
              }, 2000); 
          });
      }
      
      // (Lògica del Delete Modal - ja la teníem)
      const perfilForm = document.getElementById('perfil-form');
      const openDeleteModalButton = document.getElementById('open-delete-modal-button');
      const deleteModal = document.getElementById('delete-modal');
      const deleteCancelButton = document.getElementById('delete-cancel-button');
      const deleteConfirmInput = document.getElementById('delete-confirm-input');
      const deleteConfirmButton = document.getElementById('delete-confirm-button');
      const currentUserNickname = <?php echo json_encode($current_nickname); ?>;
      if (openDeleteModalButton && deleteModal) {
          openDeleteModalButton.addEventListener('click', () => {
              deleteModal.classList.add('visible');
              deleteConfirmInput.value = ''; 
              deleteConfirmButton.disabled = true; 
              deleteConfirmInput.focus(); 
          });
          deleteCancelButton.addEventListener('click', () => {
              deleteModal.classList.remove('visible');
          });
          deleteModal.addEventListener('click', (e) => {
              if (e.target === deleteModal) {
                  deleteModal.classList.remove('visible');
              }
          });
          deleteConfirmInput.addEventListener('input', () => {
              deleteConfirmButton.disabled = (deleteConfirmInput.value !== currentUserNickname);
          });
          deleteConfirmButton.addEventListener('click', () => {
              const hiddenInput = document.createElement('input');
              hiddenInput.type = 'hidden';
              hiddenInput.name = 'delete_account';
              hiddenInput.value = 'true';
              perfilForm.appendChild(hiddenInput);
              deleteModal.querySelector('h3').textContent = 'Eliminando...';
              deleteModal.querySelector('p').style.display = 'none';
              deleteModal.querySelector('.delete-modal-input').style.display = 'none';
              deleteModal.querySelector('.delete-modal-actions').innerHTML = '<div class="spinner"></div>';
              setTimeout(() => {
                   perfilForm.submit();
              }, 1500); 
          });
      }
  });
  
  // (La funció previsualizarFoto es queda igual)
  function previsualizarFoto(event) {
      const reader = new FileReader(); const output = document.getElementById('perfil-foto-img');
      reader.onload = function(){ if(output) output.src = reader.result; };
      if(event.target.files && event.target.files[0]){ reader.readAsDataURL(event.target.files[0]); }
      else { if(output) output.src = '<?php echo htmlspecialchars($current_photo); ?>'; } 
  }
  </script>

</body>
</html>