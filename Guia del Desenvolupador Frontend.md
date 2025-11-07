# Guia del Desenvolupador Frontend

Aquest document explica l'arquitectura modular del frontend de la plataforma, dissenyada per reutilitzar codi i facilitar la integració de nous jocs.

## 1. Arquitectura CSS Modular

Els estils estan dividits en diversos arxius CSS per mantenir una estructura neta:

- **`style_base.css`**: (Carregat a totes les pàgines)
    - Defineix `@import` de les fonts (Inter).
    - Reseteja marges i `box-sizing`.
    - Defineix l'estil base de `body.dark-theme` (color de fons, color de text, `padding-top` per al header fix).
- **`style_common.css`**: (Carregat a totes les pàgines)
    - Conté els estils per a elements compartits a tot el lloc: `_header.php` i `_footer.php`.
- **`style_dashboard.css`**: (Carregat a `dashboard.php`, `biblioteca.php` i `perfil.php`)
    - Conté els estils per als components principals del *dashboard*: carrusel "hero", files de jocs (`.game-row`), i el disseny de les targetes de joc (`.game-card`).
    - El `perfil.php` i `biblioteca.php` el carreguen per reutilitzar l'estil `.game-card`.
- **`style_game_details.css`**: (Carregat només a `game_details.php`)
    - Defineix l'estil de la pàgina de detalls del joc, incloent-hi la capçalera "hero", els botons de "Canjear"/"Jugar", i el pop-up de valoració.
- **`style_perfil.css` / `style_ranking_dashboard.css`**:
    - Estils específics només per al contingut de les pàgines de perfil i rànquing, respectivament.
- **`style_game_common.css`**: (Carregat només a `juego.php`)
    - Conté els estils per a la interfície *dins del joc*: el panell d'informació (`#infoPartida`) i tots els *overlays* (inici, instruccions, pausa, fi de partida).
- **`jocs/joc_naus/style.css`**:
    - Estils específics *només* per al joc de naus (p.ex., `.stars`, `.jugador`, `.projectil`).

## 2. El "Framework" de Jocs JavaScript

Per evitar repetir codi, la plataforma proporciona dos scripts globals que gestionen el cicle de vida d'un joc. Aquests estan a `frontend/assets/js/`:

1. **`game_flow.js`**: Gestiona el flux d'inici: `Overlay "Començar" -> Overlay "Instruccions" -> Compte enrere -> Iniciar Joc`.
2. **`game_pause_manager.js`**: Gestiona la pausa. Escolta la tecla `Escape` i controla els botons del menú de pausa (`#pause-overlay`).

### Com afegir un Joc Nou (Ex: `joc_pong`)

Perquè un joc nou (p.ex., "Pong") s'integri correctament, el seu arxiu JS principal (p.ex., `joc_ping_pong/index.js`) ha de complir un "contracte":

**1. Definir la funció d'inici del joc**
Aquesta és la funció principal que arrenca els `setIntervals` o `requestAnimationFrame` del joc.

```
// Dins de joc_ping_pong/index.js
function iniciarJocPong() {
    // Tota la lògica que arrenca el joc de Pong
    // p.ex., gameInterval = setInterval(bucleJocPong, 30);

    // ...

    // Al final, registrar les funcions de pausa
    initPauseManager(
        jocPong_pausar,
        jocPong_continuar,
        jocPong_reiniciar,
        jocPong_sortir
    );
}

```

**2. Implementar les funcions del "Contracte" del Gestor de Pausa**
El `game_pause_manager.js` necessita saber com pausar, continuar, reiniciar o sortir *del teu joc específic*. Has de crear aquestes 4 funcions:

```
// Dins de joc_ping_pong/index.js

function jocPong_pausar() {
    isPaused = true;
    clearInterval(gameInterval); // Atura el bucle del Pong
    gameInterval = null;
    pauseOverlay.classList.remove('hidden'); // Mostra el menú
}

function jocPong_continuar() {
    isPaused = false;
    gameInterval = setInterval(bucleJocPong, 30); // Reprèn el bucle del Pong
    pauseOverlay.classList.add('hidden'); // Amaga el menú
}

function jocPong_reiniciar() {
    // Simplement recarrega la pàgina del nivell actual
    window.location.reload();
}

function jocPong_sortir() {
    // Torna al dashboard (NO guarda progrés)
    window.location.href = 'dashboard.php';
}

```

**3. Iniciar el Flux de Joc**
Al final de tot del teu arxiu `joc_ping_pong/index.js`, només has de fer una crida per iniciar tot el procés:

```
// Dins de joc_ping_pong/index.js (al final de tot)

// Això arrenca el 'game_flow.js', que mostrarà l'overlay d'inici.
// Quan l'usuari acabi el compte enrere, 'game_flow.js' cridarà
// automàticament a la funció que li passem: 'iniciarJocPong'.
iniciarFluxJoc(iniciarJocPong);

```
