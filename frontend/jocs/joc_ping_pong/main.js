// Arxiu: frontend/jocs/joc_ping_pong/main.js
// Versió amb refactorització a Multi-Bola i Clons de Sombra

document.addEventListener('DOMContentLoaded', () => {

    // --------- Referències DOM (GDD + game_content.html) ---------
    const pantalla = document.getElementById('pantalla');
    const canvas = document.getElementById('pong-canvas');
    if (!canvas) {
        console.error("No s'ha trobat l'element #pong-canvas. El joc no pot començar.");
        return; 
    }
    const ctx = canvas.getContext('2d');
    const particleContainer = document.getElementById('particle-container');

    // Referències al Panell d'Informació (de juego.php)
    const infoNivell = document.getElementById('info-nivell');
    const infoPunts = document.getElementById('info-punts');
    const infoMarcador = document.getElementById('info-marcador');
    const infoVides = document.getElementById('info-vides');
    const infoTemps = document.getElementById('info-temps');
    
    // Referències al panell d'habilitats
    const powerUpTimerDisplay = document.getElementById('powerup-timer');
    const powerUpListItems = document.querySelectorAll('#powerup-list li');


    // --------- Variables Globals Joc ---------
    let configNivell = {}; 
    let jocId = 2;
    let nivellActual = 1;

    let gameInterval = null;
    let timeInterval = null;
    let startTime = 0;
    let pauseStartTime = 0;
    let tempsTotalSegons = 0;

    let initialScore = 0;
    let initialTime = 0; 

    const pala = { width: 15, height: 100 }; // Config base
    let palaJugador = { 
        x: canvas.width - 25 - pala.width, 
        y: canvas.height / 2 - pala.height / 2,
        height: pala.height 
    };
    let palaIA = { 
        x: 25, 
        y: canvas.height / 2 - pala.height / 2,
        height: pala.height 
    };

    let boles = [];

    const tecles = { ArrowUp: false, ArrowDown: false };

    let puntuacioTotal = 0; 
    let videsJugador = 5;
    let puntsIA = 0;
    let puntsJugador = 0;
    const puntsPerGuanyarNivell = 5;

    // --- Variables per Power-Ups ---
    let powerUpTimer = null; 
    let activePowerUp = 'NONE';
    let powerUpDuration = 10; 
    let powerUpNextTrigger = 12; // 12 segons
    let powerUpCountdown = powerUpNextTrigger; 
    
    const powerUpList = [
        'SUPER_VELOCITAT', 
        'PALA_GEGANT_IA', 
        'BOLA_FANTASMA',
        'PALA_GEGANT_JUGADOR', 
        'PALA_PETITA_IA',     
        'REBOT_INVERS_VERTICAL', 
        'BOLA_LENTA',
        'MULTI-BOLA', 
        'CLONS_DE_SOMBRA' 
    ];
    

    // ================================================================
    // ========= CÀRREGA I PREPARACIÓ =========
    // ================================================================

    async function carregarNivell() {
        const urlParams = new URLSearchParams(window.location.search);
        jocId = parseInt(urlParams.get('joc_id')) || 2;
        nivellActual = parseInt(urlParams.get('nivell')) || 1;
        
        initialScore = parseInt(urlParams.get('score')) || 0;
        initialTime = parseInt(urlParams.get('time')) || 0; 

        const apiPath = `api/get_nivell.php`;

        try {
            const resposta = await fetch(`${apiPath}?joc_id=${jocId}&nivell=${nivellActual}`);
            if (!resposta.ok) {
                 throw new Error(`Error ${resposta.status}: No s'ha pogut carregar la config del nivell ${nivellActual}. Assegura't d'afegir-la a la BBDD.`);
            }
            configNivell = await resposta.json();
            
            prepararJoc(); 
            
            iniciarFluxJoc(iniciarJocReal);

        } catch (error) {
            console.error("Error carregant nivell:", error);
            const gameOverlay = document.getElementById('game-overlay');
            if(gameOverlay) { 
                gameOverlay.innerHTML = `<h2 class="overlay-title" style="color: red;">Error</h2>
                                     <p class="controls-info">${error.message}</p>
                                     <a href="dashboard.php" class="play-button">Tornar</a>`;
                gameOverlay.classList.remove('hidden');
            }
        }
    }

    function prepararJoc() {
        if (gameInterval) clearInterval(gameInterval); gameInterval = null;
        if (timeInterval) clearInterval(timeInterval); timeInterval = null;
        
        tecles.ArrowUp = false; 
        tecles.ArrowDown = false;
        
        puntuacioTotal = initialScore;
        tempsTotalSegons = initialTime;
        
        puntsIA = 0;
        puntsJugador = 0;
        videsJugador = configNivell.vides_jugador || 5;

        palaJugador.height = pala.height;
        palaIA.height = pala.height;
        palaJugador.y = canvas.height / 2 - palaJugador.height / 2;
        palaIA.y = canvas.height / 2 - palaIA.height / 2;
        
        if(infoNivell) infoNivell.textContent = `${nivellActual} / 3`;
        if(infoPunts) infoPunts.textContent = puntuacioTotal;
        if(infoMarcador) infoMarcador.textContent = `${puntsIA} - ${puntsJugador}`;
        if(infoVides) infoVides.textContent = videsJugador;
        
        const minutes = String(Math.floor(initialTime / 60)).padStart(2, '0');
        const seconds = String(initialTime % 60).padStart(2, '0');
        if(infoTemps) infoTemps.textContent = `${minutes}:${seconds}`;

        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');
        
        if (powerUpTimerDisplay) powerUpTimerDisplay.textContent = `${powerUpCountdown}s`;
        powerUpListItems.forEach(item => item.classList.remove('active'));
        
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.setLineDash([10, 15]); 
        ctx.moveTo(canvas.width / 2, 0);
        ctx.lineTo(canvas.width / 2, canvas.height);
        ctx.stroke();
        ctx.setLineDash([]); 
        
        boles = []; 
        resetBola(true); 
        dibuixarJoc(); 
    }

    // ================================================================
    // ========= FLUX DEL JOC I BUCLES =========
    // ================================================================

    function iniciarJocReal() {
        if (gameInterval) return; 

        startTime = Date.now() - (initialTime * 1000); 

        resetBola(false); 

        timeInterval = setInterval(actualitzarTemps, 1000);
        gameInterval = setInterval(bucleJoc, 1000 / 60); 

        activarControls();
        
        initPauseManager(
            jocPong_pausar, 
            jocPong_continuar, 
            jocPong_reiniciar, 
            jocPong_sortir
        );
    }

    function bucleJoc() {
        if (isPaused) return;

        // Aplicar efectes de Power-Up
        const speedMultiplier = 1.5;
        const slowMultiplier = 0.5;

        // Resetejem alçades abans d'aplicar efectes
        palaIA.height = pala.height;
        palaJugador.height = pala.height;

        if (activePowerUp === 'PALA_GEGANT_IA') {
            palaIA.height = pala.height * 1.5; 
        } else if (activePowerUp === 'PALA_PETITA_IA') {
            palaIA.height = pala.height * 0.5; 
        }

        if (activePowerUp === 'PALA_GEGANT_JUGADOR') {
            palaJugador.height = pala.height * 1.5; 
        }
        
        mourePales();

        for (let i = boles.length - 1; i >= 0; i--) {
            const bola = boles[i];
            
            // Apliquem efectes de velocitat a cada bola (només si no estan ja multiplicades)
            if (activePowerUp === 'SUPER_VELOCITAT' && Math.abs(bola.vx) < bola.baseVx * 1.5 + 1) { 
                bola.vx = (bola.vx > 0 ? 1 : -1) * bola.baseVx * speedMultiplier;
                bola.vy = (bola.vy > 0 ? 1 : -1) * bola.baseVy * speedMultiplier;
            } else if (activePowerUp === 'BOLA_LENTA') {
                 if (Math.abs(bola.vx) > bola.baseVx * 0.5 - 1) { // Evitem que es multipliqui infinitament lent
                    bola.vx = (bola.vx > 0 ? 1 : -1) * bola.baseVx * slowMultiplier;
                    bola.vy = (bola.vy > 0 ? 1 : -1) * bola.baseVy * slowMultiplier;
                 }
            } else {
                 // Si no hi ha power-up, s'assegura que torni a la base si n'ha sortit
                 if (Math.abs(bola.vx) > bola.baseVx * 1.05 || Math.abs(bola.vx) < bola.baseVx * 0.95) {
                    bola.vx = (bola.vx > 0 ? 1 : -1) * bola.baseVx;
                    bola.vy = (bola.vy > 0 ? 1 : -1) * bola.baseVy;
                 }
            }


            moureBola(bola); 
            comprovarCollisions(bola, i); 
        }
        
        dibuixarJoc(); 
        
        if (videsJugador <= 0) {
            finalitzarPartida(false); 
        }
    }

    function actualitzarTemps() {
        if (!startTime || isPaused) return; 
        
        const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
        tempsTotalSegons = elapsedTime; 
        
        const minutes = String(Math.floor(elapsedTime / 60)).padStart(2, '0');
        const seconds = String(elapsedTime % 60).padStart(2, '0');
        if(infoTemps) infoTemps.textContent = `${minutes}:${seconds}`;

        if (gameInterval && !isPaused && activePowerUp === 'NONE') { 
            powerUpCountdown--;
            if (powerUpTimerDisplay) powerUpTimerDisplay.textContent = `${powerUpCountdown}s`;

            if (powerUpCountdown <= 0) {
                activarPowerUpAleatori();
            }
        }
    }

    // ================================================================
    // ========= GESTIÓ DE POWER-UPS =========
    // ================================================================

    function activarPowerUpAleatori() {
        desactivarPowerUpActual(); 

        const randomIndex = Math.floor(Math.random() * powerUpList.length);
        activePowerUp = powerUpList[randomIndex];
        console.log("Power-up activat:", activePowerUp);
        
        powerUpListItems.forEach(item => {
            if (item.dataset.powerup === activePowerUp) {
                item.classList.add('active');
            }
        });
        if (powerUpTimerDisplay) powerUpTimerDisplay.textContent = `ACTIU!`;
        
        if (activePowerUp === 'MULTI-BOLA') {
            // Les boles mantenen la velocitat de l'original
            crearBolaExtra(true); 
            crearBolaExtra(true); 
        }
        if (activePowerUp === 'CLONS_DE_SOMBRA') {
            crearBolaExtra(false); // falsa
            crearBolaExtra(false); // falsa
            
            // --- CANVI NOU: Disparem la bola real en una direcció aleatòria ---
            const bolaOriginal = boles.find(b => b.esReal);
            if (bolaOriginal) {
                randomizeBolaVelocity(bolaOriginal);
            }
            // --- FI CANVI NOU ---
        }

        powerUpTimer = setTimeout(desactivarPowerUpActual, powerUpDuration * 1000);
    }

    function desactivarPowerUpActual() {
        if (powerUpTimer) clearTimeout(powerUpTimer);
        powerUpTimer = null;
        
        const lastPowerUp = activePowerUp;
        activePowerUp = 'NONE'; 

        // Resetejar velocitat de boles
        if (lastPowerUp === 'SUPER_VELOCITAT' || lastPowerUp === 'BOLA_LENTA') {
            boles.forEach(bola => { 
                bola.vx = (bola.vx > 0 ? 1 : -1) * bola.baseVx;
                bola.vy = (bola.vy > 0 ? 1 : -1) * bola.baseVy;
            });
        }
        
        // Netejar boles extra
        if (lastPowerUp === 'MULTI-BOLA' || lastPowerUp === 'CLONS_DE_SOMBRA') {
            boles = boles.filter(b => b.esReal).slice(0, 1);
            if (boles.length === 0) resetBola(true);
        }
        
        powerUpListItems.forEach(item => {
            item.classList.remove('active');
        });
        
        powerUpCountdown = powerUpNextTrigger; 
        if (powerUpTimerDisplay) powerUpTimerDisplay.textContent = `${powerUpCountdown}s`;
        console.log("Power-ups desactivats.");
    }

    // --- NOU: Funció per randomitzar la direcció d'una bola existent ---
    function randomizeBolaVelocity(bola) {
        const velocitatBase = configNivell.velocitat_bola || 5;
        let angle = Math.random() * Math.PI * 2; // Angle complet de 360 graus
        
        // Reinicialitzem la base de velocitat
        bola.baseVx = velocitatBase * Math.cos(angle);
        bola.baseVy = velocitatBase * Math.sin(angle);
        
        // Apliquem la nova velocitat (sense power-up extra de velocitat)
        bola.vx = bola.baseVx;
        bola.vy = bola.baseVy;
    }


    // ================================================================
    // ========= LÒGICA DEL JOC (Ara amb 'bola' com a paràmetre) ======
    // ================================================================

    function mourePales() {
        const velocitatJugador = configNivell.velocitat_jugador || 8;
        if (tecles.ArrowUp && palaJugador.y > 0) {
            palaJugador.y -= velocitatJugador;
        }
        if (tecles.ArrowDown && palaJugador.y < canvas.height - palaJugador.height) { 
            palaJugador.y += velocitatJugador;
        }
        palaJugador.y = Math.max(0, Math.min(palaJugador.y, canvas.height - palaJugador.height)); 

        const velocitatIA = configNivell.velocitat_ia || 5;
        const centrePalaIA = palaIA.y + palaIA.height / 2; 
        
        const bolaRealMesPropera = boles.filter(b => b.esReal).sort((a, b) => a.x - b.x)[0];
        
        if (bolaRealMesPropera) {
             if (bolaRealMesPropera.y > centrePalaIA + 10) { 
                palaIA.y += velocitatIA;
            } else if (bolaRealMesPropera.y < centrePalaIA - 10) {
                palaIA.y -= velocitatIA;
            }
        }
        palaIA.y = Math.max(0, Math.min(palaIA.y, canvas.height - palaIA.height)); 
    }
    
    function moureBola(bola) {
        bola.x += bola.vx;
        bola.y += bola.vy;
    }

    function comprovarCollisions(bola, index) {
        // 1. Rebot Parets (Dalt / Baix)
        if (bola.y - bola.r <= 0 || bola.y + bola.r >= canvas.height) {
            bola.vy *= -1;
            
            if (activePowerUp === 'REBOT_INVERS_VERTICAL') {
                bola.vx *= -1; 
            }

            bola.y = (bola.y - bola.r <= 0) ? bola.r : canvas.height - bola.r;
            crearParticules(bola.x, bola.y);
        }

        // 2. Punt (Esquerra / Dreta)
        if (bola.x - bola.r <= 0) { 
            if (bola.esReal) {
                puntsJugador++;
                puntuacioTotal += configNivell.punts_per_gol || 50;
                resetBola(false); 
                return; 
            } else {
                boles.splice(index, 1); 
                return;
            }
        } else if (bola.x + bola.r >= canvas.width) { 
            if (bola.esReal) {
                puntsIA++;
                videsJugador--; 
                resetBola(true); 
                return;
            } else {
                boles.splice(index, 1); 
                return;
            }
        }
        
        if(infoMarcador) infoMarcador.textContent = `${puntsIA} - ${puntsJugador}`;
        if(infoVides) infoVides.textContent = videsJugador;
        if(infoPunts) infoPunts.textContent = puntuacioTotal;

        // 3. Rebot Pala Jugador
        if (bola.vx > 0 && 
            bola.x + bola.r >= palaJugador.x && 
            bola.x + bola.r <= palaJugador.x + pala.width &&
            bola.y + bola.r >= palaJugador.y &&
            bola.y - bola.r <= palaJugador.y + palaJugador.height) { 
            
            if (!bola.esReal && activePowerUp === 'CLONS_DE_SOMBRA') {
                boles.splice(index, 1); 
                crearParticules(bola.x - bola.r, bola.y);
                return; 
            }
            
            let puntImpacteY = (bola.y - (palaJugador.y + palaJugador.height / 2)) / (palaJugador.height / 2); 
            let angle = puntImpacteY * (Math.PI / 4); 
            
            let velocitatTotal = Math.sqrt(bola.baseVx * bola.baseVx + bola.baseVy * bola.baseVy); 
            
            if (activePowerUp === 'SUPER_VELOCITAT') velocitatTotal *= 1.5;
            if (activePowerUp === 'BOLA_LENTA') velocitatTotal *= 0.5;
            
            bola.vx = -velocitatTotal * Math.cos(angle);
            bola.vy = velocitatTotal * Math.sin(angle);

            crearParticules(bola.x - bola.r, bola.y);
        }

        // 4. Rebot Pala IA
        if (bola.vx < 0 && 
            bola.x - bola.r <= palaIA.x + pala.width &&
            bola.x - bola.r >= palaIA.x &&
            bola.y + bola.r >= palaIA.y && 
            bola.y - bola.r <= palaIA.y + palaIA.height) {

            if (!bola.esReal && activePowerUp === 'CLONS_DE_SOMBRA') {
                boles.splice(index, 1); 
                crearParticules(bola.x + bola.r, bola.y);
                return; 
            }
            
            let puntImpacteY = (bola.y - (palaIA.y + palaIA.height / 2)) / (palaIA.height / 2);
            let angle = puntImpacteY * (Math.PI / 4);
            
            let velocitatTotal = Math.sqrt(bola.baseVx * bola.baseVx + bola.baseVy * bola.baseVy);
            
            if (activePowerUp === 'SUPER_VELOCITAT') velocitatTotal *= 1.5;
            if (activePowerUp === 'BOLA_LENTA') velocitatTotal *= 0.5;

            bola.vx = velocitatTotal * Math.cos(angle); 
            bola.vy = velocitatTotal * Math.sin(angle);
            
            crearParticules(bola.x + bola.r, bola.y);
        }
    }

    function resetBola(serveiIA) {
        boles = []; 
        
        const velocitatBase = configNivell.velocitat_bola || 5;
        let angle = Math.random() * (Math.PI / 4) - (Math.PI / 8);
        
        let novaBola = {
            x: canvas.width / 2,
            y: canvas.height / 2,
            r: 8,
            baseVx: velocitatBase * Math.cos(angle),
            baseVy: velocitatBase * Math.sin(angle),
            vx: 0, 
            vy: 0,
            esReal: true 
        };

        let velocitatActual = velocitatBase;
        if (activePowerUp === 'SUPER_VELOCITAT') {
            velocitatActual *= 1.5;
        } else if (activePowerUp === 'BOLA_LENTA') {
            velocitatActual *= 0.5;
        }

        novaBola.vx = velocitatActual * Math.cos(angle);
        novaBola.vy = velocitatActual * Math.sin(angle);
        
        if (serveiIA) {
            novaBola.vx *= 1; 
        } else {
            novaBola.vx *= -1; 
        }
        
        if (Math.random() > 0.5) {
            novaBola.vy *= -1;
        }
        
        boles.push(novaBola); 
        desactivarPowerUpActual();
    }
    
    function crearBolaExtra(esReal) {
        const bolaOriginal = boles.find(b => b.esReal) || { x: canvas.width / 2, y: canvas.height / 2, baseVx: 5, baseVy: 5 };
        
        const velocitatBase = configNivell.velocitat_bola || 5;
        let angle = Math.random() * Math.PI * 2; 
        
        let novaBola = {
            x: bolaOriginal.x,
            y: bolaOriginal.y,
            r: 8,
            baseVx: velocitatBase * Math.cos(angle),
            baseVy: velocitatBase * Math.sin(angle),
            vx: 0, 
            vy: 0,
            esReal: esReal 
        };

        let velocitatActual = velocitatBase;
        if (activePowerUp === 'SUPER_VELOCITAT') {
            velocitatActual *= 1.5;
        } else if (activePowerUp === 'BOLA_LENTA') {
            velocitatActual *= 0.5;
        }

        novaBola.vx = velocitatActual * Math.cos(angle);
        novaBola.vy = velocitatActual * Math.sin(angle);

        boles.push(novaBola);
    }


    function dibuixarJoc() {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.strokeStyle = '#333';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.setLineDash([10, 15]);
        ctx.moveTo(canvas.width / 2, 0);
        ctx.lineTo(canvas.width / 2, canvas.height);
        ctx.stroke();
        ctx.setLineDash([]); 

        // Dibuixar Pales amb colors d'efecte
        ctx.fillStyle = '#FFF'; 
        if (activePowerUp === 'PALA_GEGANT_IA') ctx.fillStyle = '#FF4136'; 
        if (activePowerUp === 'PALA_PETITA_IA') ctx.fillStyle = '#2ECC40'; 
        ctx.fillRect(palaIA.x, palaIA.y, pala.width, palaIA.height); 
        
        ctx.fillStyle = '#FFF'; 
        if (activePowerUp === 'PALA_GEGANT_JUGADOR') ctx.fillStyle = '#2ECC40'; 
        ctx.fillRect(palaJugador.x, palaJugador.y, pala.width, palaJugador.height);


        // Dibuixar Boles (Amb efectes)
        boles.forEach(bola => {
            ctx.fillStyle = '#FFF';
            ctx.globalAlpha = 1.0;

            if (bola.esReal) {
                if (activePowerUp === 'SUPER_VELOCITAT') ctx.fillStyle = '#FF4136';
                if (activePowerUp === 'BOLA_LENTA') ctx.fillStyle = '#7FDBFF';
                if (activePowerUp === 'BOLA_FANTASMA') {
                    ctx.globalAlpha = 0.3; 
                    ctx.fillStyle = '#AAA'; 
                }
            } 
            // === CANVI NOU: Estil per CLONS_DE_SOMBRA ===
            else {
                ctx.globalAlpha = 0.7; // Més opac (0.7)
                ctx.fillStyle = '#E0E0E0'; // Més proper al blanc
            }
            // === FI CANVI NOU ===

            ctx.beginPath();
            ctx.arc(bola.x, bola.y, bola.r, 0, Math.PI * 2);
            ctx.fill();
        });
        
        ctx.globalAlpha = 1.0; 

        // Dibuixar text d'avís del Power-Up
        if (activePowerUp !== 'NONE' && !isPaused) {
            ctx.font = "24px 'Courier New', Courier, monospace";
            ctx.fillStyle = "rgba(255, 255, 255, 0.5)";
            ctx.textAlign = "center";
            const powerUpText = activePowerUp.replace(/_/g, ' '); 
            ctx.fillText(powerUpText, canvas.width / 2, 50);
        }
    }

    // ================================================================
    // ========= PARTÍCULES i GESTIÓ DE PAUSA (Sense Canvis) =========
    // ================================================================

    function crearParticules(x, y) {
        if (!particleContainer) return;
        const numParticules = 10;
        for (let i = 0; i < numParticules; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            particleContainer.appendChild(p);
            const angle = Math.random() * Math.PI * 2;
            const velocitat = Math.random() * 3 + 1;
            const destX = Math.cos(angle) * velocitat * 20; 
            const destY = Math.sin(angle) * velocitat * 20;
            p.style.left = `${x}px`;
            p.style.top = `${y}px`;
            setTimeout(() => {
                p.style.transform = `translate(${destX}px, ${destY}px)`;
                p.style.opacity = 0;
            }, 10);
            setTimeout(() => {
                p.remove();
            }, 510);
        }
    }

    let isPaused = false;
    let timeRemainingOnPowerUp = 0; 

    function jocPong_pausar() {
        if (isPaused) return;
        isPaused = true;
        pauseStartTime = Date.now();
        
        clearInterval(gameInterval); gameInterval = null;
        clearInterval(timeInterval); timeInterval = null;

        if (powerUpTimer) {
            clearTimeout(powerUpTimer);
            timeRemainingOnPowerUp = (powerUpDuration * 1000) - (Date.now() - (startTime + (powerUpCountdown * 1000)));
        }

        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.remove('hidden');
    }

    function jocPong_continuar() {
        if (!isPaused) return;
        isPaused = false;
        
        const pausedDuration = Date.now() - pauseStartTime;
        startTime += pausedDuration;

        gameInterval = setInterval(bucleJoc, 1000 / 60);
        timeInterval = setInterval(actualitzarTemps, 1000);

        if (activePowerUp !== 'NONE' && timeRemainingOnPowerUp > 0) {
            powerUpTimer = setTimeout(desactivarPowerUpActual, timeRemainingOnPowerUp);
            timeRemainingOnPowerUp = 0; 
        }

        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');
    }

    function jocPong_reiniciar() {
        if (powerUpTimer) clearTimeout(powerUpTimer);
        window.location.href = `juego.php?joc_id=${jocId}&nivell=${nivellActual}&score=${initialScore}&time=${initialTime}`;
    }

    function jocPong_sortir() {
        if (powerUpTimer) clearTimeout(powerUpTimer);
        window.location.href = 'dashboard.php';
    }

    function activarControls() {
         document.addEventListener('keydown', (event) => {
            if (isPaused || !gameInterval) {
                if (event.code !== 'Escape') return; 
            }
            if (event.code === 'ArrowUp') {
                tecles.ArrowUp = true;
                event.preventDefault();
            }
            if (event.code === 'ArrowDown') {
                tecles.ArrowDown = true;
                event.preventDefault();
            }
        });
         document.addEventListener('keyup', (event) => {
            if (event.code === 'ArrowUp') tecles.ArrowUp = false;
            if (event.code === 'ArrowDown') tecles.ArrowDown = false;
        });
    }

    function finalitzarPartida(haGuanyatNivell) {
        isPaused = true; 
        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');
        
        if (gameInterval) clearInterval(gameInterval); gameInterval = null;
        if (timeInterval) clearInterval(timeInterval); timeInterval = null;
        if (powerUpTimer) clearTimeout(powerUpTimer);
        powerUpTimer = null;
        activePowerUp = 'NONE';
        boles = [];

        const tempsAquestaPartida = tempsTotalSegons - initialTime;
        
        enviarDadesPartida(
            jocId, 
            nivellActual, 
            puntuacioTotal, 
            tempsAquestaPartida, 
            haGuanyatNivell,
            { "gols_favor": puntsJugador, "gols_contra": puntsIA } 
        );

        const gameOverlay = document.getElementById('game-overlay');
        const startButton = document.getElementById('start-button');
        const exitLevelButton = document.getElementById('exit-level-button');
        const controlsInfo = gameOverlay.querySelector('p.controls-info');
        const overlayTitle = gameOverlay.querySelector('.overlay-title');
        
        if (!gameOverlay || !startButton || !exitLevelButton || !controlsInfo || !overlayTitle) {
            console.error("No es poden trobar els elements de l'overlay de fi de partida.");
            return;
        }
        gameOverlay.classList.remove('hidden');
        gameOverlay.classList.remove('show-two-buttons'); 
        if (exitLevelButton) exitLevelButton.style.display = 'none'; 
        let missatge = '';
        let botoText = '';
        let botoAccio = null;
        const gamePath = `juego.php`;
        const dashboardPath = `dashboard.php`;

        if (!haGuanyatNivell) { 
            missatge = `Game Over! (Nivell ${nivellActual})<br>Punts: ${puntuacioTotal}`;
            botoText = '<i class="fas fa-redo"></i> Tornar a intentar';
            botoAccio = () => {
                window.location.href = `${gamePath}?joc_id=${jocId}&nivell=${nivellActual}&score=${initialScore}&time=${initialTime}`;
            };
            if (exitLevelButton) exitLevelButton.style.display = 'none';

        } else { 
            if (nivellActual < 3) {
                missatge = `Nivell ${nivellActual} Superat!<br>Punts: ${puntuacioTotal}`;
                botoText = `Continuar (Nivell ${nivellActual + 1}) <i class="fas fa-arrow-right"></i>`;
                botoAccio = () => {
                    window.location.href = `${gamePath}?joc_id=${jocId}&nivell=${nivellActual + 1}&score=${puntuacioTotal}&time=${tempsTotalSegons}`;
                };
                if (exitLevelButton) {
                    exitLevelButton.style.display = 'inline-flex';
                    exitLevelButton.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sortir';
                    exitLevelButton.href = dashboardPath; 
                }
                gameOverlay.classList.add('show-two-buttons');

            } else { 
                missatge = `Enhorabona! Joc Completat!<br>Puntuació Final: ${puntuacioTotal}`;
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

    async function enviarDadesPartida(jocId, nivellJugat, puntuacio, tempsSegons, superat, dadesExtra) {
        const apiPath = 'api/guardar_partida.php'; 
        console.log("Enviant dades:", {jocId, nivellJugat, puntuacio, tempsSegons, superat, dadesExtra});
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