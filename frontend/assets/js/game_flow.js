// Aquest arxiu és la "plantilla" genèrica per al flux d'inici del joc

/**
 * Inicia el flux de pre-joc (Overlays, Instruccions, Comptador).
 * @param {function} startGameCallback - La funció que s'ha de cridar quan el joc real ha de començar.
 */
function iniciarFluxJoc(startGameCallback) {
    
    // 1. Trobar tots els elements de la plantilla HTML
    const gameOverlay = document.getElementById('game-overlay');
    const startButton = document.getElementById('start-button');
    const instructionsOverlay = document.getElementById('instructions-overlay');
    const countdownTimer = document.getElementById('countdown-timer');
    const skipButton = document.getElementById('skip-instructions');

    let countdownInterval = null; // Variable per guardar el comptador

    // Comprovació de seguretat
    if (!gameOverlay || !startButton || !instructionsOverlay || !countdownTimer || !skipButton) {
        console.error("Error: Falten elements del DOM per al flux del joc.");
        // Si falten elements, simplement comencem el joc directament
        startGameCallback();
        return;
    }

    // 2. Funció per mostrar les instruccions
    function mostrarInstruccions() {
        // *** AQUESTA ÉS LA CORRECCIÓ AL TEU ERROR ***
        // Amaguem l'Overlay 1 ("Començar")
        gameOverlay.classList.add('hidden');
        
        // Mostrem l'Overlay 2 ("Instruccions")
        instructionsOverlay.classList.remove('hidden');

        // Configurem el botó d'Omitir
        skipButton.onclick = iniciarJocReal;

        // Iniciem el comptador de 10 segons
        let tempsRestant = 5;
        countdownTimer.textContent = tempsRestant;

        // Neteja qualsevol comptador antic abans de començar un de nou
        if (countdownInterval) clearInterval(countdownInterval);

        countdownInterval = setInterval(() => {
            tempsRestant--;
            countdownTimer.textContent = tempsRestant;
            
            if (tempsRestant <= 0) {
                iniciarJocReal(); // Comença el joc automàticament
            }
        }, 1000);
    }

    // 3. Funció per començar el joc realment
    function iniciarJocReal() {
        // Parem el comptador (MOLT IMPORTANT!)
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = null;

        // Amaguem TOTS els overlays (per seguretat)
        gameOverlay.classList.add('hidden');
        instructionsOverlay.classList.add('hidden');

        // 4. Cridem a la funció específica del joc (que rebem com a paràmetre)
        startGameCallback();
    }

    // 5. Configuració inicial:
    //    El botó "Començar Partida" de l'Overlay 1...
    //    ...crida a "mostrarInstruccions".
    startButton.onclick = mostrarInstruccions;

    // Assegurem que l'Overlay 1 sigui visible i l'Overlay 2 estigui amagat
    gameOverlay.classList.remove('hidden');
    instructionsOverlay.classList.add('hidden');
}