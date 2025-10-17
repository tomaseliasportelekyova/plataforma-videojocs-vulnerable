<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard de Videojuegos</title>
  <link rel="stylesheet" href="../frontend/assets/css/dashboard.css" />
</head>
<body>
  <header>
    <h1>Videojuegos</h1>
  </header>

  <main class="dashboard">
  <div class="game-card">
    <div class="game-content">
      <h2>Space War</h2>
      <p><strong>Plataforma:</strong> Nintendo Switch</p>
      <p><strong>Puntuación:</strong> 9.5/10</p>
      <span class="status accion">Acción</span>
      <span class="status espacial">Espacial</span>
    </div>
    <div class="game-image-container">
      <img src="../frontend/imatges/spacewar.jpg" alt="Space War" class="game-image" />
    </div>
  </div>

  <div class="game-card">
    <div class="game-content">
      <h2>Unlucky Mario Bros</h2>
      <p><strong>Plataforma:</strong> PlayStation 5</p>
      <p><strong>Puntuación:</strong> 9.8/10</p>
      <span class="status hardcore">Hardcore</span>
      <span class="status accion">Acción</span>
      <span class="status aventuras">Aventuras</span>
    </div>
    <div class="game-image-container">
      <img src="../frontend/imatges/mario.png" alt="Unlucky Mario Bros" class="game-image" />
    </div>
  </div>

  <div class="game-card">
    <div class="game-content">
      <h2>Hollow Knight</h2>
      <p><strong>Género:</strong> Metroidvania</p>
      <p><strong>Plataforma:</strong> PC</p>
      <p><strong>Puntuación:</strong> 9.0/10</p>
      <span class="status pendiente">Pendiente</span>
    </div>
    <div class="game-image-container">
      <img src="../frontend/imatges/hollow-knight.jpg" alt="Hollow Knight" class="game-image" />
    </div>
  </div>
</main>
</body>
</html>