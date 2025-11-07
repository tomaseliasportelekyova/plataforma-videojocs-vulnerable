// Arxiu: frontend/jocs/joc_naus/main.js

// --------- Referències DOM ---------
const pantalla = document.querySelector("#pantalla");
const infoPartidaContainer = document.querySelector("#infoPartida");

const infoNivell = document.getElementById('info-nivell');
const infoPunts = document.getElementById('info-punts');
const infoKills = document.getElementById('info-kills');
const infoVides = document.getElementById('info-vides');
const infoTemps = document.getElementById('info-temps');

// --------- Variables Globals Joc ---------
let pantallaAmple = pantalla.offsetWidth;
let pantallaAlt = pantalla.offsetHeight;
const fotogrames = 1000 / 60; 

let vectorEnemics = [];
let vectorProjectils = [];
let vectorProjectilsEnemics = []; 
let boss = null;
let bossActiu = false;
let jugador = null;
let gameInterval = null; 
let timeInterval = null; 
let configNivell = {};
let nivellActual = 1;
let startTime = 0;
let pauseStartTime = 0; 

// === NOU: Variables per carregar el progrés ===
let initialScore = 0;
let initialKills = 0;
let initialTime = 0; // en segons

// Constants d'imatges
const JUGADOR_IMG = '../frontend/jocs/joc_naus/assets/player.png'; 
const ENEMIC_IMG = '../frontend/jocs/joc_naus/assets/enemy1.png'; 
const BOSS_IMG = '../frontend/jocs/joc_naus/assets/boss.png'; 
const PROJECTIL_IMG = null; 
const tecles = { ArrowLeft: false, ArrowRight: false };


// ================================================================
// ========= CARREGAR NIVELL (Funció inicial) =========
// ================================================================
async function carregarNivell() {
    const urlParams = new URLSearchParams(window.location.search);
    const jocIdUrl = parseInt(urlParams.get('joc_id')) || 1; 
    let nivellACarregar = parseInt(urlParams.get('nivell')) || 1;
    nivellActual = nivellACarregar;
    
    // === NOU: Llegim les dades de progrés de la URL ===
    initialScore = parseInt(urlParams.get('score')) || 0;
    initialKills = parseInt(urlParams.get('kills')) || 0;
    initialTime = parseInt(urlParams.get('time')) || 0; // en segons

    const apiPath = `api/get_nivell.php`; 

    try {
        const resposta = await fetch(`${apiPath}?joc_id=${jocIdUrl}&nivell=${nivellACarregar}`);
        if (!resposta.ok) {
             throw new Error(`Error ${resposta.status}: No s'ha pogut carregar la configuració del nivell ${nivellACarregar}.`);
        }
        configNivell = await resposta.json();
        configNivell.jocId = jocIdUrl; 
        
        prepararJoc(); 
        
        iniciarFluxJoc(iniciarJocReal);

    } catch (error) {
        console.error("Error carregant nivell:", error);
        const gameOverlay = document.getElementById('game-overlay');
        gameOverlay.innerHTML = `<h2 class="overlay-title" style="color: red;">Error</h2>
                                 <p class="controls-info">${error.message}</p>
                                 <a href="dashboard.php" class="play-button">Tornar</a>`;
        gameOverlay.classList.remove('hidden');
    }
}

// ================================================================
// ========= PREPARAR JOC (Setup inicial, PAUSAT) =========
// ================================================================
function prepararJoc() {
    pantalla.querySelectorAll('.entitat').forEach(el => el.remove());
    if (gameInterval) clearInterval(gameInterval); gameInterval = null;
    if (timeInterval) clearInterval(timeInterval); timeInterval = null;
    vectorEnemics = []; 
    vectorProjectils = [];
    vectorProjectilsEnemics = []; 
    boss = null; 
    bossActiu = false; 
    jugador = null;
    tecles.ArrowLeft = false; 
    tecles.ArrowRight = false;
    
    jugador = new Jugador("Player1", configNivell.vides || 3, 8, JUGADOR_IMG, PROJECTIL_IMG, pantalla);

    // === NOU: Apliquem les dades carregades ===
    jugador.punts = initialScore;
    jugador.derribats = initialKills;

    const maxEnemicsInicials = configNivell.maxEnemics || 5;
    for (let i = 0; i < maxEnemicsInicials; i++) crearEnemic();

    // Actualitzem panell d'informació amb les dades carregades
    infoNivell.textContent = nivellActual;
    infoPunts.textContent = jugador.punts; // Mostra punts acumulats
    infoKills.textContent = jugador.derribats; // Mostra kills acumulats
    infoVides.textContent = jugador.vides;
    
    // Calculem el temps inicial
    const minutes = String(Math.floor(initialTime / 60)).padStart(2, '0');
    const seconds = String(initialTime % 60).padStart(2, '0');
    infoTemps.textContent = `${minutes}:${seconds}`;

    jugador.dibuixar();
    vectorEnemics.forEach(e => e.dibuixar());
    
    if (pauseOverlay) pauseOverlay.classList.add('hidden');
    pantalla.querySelector('.stars').classList.remove('paused');
}


// ================================================================
// ========= AQUESTA ÉS LA FUNCIÓ QUE CRIDA EL 'game_flow.js' =========
// ================================================================
function iniciarJocReal() {
    if (gameInterval) return; 

    // === NOU: Ajustem el 'startTime' amb el temps acumulat ===
    // Restem el temps que ja portàvem (en ms)
    startTime = Date.now() - (initialTime * 1000); 

    timeInterval = setInterval(actualitzarTemps, 1000);
    gameInterval = setInterval(bucleJoc, fotogrames);

    activarControls();
    
    // --- NOU: Registrem les funcions del joc al Gestor Global ---
    initPauseManager(
        jocEspecific_pausar, 
        jocEspecific_continuar, 
        jocEspecific_reiniciar, 
        jocEspecific_sortir // <-- CANVI DE NOM
    );
    
    pantalla.querySelector('.stars').classList.remove('paused');
}

// ================================================================
// ========= BUCLE DEL JOC (El motor) =========
// ================================================================
function bucleJoc() {
    if (!jugador || !jugador.viu) {
        finalitzarPartida(false); 
        return;
    }
    if (tecles.ArrowLeft) jugador.moure('esquerra');
    if (tecles.ArrowRight) jugador.moure('dreta');
    comprovarCollisions();
    jugador.dibuixar();
    vectorEnemics.forEach(e => { if(e.viu) { e.moure(); e.dibuixar(); } });
    vectorProjectils.forEach(p => { if(p.viu) { p.moure(); p.dibuixar(); } });
    vectorProjectilsEnemics.forEach(p => { if(p.viu) { p.moure(); p.dibuixar(); } });
    if (bossActiu && boss && boss.viu) { boss.moure(); boss.dibuixar(); }
    vectorEnemics = vectorEnemics.filter(e => e.viu);
    vectorProjectils = vectorProjectils.filter(p => p.viu);
    vectorProjectilsEnemics = vectorProjectilsEnemics.filter(p => p.viu); 
    if (jugador.vides < 0) {
        infoVides.textContent = 0; 
        finalitzarPartida(false); 
        return; 
    }
    infoVides.textContent = Math.max(0, jugador.vides); 
    infoPunts.textContent = jugador.punts;
    infoKills.textContent = jugador.derribats;
    if (!bossActiu && vectorEnemics.length < (configNivell.maxEnemics || 5)) {
        if (Math.random() < 0.05) { crearEnemic(); }
    }
     if (!bossActiu && boss === null && jugador.punts >= (configNivell.puntsNivell || 100)) {
        spawnBoss();
    }
}


// --------- 3. ESDEVENIMENTS TECLAT (MODIFICAT) ---------
let controlsActius = false;
function activarControls() {
    if (controlsActius) return;
    controlsActius = true;
     document.addEventListener('keydown', (event) => {
        if (isPaused || !gameInterval) {
            // Si estem en pausa o el joc no ha començat,
            // només permetem que el 'game_pause_manager' gestioni 'Escape'
            if (event.code !== 'Escape') {
                 return; 
            }
        }

        // Si el joc SÍ està actiu (no pausat)
        if (event.code === 'ArrowLeft') tecles.ArrowLeft = true;
        if (event.code === 'ArrowRight') tecles.ArrowRight = true;
        if (event.code === 'Space') {
            event.preventDefault(); 
            if (jugador && jugador.viu) jugador.disparar();
        }
    });
     document.addEventListener('keyup', (event) => {
        if (event.code === 'ArrowLeft') tecles.ArrowLeft = false;
        if (event.code === 'ArrowRight') tecles.ArrowRight = false;
    });
}


// --- Funcions que implementen el "Contracte" del Gestor de Pausa ---

function jocEspecific_pausar() {
    if (!gameInterval || isPaused) return; 
    isPaused = true;
    pauseStartTime = Date.now();

    clearInterval(gameInterval); gameInterval = null;
    clearInterval(timeInterval); timeInterval = null;

    pantalla.querySelector('.stars').classList.add('paused');
    if (pauseOverlay) pauseOverlay.classList.remove('hidden');
}

function jocEspecific_continuar() {
    if (!isPaused) return;
    isPaused = false;
    
    // Ajustem el cronòmetre
    const pausedDuration = Date.now() - pauseStartTime;
    startTime += pausedDuration; // Sumem el temps de pausa al temps d'inici

    gameInterval = setInterval(bucleJoc, fotogrames);
    timeInterval = setInterval(actualitzarTemps, 1000);

    pantalla.querySelector('.stars').classList.remove('paused');
    if (pauseOverlay) pauseOverlay.classList.add('hidden');
}

function jocEspecific_reiniciar() {
    // Recarreguem la pàgina, però sense les dades de progrés
    // (només amb el nivell actual)
    window.location.href = `juego.php?joc_id=${configNivell.jocId}&nivell=${nivellActual}`;
}

// === CANVI: Aquesta funció ara NOMÉS surt ===
function jocEspecific_sortir() {
    // Ja no desa res, només torna al dashboard
    window.location.href = 'dashboard.php';
}


// --------- 4. ALTRES FUNCIONS ---------

function actualitzarTemps() {
    if (!startTime) return; 
    
    // Aquesta funció ara funciona perfectament
    // perquè 'startTime' ja està ajustat amb el 'initialTime'
    const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
    
    if (isPaused) return; 

    const minutes = String(Math.floor(elapsedTime / 60)).padStart(2, '0');
    const seconds = String(elapsedTime % 60).padStart(2, '0');
    if(infoTemps) infoTemps.textContent = `${minutes}:${seconds}`;
}

// ... (crearEnemic, comprovarCollisions, col·lisio, spawnBoss, passarDeNivell... 
//     tot això es queda igual) ...
function crearEnemic() {
    if (!pantalla || !jugador) return; 
    let posX = Math.random() * (pantallaAmple - 40); 
    let velocitat = (Math.random() * 1.5) + 0.5; 
    const ampleEnemic = 40;
    const altEnemic = 40;
    const nouEnemicRect = { x: posX, y: -altEnemic, ample: ampleEnemic, alt: altEnemic };
    for (const enemicExistent of vectorEnemics) {
        if (enemicExistent.y < altEnemic * 2) { 
            if (col·lisio(nouEnemicRect, enemicExistent)) {
                return; 
            }
        }
    }
    const nouEnemic = new Enemic(jugador, velocitat, posX, ENEMIC_IMG, pantalla, configNivell);
    vectorEnemics.push(nouEnemic);
}
function comprovarCollisions() {
    if (!jugador || !jugador.viu) return;
    vectorProjectils.forEach((p) => {
        vectorEnemics.forEach((e) => {
            if (p.viu && e.viu && col·lisio(p, e)) {
                p.morir(); e.morir();
                jugador.punts += configNivell.puntsPerEnemic || 10;
                jugador.derribats++;
            }
        });
        if (bossActiu && boss && boss.viu && p.viu && col·lisio(p, boss)) {
            p.morir();
            boss.rebreCop(); 
            jugador.punts += configNivell.puntsPerBoss || 5;
            if (boss.vides <= 0) {
                boss.morir(); bossActiu = false; boss = null;
                jugador.punts += 1000; 
                passarDeNivell(); 
            }
        }
    });
    vectorEnemics.forEach((e) => {
        if (e.viu && col·lisio(jugador, e)) { e.morir(); jugador.vides--; }
    });
    if (bossActiu && boss && boss.viu && col·lisio(jugador, boss)) {
        jugador.vides = -1; 
    }
    vectorProjectilsEnemics.forEach((p) => {
        if (p.viu && jugador.viu && col·lisio(p, jugador)) {
            p.morir(); 
            jugador.vides--; 
        }
    });
}
function col·lisio(rect1, rect2) {
    return ( rect1.x < rect2.x + rect2.ample && rect1.x + rect1.ample > rect2.x &&
             rect1.y < rect2.y + rect2.alt && rect1.y + rect1.alt > rect2.y );
}
function spawnBoss() {
    if (!pantalla || boss) return; 
    bossActiu = true;
    vectorEnemics.forEach(e => e.morir()); vectorEnemics = []; 
    boss = new Boss(configNivell.videsBoss || 50, BOSS_IMG, pantalla);
    if(infoNivell) infoNivell.textContent = `Nivell ${nivellActual} - BOSS!`;
}
function passarDeNivell() {
    finalitzarPartida(true); // Guanyat
}

// ================================================================
// ========= FINALITZAR PARTIDA (MODIFICADA) =========
// ================================================================
function finalitzarPartida(haGuanyatNivell) {
    isPaused = true; 
    if (pauseOverlay) pauseOverlay.classList.add('hidden');
    pantalla.querySelector('.stars').classList.add('paused');

    if (gameInterval) clearInterval(gameInterval); gameInterval = null;
    if (timeInterval) clearInterval(timeInterval); timeInterval = null;
    
    if (jugador) jugador.viu = false;
    vectorEnemics.forEach(e => e.velocitat = 0);
    if (boss) boss.velocitat = 0;

    const endTime = Date.now();
    
    // === CANVI: Càlcul de temps i punts ===
    // Temps total (incloent el temps carregat)
    const tempsTotalSegons = startTime ? Math.floor((endTime - startTime) / 1000) : 0;
    // Temps jugat NOMÉS en aquesta sessió
    const tempsAquestaPartida = tempsTotalSegons - initialTime;

    // Els punts i kills ja són els totals (p.ex. Nivell 1 + Nivell 2)
    const puntsFinals = jugador ? jugador.punts : 0; 
    
    // Enviem les dades. L'API només guardarà progrés si haGuanyatNivell = true
    enviarDadesPartida(configNivell.jocId || 1, nivellActual, puntsFinals, tempsAquestaPartida, haGuanyatNivell);

    // ... (Tota la lògica de mostrar l'overlay de "Game Over" o "Nivell Superat"
    //     es queda exactament igual que la tenies) ...
    const gameOverlay = document.getElementById('game-overlay');
    const startButton = document.getElementById('start-button');
    const exitLevelButton = document.getElementById('exit-level-button');
    const controlsInfo = gameOverlay.querySelector('p.controls-info');
    const overlayTitle = gameOverlay.querySelector('.overlay-title');
    
    if (!haGuanyatNivell) {
        infoVides.textContent = 0;
    }
    
    gameOverlay.classList.remove('hidden');
    gameOverlay.style.opacity = 1;
    gameOverlay.style.visibility = 'visible';
    gameOverlay.classList.remove('show-two-buttons'); 
    if (exitLevelButton) exitLevelButton.style.display = 'none'; 

    let missatge = '';
    let botoText = '';
    let botoAccio = null;

    const gamePath = `juego.php`; 
    const dashboardPath = `dashboard.php`; 

    if (!haGuanyatNivell) { 
        missatge = `Game Over! (Nivell ${nivellActual})<br>Punts: ${puntsFinals}`;
        botoText = '<i class="fas fa-redo"></i> Tornar a intentar';
        botoAccio = () => {
            // Reinicia el nivell, però MANTENINT el progrés carregat
            window.location.href = `${gamePath}?joc_id=${configNivell.jocId}&nivell=${nivellActual}&score=${initialScore}&kills=${initialKills}&time=${initialTime}`;
        };
        if (exitLevelButton) exitLevelButton.style.display = 'none';

    } else { 
        if (nivellActual < 3) { // Suposant 3 nivells
            missatge = `Nivell ${nivellActual} Superat!<br>Punts: ${puntsFinals}`;
            botoText = `Continuar (Nivell ${nivellActual + 1}) <i class="fas fa-arrow-right"></i>`;
            botoAccio = () => {
                // Envia al següent nivell AMB el progrés actualitzat
                window.location.href = `${gamePath}?joc_id=${configNivell.jocId}&nivell=${nivellActual + 1}&score=${puntsFinals}&kills=${jugador.derribats}&time=${tempsTotalSegons}`;
            };
            
            if (exitLevelButton) {
                exitLevelButton.style.display = 'inline-flex';
                exitLevelButton.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sortir';
                exitLevelButton.href = dashboardPath; 
            }
            gameOverlay.classList.add('show-two-buttons');

        } else { 
            missatge = `Enhorabona! Joc Completat!<br>Puntuació Final: ${puntsFinals}`;
            botoText = '<i class="fas fa-trophy"></i> Tornar al Dashboard';
            botoAccio = () => {
                window.location.href = dashboardPath;
            };
            if (exitLevelButton) exitLevelButton.style.display = 'none';
        }
    }

    if (overlayTitle) overlayTitle.innerHTML = missatge.includes('<br>') ? missatge.split('<br>')[0] : missatge; 
    if (controlsInfo) controlsInfo.innerHTML = missatge; 
    if (startButton) {
        startButton.innerHTML = botoText;
        startButton.onclick = botoAccio; 
    }
}


async function enviarDadesPartida(jocId, nivellJugat, puntuacio, tempsSegons, superat) {
    const apiPath = 'api/guardar_partida.php'; 
    console.log("Enviant dades a " + apiPath, {jocId, nivellJugat, puntuacio, tempsSegons, superat});
    
    // === CANVI: Enviem els KILLS totals ===
    const videsFinals = jugador ? Math.max(0, jugador.vides) : 0;
    const killsFinals = jugador ? jugador.derribats : 0; // Aquests ja són els totals

    try {
        const response = await fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin', 
            body: JSON.stringify({
                joc_id: jocId,
                nivell_jugat: nivellJugat,
                puntuacio_obtinguda: puntuacio, // Total de punts
                durada_segons: tempsSegons, // Temps NOMÉS d'aquest nivell
                nivell_superat: superat,
                dades_extra: {
                    kills: killsFinals, // Total de kills
                    vides_restants: videsFinals 
                }
            })
        });
        if (!response.ok) {
            console.error("Error de l'API al guardar partida:", response.statusText);
        } else {
            console.log("Partida guardada correctament!");
        }
    } catch (error) {
        console.error("Error de xarxa guardant partida:", error);
    }
}

// --- Funció per redimensionar (si la finestra canvia) ---
function redimensionarJoc() {
    pantallaAmple = pantalla.offsetWidth;
    pantallaAlt = pantalla.offsetHeight;
    if (jugador) {
        jugador.pantalla = pantalla; 
        jugador.x = pantallaAmple / 2 - jugador.ample / 2;
        jugador.y = pantallaAlt - jugador.alt - 10;
        jugador.dibuixar();
    }
}
window.addEventListener('resize', redimensionarJoc);

// --- INICI DEL JOC ---
carregarNivell();