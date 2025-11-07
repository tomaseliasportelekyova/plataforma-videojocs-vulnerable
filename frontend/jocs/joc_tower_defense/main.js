// Arxiu: frontend/jocs/joc_tower_defense/main.js

document.addEventListener('DOMContentLoaded', () => {

    // --------- Referències DOM (GDD + game_content.html) ---------
    const pantalla = document.getElementById('pantalla');
    const gameWorld = document.getElementById('td-game-world');
    const gameLayer = document.createElement('div');
    gameLayer.id = 'game-layer';
    gameWorld.appendChild(gameLayer);
    
    // === NOU: Referència al comptador de Nivell ===
    const levelCounterDisplay = document.getElementById('level-counter');

    const pathElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    pathElement.id = 'enemy-path';
    gameWorld.insertBefore(pathElement, gameLayer);
    
    const turretGhost = document.createElement('div');
    turretGhost.id = 'turret-ghost';
    turretGhost.style.display = 'none';
    const rangeIndicator = document.createElement('div');
    rangeIndicator.className = 'turret-range-indicator';
    turretGhost.appendChild(rangeIndicator);
    gameWorld.appendChild(turretGhost);

    const uiPanel = document.getElementById('td-ui-panel');
    const shopItemsWrapper = document.querySelector('.shop-items-wrapper');
    const shopButtons = shopItemsWrapper.querySelectorAll('.shop-button');
    const creditsDisplay = document.getElementById('info-credits');
    
    // === PANNELL DE MILLORA ===
    const upgradePanel = document.getElementById('selected-turret-panel');
    const sellBtn = document.getElementById('sell-turret-button');
    const panelTurretName = document.getElementById('selected-turret-name');
    const panelTurretLevel = document.getElementById('selected-turret-level');
    const panelTurretDamage = document.getElementById('selected-turret-damage');
    const panelTurretSpeed = document.getElementById('selected-turret-speed');
    const upgradeBtnA = document.getElementById('upgrade-path-A-button');
    const upgradeBtnB = document.getElementById('upgrade-path-B-button');

    const onadaDisplay = document.getElementById('info-onada');
    const puntuacioDisplay = document.getElementById('info-puntuacio');
    const killsDisplay = document.getElementById('info-kills');
    const videsDisplay = document.getElementById('info-vides');
    const tempsDisplay = document.getElementById('info-temps');

    // --------- Variables Globals del Joc ---------
    let configNivell = {};
    let jocId = 1; let nivellActual = 1;
    let gameLoopInterval = null; let waveLoopInterval = null; let countdownInterval = null;
    let gameState = 'loading';
    
    let credits = 0;
    let puntuacioTotal = 0;
    let videsBase = 0;
    let onadaActual = 0;
    let killsTotals = 0;
    let tempsTotalSegons = 0;

    let pathCoords = [];
    let pathWidth = 40;
    
    let enemics = [];
    let torretes = [];
    let projectils = [];

    let placingTurretType = null;
    let placingTurretStats = {};
    let isPlacementValid = false;
    let currentMousePos = { x: 0, y: 0 };
    
    let selectedTurret = null;
    
    async function carregarNivell() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            jocId = parseInt(urlParams.get('joc_id')) || 23;
            nivellActual = parseInt(urlParams.get('nivell')) || 1;
            const resposta = await fetch(`api/get_nivell.php?joc_id=${jocId}&nivell=${nivellActual}`);
            if (!resposta.ok) throw new Error(`Error ${resposta.status}: No s'ha pogut carregar el nivell.`);
            configNivell = await resposta.json();
            
            // === NOU: Comptador de nivell ===
            if (levelCounterDisplay) levelCounterDisplay.textContent = `Nivell ${nivellActual} / 10`;

            prepararJoc();
            iniciarFluxJoc(iniciarJocTD); // Funció de game_flow.js
        } catch (error) {
            console.error("Error carregant nivell:", error);
            const gameOverlay = document.getElementById('game-overlay');
            gameOverlay.innerHTML = `<h2 class="overlay-title" style="color: red;">Error</h2><p class="controls-info">${error.message}</p><a href="dashboard.php" class="play-button">Tornar</a>`;
            gameOverlay.classList.remove('hidden');
        }
    }

    function prepararJoc() {
        enemics = []; torretes = []; projectils = [];
        onadaActual = 0; killsTotals = 0;
        puntuacioTotal = 0; 
        
        credits = configNivell.credits_inicials;
        videsBase = configNivell.vides_base;
        pathCoords = configNivell.path;
        pathWidth = configNivell.path_width || 40;
        
        dibuixarCami(pathCoords, pathWidth);
        
        updateInfoPanel();
        updateShopButtons(); 
        setupUIListeners();
        
        gameState = 'between_waves';
        tempsDisplay.textContent = formatTime(30);
    }
    
    function iniciarJocTD() {
        gameState = 'between_waves';
        initPauseManager(jocTD_pausar, jocTD_continuar, jocTD_reiniciar, jocTD_sortir);
        gameLoopInterval = setInterval(bucleJoc, 1000 / 60);
        iniciarCompteEnrereOnada(30);
    }

    function bucleJoc() {
        if (isPaused || gameState === 'game_over') return;

        // === NOU: Bucle d'Aures i Detecció (Pas 0) ===
        // 0.1: Resetejem estats temporals (invisibilitat, buffs)
        for (const enemic of enemics) {
            if (enemic.type === 'Phisher') enemic.isInvisible = true;
            if (enemic.isBuffed) {
                enemic.isBuffed = false;
                enemic.velocitat = enemic.velocitatOriginal; // Resetejem buff velocitat
            }
        }
        
        // 0.2: Apliquem Aures (Detectors i Adware)
        for (const torreta of torretes) {
            if (torreta.type === 'Detector' && torreta.auraRadius > 0) {
                for (const enemic of enemics) {
                    if (enemic.isInvisible && torreta.distanciaA(enemic) <= torreta.auraRadius) {
                        enemic.isInvisible = false; // El fem visible!
                    }
                }
            }
        }
        for (const enemic of enemics) {
            if (enemic.type === 'Adware' && enemic.auraRadius > 0) {
                for (const altreEnemic of enemics) {
                    if (enemic !== altreEnemic && enemic.distanciaA(altreEnemic) <= enemic.auraRadius) {
                        altreEnemic.isBuffed = true;
                        altreEnemic.velocitat = altreEnemic.velocitatOriginal * 1.25; // Buff 25% GDD
                    }
                }
            }
        }
        // === FI NOU PAS 0 ===


        // 1. Moure Enemics
        for (let i = enemics.length - 1; i >= 0; i--) {
            const enemic = enemics[i];
            if (enemic.viu) {
                enemic.moure(); // 'moure' ara també actualitza l'estat visual
                if (enemic.haArribatAlFinal()) {
                    videsBase--;
                    enemic.morir();
                    if (videsBase <= 0) {
                        finalitzarPartida(false);
                    }
                }
            } else {
                enemics.splice(i, 1);
            }
        }
        
        // 2. Moure Projectils
        for (let i = projectils.length - 1; i >= 0; i--) {
            const projectil = projectils[i];
            if (projectil.viu) {
                const creditsGuanyats = projectil.moure();
                if (creditsGuanyats > 0) {
                    credits += creditsGuanyats;
                    puntuacioTotal += creditsGuanyats;
                    killsTotals++;
                    updateShopButtons(); 
                    
                    // === NOU: Lògica "Worm" ===
                    // Comprovem si l'enemic que hem matat era un 'Worm'
                    if (projectil.target && !projectil.target.viu && projectil.target.type === 'Worm') {
                        // Creem 3 "Bugs" on ha mort (GDD 4.2)
                        for (let b = 0; b < 3; b++) {
                            const nouBug = new Enemic(gameLayer, pathCoords, 'Bot'); // Usem "Bot" (Vides 1)
                            // El posicionem on era el Worm
                            nouBug.x = projectil.target.x + (Math.random() * 20 - 10);
                            nouBug.y = projectil.target.y + (Math.random() * 20 - 10);
                            // Li diem que continuï des del punt del path on era el Worm
                            nouBug.puntDelPath = projectil.target.puntDelPath; 
                            enemics.push(nouBug);
                        }
                    }
                    // === FI NOU ===
                }
            } else {
                projectils.splice(i, 1);
            }
        }

        // 3. Torretes ataquen (Ara ignoren enemics invisibles)
        for (const torreta of torretes) {
            if (torreta.viu) {
                torreta.update(enemics, projectils);
            }
        }
        
        // 4. Actualitzar el "fantasma"
        if (placingTurretType) {
            updateTurretGhost(currentMousePos.x, currentMousePos.y);
        }
        
        // 5. Actualitzar UI
        updateInfoPanel();
    }
    
    function iniciarCompteEnrereOnada(segons) {
        if (countdownInterval) clearInterval(countdownInterval);
        let tempsRestant = segons;
        tempsDisplay.textContent = formatTime(tempsRestant);
        countdownInterval = setInterval(() => {
            if (isPaused) return;
            tempsRestant--;
            tempsDisplay.textContent = formatTime(tempsRestant);
            if (tempsRestant <= 0) {
                clearInterval(countdownInterval);
                iniciarOnada();
            }
        }, 1000);
    }

    function iniciarOnada() {
        if (onadaActual >= configNivell.onades.length) return;
        gameState = 'wave';
        const dadesOnada = configNivell.onades[onadaActual];
        let enemicsASpaunejar = [...dadesOnada.enemics];
        
        waveLoopInterval = setInterval(() => {
            if (isPaused) return;
            if (enemicsASpaunejar.length > 0) {
                const tipusEnemic = enemicsASpaunejar.shift();
                const nouEnemic = new Enemic(gameLayer, pathCoords, tipusEnemic);
                enemics.push(nouEnemic);
            } else {
                clearInterval(waveLoopInterval);
                waveLoopInterval = null;
                comprovarFiOnada();
            }
        }, dadesOnada.interval_ms);
        onadaActual++;
        updateInfoPanel();
    }

    function comprovarFiOnada() {
        if (isPaused) { setTimeout(comprovarFiOnada, 500); return; }
        if (waveLoopInterval) return;
        if (enemics.some(e => e.viu)) {
            setTimeout(comprovarFiOnada, 500);
            return;
        }
        gameState = 'between_waves';
        // Comprovem si era l'última onada del nivell
        if (onadaActual >= configNivell.onades.length) {
            finalitzarPartida(true);
        } else {
            iniciarCompteEnrereOnada(15);
        }
    }

    // --------- 4. Gestió de la UI (MODIFICADA) ---------
    
    function setupUIListeners() {
        shopButtons.forEach(btn => {
            const tipus = btn.dataset.type;
            const cost = parseInt(btn.dataset.cost);
            const iconWrapper = btn.querySelector('.svg-icon-wrapper');
            const tempTurret = new Torreta(document.createElement('div'), 0, 0, tipus);
            if (iconWrapper) iconWrapper.innerHTML = tempTurret.element.innerHTML;
            
            btn.addEventListener('click', () => {
                if (placingTurretType === tipus) {
                    cancelPlacingMode();
                    return;
                }
                if (credits >= cost) {
                    placingTurretType = tipus;
                    placingTurretStats = getTurretStats(tipus);
                    const ghostContent = new Torreta(document.createElement('div'), 0, 0, tipus);
                    turretGhost.innerHTML = '';
                    rangeIndicator.style.width = `${placingTurretStats.abast * 2}px`;
                    rangeIndicator.style.height = `${placingTurretStats.abast * 2}px`;
                    ghostContent.element.style.width = `${placingTurretStats.mida}px`;
                    ghostContent.element.style.height = `${placingTurretStats.mida}px`;
                    turretGhost.appendChild(ghostContent.element);
                    turretGhost.appendChild(rangeIndicator);
                    ghostContent.morir();
                    turretGhost.style.display = 'block';
                    gameWorld.style.cursor = 'crosshair';
                }
            });
            tempTurret.morir();
        });
        
        gameWorld.addEventListener('mousemove', (e) => {
            const rect = gameWorld.getBoundingClientRect();
            currentMousePos.x = e.clientX - rect.left;
            currentMousePos.y = e.clientY - rect.top;
        });
        gameWorld.addEventListener('mouseleave', () => { if (placingTurretType) turretGhost.style.display = 'none'; });
        gameWorld.addEventListener('mouseenter', () => { if (placingTurretType) turretGhost.style.display = 'block'; });
        gameWorld.addEventListener('click', () => {
            if (placingTurretType && isPlacementValid) {
                placeTurret(currentMousePos.x, currentMousePos.y);
            }
        });
        
        // Listener de Vendre
        sellBtn.addEventListener('click', () => {
            if (selectedTurret) {
                const retorn = selectedTurret.vendre();
                credits += Math.floor(retorn);
                torretes = torretes.filter(t => t !== selectedTurret);
                hideUpgradePanel();
                updateShopButtons(); 
            }
        });

        // Listeners de Millora
        if (upgradeBtnA) {
            upgradeBtnA.addEventListener('click', () => {
                handleUpgrade('pathA');
            });
        }
        if (upgradeBtnB) {
            upgradeBtnB.addEventListener('click', () => {
                handleUpgrade('pathB');
            });
        }

        document.addEventListener('click', (e) => {
            if (selectedTurret && !upgradePanel.contains(e.target) && !selectedTurret.element.contains(e.target)) {
                hideUpgradePanel();
            }
        }, true);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && placingTurretType) cancelPlacingMode(); });
        gameWorld.addEventListener('contextmenu', (e) => { e.preventDefault(); if (placingTurretType) cancelPlacingMode(); });
    }
    
    function handleUpgrade(path) {
        if (!selectedTurret) return;
        const costMillora = selectedTurret.costMillora;
        if (credits < costMillora) return; 

        credits -= costMillora;
        const milloraAplicada = selectedTurret.aplicarMillora(path);
        
        if (milloraAplicada) {
            updateShopButtons(); 
            showUpgradePanel(selectedTurret); // Actualitza el panell amb els nous stats
        } else {
            credits += costMillora;
        }
    }
    
    function placeTurret(x, y) {
        credits -= placingTurretStats.cost;
        const novaTorreta = new Torreta(gameLayer, x, y, placingTurretType);
        torretes.push(novaTorreta);
        novaTorreta.element.addEventListener('click', (e) => {
            e.stopPropagation();
            showUpgradePanel(novaTorreta);
        });
        cancelPlacingMode();
        updateShopButtons(); 
    }
    
    function updateTurretGhost(x, y) {
        turretGhost.style.left = `${x}px`;
        turretGhost.style.top = `${y}px`;
        isPlacementValid = checkPlacementValidity(x, y);
        if (isPlacementValid) {
            turretGhost.classList.remove('placing-invalid');
            turretGhost.classList.add('placing-valid');
        } else {
            turretGhost.classList.remove('placing-valid');
            turretGhost.classList.add('placing-invalid');
        }
    }
    function checkPlacementValidity(x, y) {
        if (credits < placingTurretStats.cost) return false;
        if (isPointOnPath(x, y, placingTurretStats.mida / 2)) return false;
        for (const t of torretes) {
            const dist = t.distanciaA({x: x, y: y});
            if (dist < (t.mida / 2) + (placingTurretStats.mida / 2)) return false;
        }
        const halfSize = placingTurretStats.mida / 2;
        if (x < halfSize || x > (1000 - halfSize) || y < halfSize || y > (600 - halfSize)) return false;
        return true;
    }
    
    function isPointOnPath(x, y, turretRadius) {
        const minDistance = turretRadius + (pathWidth / 2);
        for (let i = 0; i < pathCoords.length - 1; i++) {
            const p1 = pathCoords[i];
            const p2 = pathCoords[i+1];
            const L2 = Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2);
            let t = ((x - p1.x) * (p2.x - p1.x) + (y - p1.y) * (p2.y - p1.y)) / L2;
            t = Math.max(0, Math.min(1, t));
            const closestX = p1.x + t * (p2.x - p1.x);
            const closestY = p1.y + t * (p2.y - p1.y);
            const dx = x - closestX;
            const dy = y - closestY;
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < minDistance) return true;
        }
        return false;
    }

    function cancelPlacingMode() {
        placingTurretType = null;
        gameWorld.style.cursor = 'default';
        turretGhost.style.display = 'none';
    }

    function showUpgradePanel(torreta) {
        if (!upgradeBtnA || !upgradeBtnB) {
            console.error("No s'han trobat els botons de millora 'upgrade-path-A-button' o 'upgrade-path-B-button'. Revisa el 'game_content.html'.");
            return;
        }

        selectedTurret = torreta;
        const costMillora = torreta.costMillora;
        const potMillorar = credits >= costMillora;
        const valorVenda = Math.floor(torreta.costTotal * 0.7);

        // 1. Actualitzar Textos
        if (panelTurretName) panelTurretName.textContent = torreta.type;
        if (panelTurretLevel) panelTurretLevel.textContent = torreta.nivell;
        if (panelTurretDamage) panelTurretDamage.textContent = torreta.mal;
        if (panelTurretSpeed) panelTurretSpeed.textContent = (torreta.cadencia > 0) ? (60 / torreta.cadencia).toFixed(1) : 'N/A';
        if (sellBtn) sellBtn.innerHTML = `Vendre (Retorn: ${valorVenda} <i class="fas fa-coins"></i>)`;

        // 2. Noms dels Camins (GDD)
        let pathAName = "Millora A"; let pathBName = "Millora B";
        switch (torreta.type) {
            case 'Sentry': pathAName = "Cadència"; pathBName = "Dany"; break;
            case 'Cano': pathAName = "Explosió"; pathBName = "Velocitat"; break;
            case 'Bit-Freezer': pathAName = "Intensitat"; pathBName = "Àrea"; break;
            case 'Detector': pathAName = "Utilitat"; pathBName = "Economia"; break;
        }

        // 3. Lògica Botons
        if (torreta.nivell >= torreta.maxNivell) {
            upgradeBtnA.style.display = 'none';
            upgradeBtnB.style.display = 'block'; 
            upgradeBtnB.disabled = true;
            upgradeBtnB.innerHTML = 'NIVELL MÀXIM';
        } else if (torreta.upgradePath === null) {
            upgradeBtnA.style.display = 'block';
            upgradeBtnB.style.display = 'block';
            upgradeBtnA.innerHTML = `${pathAName} (Cost: ${costMillora} <i class="fas fa-coins"></i>)`;
            upgradeBtnB.innerHTML = `${pathBName} (Cost: ${costMillora} <i class="fas fa-coins"></i>)`;
            upgradeBtnA.disabled = !potMillorar;
            upgradeBtnB.disabled = !potMillorar;
        } else if (torreta.upgradePath === 'pathA') {
            upgradeBtnA.style.display = 'block';
            upgradeBtnB.style.display = 'none'; 
            upgradeBtnA.innerHTML = `Millorar ${pathAName} (Cost: ${costMillora} <i class="fas fa-coins"></i>)`;
            upgradeBtnA.disabled = !potMillorar;
        } else if (torreta.upgradePath === 'pathB') {
            upgradeBtnA.style.display = 'none'; 
            upgradeBtnB.style.display = 'block';
            upgradeBtnB.innerHTML = `Millorar ${pathBName} (Cost: ${costMillora} <i class="fas fa-coins"></i>)`;
            upgradeBtnB.disabled = !potMillorar;
        }

        // 4. Posicionar
        upgradePanel.style.display = 'block';
        upgradePanel.style.left = `${torreta.x + 30}px`;
        upgradePanel.style.top = `${torreta.y - 30}px`;
    }

    function hideUpgradePanel() {
        selectedTurret = null;
        upgradePanel.style.display = 'none';
    }
    
    function updateInfoPanel() {
        onadaDisplay.textContent = `${onadaActual} / ${configNivell.onades.length}`;
        creditsDisplay.textContent = credits;
        puntuacioDisplay.textContent = puntuacioTotal;
        killsDisplay.textContent = killsTotals;
        videsDisplay.textContent = videsBase;
    }
    
    function updateShopButtons() {
        shopButtons.forEach(btn => {
            const cost = parseInt(btn.dataset.cost);
            btn.disabled = (credits < cost);
        });
    }

    function getTurretStats(type) {
        if (type === 'Sentry') return { cost: 100, mida: 40, abast: 150 };
        if (type === 'Cano') return { cost: 250, mida: 40, abast: 200 };
        if (type === 'Bit-Freezer') return { cost: 150, mida: 40, abast: 120 };
        if (type === 'Detector') return { cost: 75, mida: 40, abast: 200 };
        return { cost: 9999, mida: 40, abast: 0 };
    }
    function dibuixarCami(pathData, width) {
        if (pathData.length < 2) return;
        pathElement.innerHTML = '';
        let d = `M ${pathData[0].x} ${pathData[0].y}`;
        for (let i = 1; i < pathData.length; i++) {
            d += ` L ${pathData[i].x} ${pathData[i].y}`;
        }
        const pathSVG = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        pathSVG.setAttribute('d', d);
        pathSVG.style.strokeWidth = `${width}px`;
        pathElement.appendChild(pathSVG);
    }
    function formatTime(segons) {
        const min = String(Math.floor(segons / 60)).padStart(2, '0');
        const seg = String(segons % 60).padStart(2, '0');
        return `${min}:${seg}`;
    }

    // (Contracte de Pausa)
    let isPaused = false;
    function jocTD_pausar() { isPaused = true; if (countdownInterval) clearInterval(countdownInterval); countdownInterval=null; if (waveLoopInterval) clearInterval(waveLoopInterval); waveLoopInterval=null; }
    function jocTD_continuar() { 
        isPaused = false; 
        if (gameState === 'between_waves') {
            const tempsRestant = parseInt(tempsDisplay.textContent.split(':')[1]) || 30;
            iniciarCompteEnrereOnada(tempsRestant);
        }
    }
    function jocTD_reiniciar() { window.location.reload(); }
    function jocTD_sortir() { window.location.href = 'dashboard.php'; }

    // (Final de Partida)
    function finalitzarPartida(haGuanyat) {
        gameState = 'game_over';
        jocTD_pausar();
        hideUpgradePanel();
        tempsTotalSegons = 300; // TODO: Implementar cronòmetre
        
        enviarDadesPartida(
            jocId, nivellActual, puntuacioTotal, tempsTotalSegons, haGuanyat,
            { "kills": killsTotals, "torretes_venudes": 0 }
        );
        
        const overlay = document.getElementById('game-overlay');
        const title = overlay.querySelector('.overlay-title');
        const startBtn = overlay.querySelector('#start-button');
        const exitBtn = overlay.querySelector('#exit-level-button');

        if (haGuanyat) {
            title.innerHTML = `Nivell ${nivellActual} Superat!`;
            // === NOU: Comprovem si hi ha un nivell 10 ===
            if (nivellActual >= 10) {
                 startBtn.innerHTML = `Joc Completat! <i class="fas fa-trophy"></i>`;
                 startBtn.onclick = () => { window.location.href = 'dashboard.php'; };
            } else {
                 startBtn.innerHTML = `Continuar (Nivell ${nivellActual + 1}) <i class="fas fa-arrow-right"></i>`;
                 startBtn.onclick = () => { window.location.href = `juego.php?joc_id=${jocId}&nivell=${nivellActual + 1}`; };
            }
            exitBtn.style.display = 'inline-flex';
        } else {
            videsDisplay.textContent = 0;
            title.innerHTML = `Game Over (Onada ${onadaActual})`;
            startBtn.innerHTML = `<i class="fas fa-redo"></i> Tornar a intentar`;
            startBtn.onclick = () => jocTD_reiniciar();
            exitBtn.style.display = 'none';
        }
        overlay.classList.remove('hidden');
    }
    
    async function enviarDadesPartida(jocId, nivell, punts, temps, superat, extra) {
        try {
            await fetch('api/guardar_partida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    joc_id: jocId, nivell_jugat: nivell,
                    puntuacio_obtinguda: punts,
                    durada_segons: temps,
                    nivell_superat: superat, dades_extra: extra
                })
            });
        } catch (error) { console.error("Error al guardar partida:", error); }
    }

    // --------- INICI ---------
    carregarNivell();
});