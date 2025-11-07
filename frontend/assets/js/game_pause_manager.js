/*
 * Arxiu: frontend/assets/js/game_pause_manager.js
 * * Aquest arxiu conté la lògica COMUNA per al menú de pausa (ESC)
*/

// --- Variables Globals del Gestor ---
let isPaused = false;
let soundOn = true;

// --- Funcions "Contracte" ---
let funcPausarJoc = null;
let funcContinuarJoc = null;
let funcReiniciarJoc = null;
let funcSortir = null; // <-- CANVI DE NOM

// --- Referències al DOM (són comuns gràcies a juego.php) ---
const pauseOverlay = document.getElementById('pause-overlay');
const pauseContinueBtn = document.getElementById('pause-continue-btn');
const pauseRestartBtn = document.getElementById('pause-restart-btn');
const pauseSoundBtn = document.getElementById('pause-sound-btn');
const pauseExitBtn = document.getElementById('pause-exit-btn');


/**
 * Funció d'inicialització que crida el joc específic (main.js)
 * @param {function} onPause - Funció per pausar el joc
 * @param {function} onResume - Funció per continuar el joc
 * @param {function} onRestart - Funció per reiniciar el joc
 * @param {function} onExit - Funció per sortir (SENSE GUARDAR)
 */
function initPauseManager(onPause, onResume, onRestart, onExit) {
    // 1. Guardem les funcions específiques del joc
    funcPausarJoc = onPause;
    funcContinuarJoc = onResume;
    funcReiniciarJoc = onRestart;
    funcSortir = onExit; // <-- CANVI DE NOM

    // 2. Activem els listeners
    activarBotonsPausa();
    activarEscListener();
}

function activarBotonsPausa() {
    if (!pauseContinueBtn) return; // Comprovació per si de cas

    pauseContinueBtn.addEventListener('click', () => {
        if (funcContinuarJoc) funcContinuarJoc();
    });
    
    pauseRestartBtn.addEventListener('click', () => {
        if (funcReiniciarJoc) funcReiniciarJoc();
    });

    // === CANVI: Aquest botó ara només surt ===
    pauseExitBtn.addEventListener('click', () => {
        if (funcSortir) funcSortir(); // <-- CANVI DE NOM
    });
    
    pauseSoundBtn.addEventListener('click', () => {
        soundOn = !soundOn; // Invertim l'estat
        if (soundOn) {
            pauseSoundBtn.innerHTML = '<i class="fas fa-volume-up"></i> Sound On';
        } else {
            pauseSoundBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Sound Off';
        }
    });
}

function activarEscListener() {
    document.addEventListener('keydown', (event) => {
        if (event.code === 'Escape') {
            event.preventDefault();
            
            if (!funcPausarJoc) return; 

            if (isPaused) {
                if (funcContinuarJoc) funcContinuarJoc();
            } else {
                if (funcPausarJoc) funcPausarJoc();
            }
        }
    });
}