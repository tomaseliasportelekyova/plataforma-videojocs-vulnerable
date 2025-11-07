// Arxiu: frontend/jocs/joc_snake_tron/main.js
// VERSIÓ 2: Disseny de serp millorat i avís de trampes

document.addEventListener('DOMContentLoaded', () => {

    // --------- Referències DOM ---------
    const canvas = document.getElementById('snake-canvas');
    if (!canvas) {
        console.error("No s'ha trobat l'element #snake-canvas.");
        return; 
    }
    const ctx = canvas.getContext('2d');

    // Referències Panell Info
    const infoNivell = document.getElementById('info-nivell');
    const infoPunts = document.getElementById('info-punts');
    const infoPomes = document.getElementById('info-kills');
    const infoVides = document.getElementById('info-vides');
    const infoTemps = document.getElementById('info-temps');
    
    // --------- Variables Globals Joc ---------
    let configNivell = {}; 
    let jocId = 24;
    let nivellActual = 1;
    let gameInterval = null;
    let timeInterval = null;
    let startTime = 0;
    let pauseStartTime = 0;
    let tempsTotalSegons = 0;

    // Variables Snake
    let gridSize = 20;
    let tileSize = canvas.width / gridSize;
    let velocitatMs = 150;
    let serp = [];
    let direccio = { x: 0, y: 0 };
    let menjar = {};
    let puntuacio = 0;
    let vides = 1; 
    let pomesMenjades = 0;
    
    // --- NOVES VARIABLES PER TRAMPES ---
    let trampesAvisos = []; // Llista de trampes en estat "avís"
    let trampesActives = []; // Llista de trampes letals
    let intervalGenerarTrampa = null;
    const TEMPS_AVIS_TRAMPA_MS = 1000; // 1 segon d'avís
    const DURADA_TRAMPA_MS = 2000; // 2 segons activa
    const TEMPS_APARICIO_TRAMPA_MS = 5000; // Cada 5 segons
    // ------------------------------------
    
    // Colors TRON
    const COLOR_SERP = '#00FFFF'; // Cian
    const COLOR_CAP = '#FFFFFF'; // Blanc
    const COLOR_MENJAR = '#FF00FF'; // Magenta
    const COLOR_RESPLENDOR = 'rgba(0, 255, 255, 0.7)';
    const COLOR_RESPLENDOR_MENJAR = 'rgba(255, 0, 255, 0.7)';
    
    // --- NOUS COLORS TRAMPA ---
    const COLOR_TRAMPA_AVIS = '#FFFF00'; // Groc avís
    const COLOR_RESPLENDOR_AVIS = 'rgba(255, 255, 0, 0.7)';
    const COLOR_TRAMPA_ACTIVA = '#FF0000'; // Vermell perill
    const COLOR_RESPLENDOR_ACTIVA = 'rgba(255, 0, 0, 0.9)';
    // ---------------------------

    // ================================================================
    // ========= CÀRREGA I PREPARACIÓ =========
    // ================================================================

    async function carregarNivell() {
        const urlParams = new URLSearchParams(window.location.search);
        jocId = parseInt(urlParams.get('joc_id')) || 24;
        nivellActual = parseInt(urlParams.get('nivell')) || 1;
        
        const apiPath = `api/get_nivell.php`;
        try {
            const resposta = await fetch(`${apiPath}?joc_id=${jocId}&nivell=${nivellActual}`);
            if (!resposta.ok) throw new Error(`Error ${resposta.status}: No s'ha pogut carregar la config del nivell.`);
            
            configNivell = await resposta.json();
            
            gridSize = configNivell.gridSize || 20;
            velocitatMs = configNivell.velocitatMs || 150;
            tileSize = canvas.width / gridSize;
            
            prepararJoc(); 
            // Cridem al TEU 'game_flow.js'
            iniciarFluxJoc(iniciarJocReal);

        } catch (error) {
            console.error("Error carregant nivell:", error);
            // ... (codi de gestió d'error)
        }
    }

    function prepararJoc() {
        if (gameInterval) clearInterval(gameInterval); gameInterval = null;
        if (timeInterval) clearInterval(timeInterval); timeInterval = null;
        if (intervalGenerarTrampa) clearInterval(intervalGenerarTrampa); intervalGenerarTrampa = null;
        
        serp = [ { x: Math.floor(gridSize / 2), y: Math.floor(gridSize / 2) } ];
        direccio = { x: 0, y: 0 }; 
        puntuacio = 0;
        pomesMenjades = 0;
        vides = 1; 
        trampesAvisos = [];
        trampesActives = [];
        
        generarMenjar();
        
        if(infoNivell) infoNivell.textContent = nivellActual;
        if(infoPunts) infoPunts.textContent = puntuacio;
        if(infoPomes) infoPomes.textContent = pomesMenjades;
        if(infoVides) infoVides.textContent = vides;
        if(infoTemps) infoTemps.textContent = "00:00";
        
        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');
        
        dibuixarJoc(); 
    }

    // ================================================================
    // ========= FLUX DEL JOC I BUCLES =========
    // ================================================================

    function iniciarJocReal() {
        if (gameInterval) return; 
        startTime = Date.now(); 
        timeInterval = setInterval(actualitzarTemps, 1000);
        gameInterval = setInterval(bucleJoc, velocitatMs); 
        intervalGenerarTrampa = setInterval(generarTrampa, TEMPS_APARICIO_TRAMPA_MS);

        activarControls();
        
        // Ens connectem al TEU 'game_pause_manager.js'
        initPauseManager(
            jocSnake_pausar, 
            jocSnake_continuar, 
            jocSnake_reiniciar, 
            jocSnake_sortir
        );
    }

    function bucleJoc() {
        if (isPaused) return;

        // Abans de moure, gestionem les trampes
        gestionarTrampes();

        if (moureSerp()) {
            vides = 0;
            finalitzarPartida(false); 
        } else {
            dibuixarJoc();
        }
    }

    function actualitzarTemps() {
        if (!startTime || isPaused) return; 
        const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
        tempsTotalSegons = elapsedTime; 
        const minutes = String(Math.floor(elapsedTime / 60)).padStart(2, '0');
        const seconds = String(elapsedTime % 60).padStart(2, '0');
        if(infoTemps) infoTemps.textContent = `${minutes}:${seconds}`;
    }

    // ================================================================
    // ========= LÒGICA DEL JOC SNAKE =========
    // ================================================================

    function moureSerp() {
        if (direccio.x === 0 && direccio.y === 0) return false;

        const cap = { x: serp[0].x + direccio.x, y: serp[0].y + direccio.y };

        // 1. Col·lisió Parets
        if (cap.x < 0 || cap.x >= gridSize || cap.y < 0 || cap.y >= gridSize) {
            return true; 
        }
        // 2. Col·lisió Serp
        for (let i = 0; i < serp.length; i++) {
            if (cap.x === serp[i].x && cap.y === serp[i].y) {
                return true; 
            }
        }
        // 3. Col·lisió Trampes ACTIVES
        for (const trampa of trampesActives) {
            if (cap.x === trampa.x && cap.y === trampa.y) {
                return true;
            }
        }

        serp.unshift(cap); // Afegim el nou cap

        if (cap.x === menjar.x && cap.y === menjar.y) {
            puntuacio += 10;
            pomesMenjades++;
            
            // Augmentem velocitat (descomentat)
            velocitatMs = Math.max(50, velocitatMs * 0.95); 
            clearInterval(gameInterval); 
            gameInterval = setInterval(bucleJoc, velocitatMs);
            
            generarMenjar();
            
            if(infoPunts) infoPunts.textContent = puntuacio;
            if(infoPomes) infoPomes.textContent = pomesMenjades;
        } else {
            serp.pop(); // Traiem la cua
        }
        
        return false; 
    }

    function generarMenjar() {
        let posicioValida = false;
        while (!posicioValida) {
            menjar = {
                x: Math.floor(Math.random() * gridSize),
                y: Math.floor(Math.random() * gridSize)
            };
            posicioValida = true;
            
            // Comprovar si està a sobre la serp
            for (const segment of serp) {
                if (segment.x === menjar.x && segment.y === menjar.y) {
                    posicioValida = false; break;
                }
            }
            if (!posicioValida) continue;

            // Comprovar si està a sobre una trampa ACTIVA
            for (const trampa of trampesActives) {
                if (trampa.x === menjar.x && trampa.y === menjar.y) {
                    posicioValida = false; break;
                }
            }
            if (!posicioValida) continue;

            // Comprovar si està a sobre una trampa en AVIS
            for (const trampa of trampesAvisos) {
                if (trampa.x === menjar.x && trampa.y === menjar.y) {
                    posicioValida = false; break;
                }
            }
        }
    }

    // --- LÒGICA DE TRAMPES MILLORADA ---
    function generarTrampa() {
        if (isPaused) return;

        let posicioValida = false;
        let novaTrampa = {};
        while (!posicioValida) {
            novaTrampa = {
                x: Math.floor(Math.random() * gridSize),
                y: Math.floor(Math.random() * gridSize),
                tempsCreacio: Date.now() // Per controlar l'avís
            };
            
            posicioValida = true;
            // Evitar generar sobre la serp
            for (const segment of serp) {
                if (segment.x === novaTrampa.x && segment.y === novaTrampa.y) {
                    posicioValida = false; break;
                }
            }
            if (!posicioValida) continue;
            
            // Evitar generar sobre el menjar
            if (menjar.x === novaTrampa.x && menjar.y === novaTrampa.y) {
                posicioValida = false;
            }
        }
        // L'afegim a la llista d'AVISOS
        trampesAvisos.push(novaTrampa);
    }

    function gestionarTrampes() {
        const tempsActual = Date.now();

        // 1. Revisar AVISOS per activar-los
        trampesAvisos = trampesAvisos.filter(trampa => {
            if (tempsActual - trampa.tempsCreacio > TEMPS_AVIS_TRAMPA_MS) {
                // S'ha acabat l'avís, la movem a ACTIVES
                trampa.tempsCreacio = tempsActual; // Resetejem el temps per la durada activa
                trampesActives.push(trampa);
                return false; // L'eliminem de la llista d'avisos
            }
            return true; // La mantenim a avisos
        });

        // 2. Revisar ACTIVES per esborrar-les
        trampesActives = trampesActives.filter(trampa => {
            return (tempsActual - trampa.tempsCreacio) < DURADA_TRAMPA_MS;
        });
    }
    // --- FI LÒGICA DE TRAMPES ---


    function dibuixarJoc() {
        // Estela TRON (Fons negre semitransparent)
        ctx.fillStyle = 'rgba(0, 0, 0, 0.2)'; 
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Dibuixar Menjar
        ctx.fillStyle = COLOR_MENJAR;
        ctx.shadowColor = COLOR_RESPLENDOR_MENJAR;
        ctx.shadowBlur = 15;
        dibuixarQuadrat(menjar.x, menjar.y);
        
        // --- DIBUIXAR TRAMPES (AVÍS) ---
        ctx.shadowColor = COLOR_RESPLENDOR_AVIS;
        ctx.shadowBlur = 15;
        trampesAvisos.forEach(trampa => {
            // Parpelleig ràpid per avís
            const alpha = (Math.sin(Date.now() / 50) > 0) ? 1 : 0.5;
            ctx.fillStyle = `rgba(255, 255, 0, ${alpha})`; // Groc
            dibuixarQuadrat(trampa.x, trampa.y);
        });

        // --- DIBUIXAR TRAMPES (ACTIVES) ---
        ctx.shadowColor = COLOR_RESPLENDOR_ACTIVA;
        ctx.shadowBlur = 15;
        trampesActives.forEach(trampa => {
            ctx.fillStyle = COLOR_TRAMPA_ACTIVA; // Vermell
            dibuixarQuadrat(trampa.x, trampa.y);
        });
        
        // --- DIBUIXAR SERP (NOU DISSENY) ---
        ctx.shadowColor = COLOR_RESPLENDOR;
        ctx.shadowBlur = 10;
        
        // 1. Dibuixar el cos (línies)
        ctx.strokeStyle = COLOR_SERP;
        ctx.lineWidth = tileSize * 0.8; // Gruix de la línia (80% del quadrat)
        ctx.lineCap = 'round'; // Extrems arrodonits
        ctx.lineJoin = 'round'; // Unions arrodonides

        ctx.beginPath();
        ctx.moveTo(
            serp[0].x * tileSize + tileSize / 2, 
            serp[0].y * tileSize + tileSize / 2
        );
        for (let i = 1; i < serp.length; i++) {
            ctx.lineTo(
                serp[i].x * tileSize + tileSize / 2, 
                serp[i].y * tileSize + tileSize / 2
            );
        }
        ctx.stroke();

        // 2. Dibuixar el cap (cercle) a sobre
        ctx.fillStyle = COLOR_CAP;
        ctx.beginPath();
        ctx.arc(
            serp[0].x * tileSize + tileSize / 2, // Centre X
            serp[0].y * tileSize + tileSize / 2, // Centre Y
            tileSize * 0.45, // Radi (una mica més gran que el cos)
            0, 2 * Math.PI
        );
        ctx.fill();
        // --- FI NOU DISSENY SERP ---

        ctx.shadowBlur = 0; // Resetejem ombres
    }

    /** Funció ajudant per dibuixar un quadrat a la reixeta */
    function dibuixarQuadrat(gridX, gridY) {
        // La usem ara només pel menjar i les trampes
        const margin = 0.1; 
        ctx.fillRect(
            gridX * tileSize + (tileSize * margin / 2),
            gridY * tileSize + (tileSize * margin / 2),
            tileSize * (1 - margin),
            tileSize * (1 - margin)
        );
    }

    // ================================================================
    // ========= GESTIÓ DE PAUSA (Contracte) =========
    // ================================================================

    let isPaused = false;

    function jocSnake_pausar() {
        if (isPaused) return;
        isPaused = true;
        pauseStartTime = Date.now();
        
        if (gameInterval) clearInterval(gameInterval); gameInterval = null;
        if (timeInterval) clearInterval(timeInterval); timeInterval = null;
        if (intervalGenerarTrampa) clearInterval(intervalGenerarTrampa); intervalGenerarTrampa = null;

        // Congelar els temporitzadors de les trampes
        const tempsPausa = Date.now();
        trampesAvisos.forEach(t => t.tempsPausa = tempsPausa);
        trampesActives.forEach(t => t.tempsPausa = tempsPausa);

        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.remove('hidden');
    }

    function jocSnake_continuar() {
        if (!isPaused) return;
        isPaused = false;
        
        const tempsPausat = Date.now() - pauseStartTime;
        startTime += tempsPausat; 

        // Reprenem els bucles
        gameInterval = setInterval(bucleJoc, velocitatMs);
        timeInterval = setInterval(actualitzarTemps, 1000);
        intervalGenerarTrampa = setInterval(generarTrampa, TEMPS_APARICIO_TRAMPA_MS);

        // Ajustem els temps de les trampes
        trampesAvisos.forEach(t => t.tempsCreacio += tempsPausat);
        trampesActives.forEach(t => t.tempsCreacio += tempsPausat);

        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');
    }

    function jocSnake_reiniciar() {
        window.location.href = `juego.php?joc_id=${jocId}&nivell=${nivellActual}`;
    }

    function jocSnake_sortir() {
        window.location.href = 'dashboard.php';
    }

    // ================================================================
    // ========= CONTROLS I FI DE PARTIDA =========
    // ================================================================

    function activarControls() {
         document.addEventListener('keydown', (event) => {
            if (isPaused || !gameInterval) {
                if (event.code !== 'Escape') return; 
            }
            
            // Lògica per evitar girar 180 graus
            switch (event.code) {
                case 'ArrowUp':
                case 'KeyW':
                    if (direccio.y === 0) direccio = { x: 0, y: -1 };
                    event.preventDefault();
                    break;
                case 'ArrowDown':
                case 'KeyS':
                    if (direccio.y === 0) direccio = { x: 0, y: 1 };
                    event.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'KeyA':
                    if (direccio.x === 0) direccio = { x: -1, y: 0 };
                    event.preventDefault();
                    break;
                case 'ArrowRight':
                case 'KeyD':
                    if (direccio.x === 0) direccio = { x: 1, y: 0 };
                    event.preventDefault();
                    break;
            }
        });
    }

    function finalitzarPartida(haGuanyatNivell) {
        isPaused = true; 
        if(infoVides) infoVides.textContent = 0;
        
        // Parem tots els intervals
        if (gameInterval) clearInterval(gameInterval); gameInterval = null;
        if (timeInterval) clearInterval(timeInterval); timeInterval = null;
        if (intervalGenerarTrampa) clearInterval(intervalGenerarTrampa); intervalGenerarTrampa = null;
        
        // ... (Mostrar overlay de Game Over, exactament igual que abans) ...
        
        // Dibuixem un efecte "Game Over"
        ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
        ctx.shadowColor = 'red';
        ctx.shadowBlur = 20;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.shadowBlur = 0;
        
        enviarDadesPartida(
            jocId, 
            nivellActual, 
            puntuacio, 
            tempsTotalSegons, 
            haGuanyatNivell, 
            { "pomes": pomesMenjades }
        );

        const gameOverlay = document.getElementById('game-overlay');
        const startButton = document.getElementById('start-button');
        const exitLevelButton = document.getElementById('exit-level-button');
        const controlsInfo = gameOverlay.querySelector('p.controls-info');
        const overlayTitle = gameOverlay.querySelector('.overlay-title');
        
        if (!gameOverlay || !startButton || !exitLevelButton || !controlsInfo || !overlayTitle) {
            console.error("No es poden trobar els elements de l'overlay.");
            return;
        }
        
        gameOverlay.classList.remove('hidden');
        gameOverlay.classList.remove('show-two-buttons'); 
        if (exitLevelButton) exitLevelButton.style.display = 'none'; 

        overlayTitle.innerHTML = `Game Over`;
        controlsInfo.innerHTML = `Puntuació: ${puntuacio}<br>Pomes: ${pomesMenjades}<br>Temps: ${infoTemps.textContent}`;
        startButton.innerHTML = '<i class="fas fa-redo"></i> Tornar a intentar';
        startButton.onclick = () => {
            window.location.href = `juego.php?joc_id=${jocId}&nivell=${nivellActual}`;
        };
    }

    async function enviarDadesPartida(jocId, nivellJugat, puntuacio, tempsSegons, superat, dadesExtra) {
        // ... (Aquesta funció és la mateixa, no canvia) ...
        const apiPath = 'api/guardar_partida.php'; 
        try {
            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin', 
                body: JSON.stringify({
                    joc_id: jocId,
                    nivell_jugat: nivellJugat,
                    puntuacio_obtinguda: puntuacio,
                    durada_segons: tempsSegons, 
                    nivell_superat: superat,
                    dades_extra: dadesExtra
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

    // --- INICI DEL JOC ---
    carregarNivell();
});