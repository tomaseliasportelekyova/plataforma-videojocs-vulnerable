// Arxiu: frontend/jocs/joc_tower_defense/classes.js

// === 1. CLASSE BASE ===
class Entitat {
    constructor(gameLayer, x, y, mida) {
        this.gameLayer = gameLayer;
        this.x = x;
        this.y = y;
        this.mida = mida;
        this.viu = true;
        this.element = document.createElement('div');
        this.element.className = 'entitat';
        this.element.style.left = '0px';
        this.element.style.top = '0px';
        if (mida > 0) {
            this.element.style.width = `${mida}px`;
            this.element.style.height = `${mida}px`;
        }
        this.rotacio = 0; 
        this.gameLayer.appendChild(this.element);
        this.dibuixar();
    }

    crearSVG(type) {
        let svgMarkup = '';
        const colors = {
            bug: '#FF4136', troia: '#0074D9', spyware: '#2ECC40',
            adware: '#B10DC9', worm: '#FF851B', phisher: '#AAAAAA', bot: '#111111',
            sentry: '#8A2BE2', cano: '#B10DC9', freezer: '#50A5FF', detector: '#2ECC40'
        };
        switch(type) {
            // === ENEMICS DEL GDD ===
            case 'Bug':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="2" y="2" width="16" height="16" fill="${colors.bug}" rx="4" /></svg>`;
                break;
            case 'Troia':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="0" y="0" width="20" height="20" fill="${colors.troia}" /><rect x="4" y="4" width="12" height="12" fill="#001f3f" /></svg>`;
                break;
            case 'Spyware':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><polygon points="10,0 20,20 0,20" fill="${colors.spyware}" /></svg>`;
                break;
            case 'Adware': // GDD: Cercle lila que fa pampallugues
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><circle cx="10" cy="10" r="8" fill="${colors.adware}" /></svg>`;
                break;
            case 'Worm': // GDD: Rectangle llarg segmentat
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="2" y="2" width="16" height="16" fill="${colors.worm}" rx="8" /></svg>`;
                break;
            case 'Phisher': // GDD: Quadrat semi-transparent
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="2" y="2" width="16" height="16" fill="${colors.phisher}" rx="2" /></svg>`;
                break;
            case 'Bot': // GDD: Petit punt negre
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><circle cx="10" cy="10" r="6" fill="${colors.bot}" /></svg>`;
                break;
            
            // === TORRETES (sense canvis) ===
            case 'Sentry':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><circle cx="10" cy="10" r="8" fill="${colors.sentry}" /><rect x="8" y="0" width="4" height="12" fill="#555" /></svg>`;
                break;
            case 'Cano':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="2" y="2" width="16" height="16" fill="${colors.cano}" rx="3" /><rect x="6" y="0" width="8" height="14" fill="#555" /></svg>`;
                break;
            case 'Bit-Freezer':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><polygon points="10,0 13,7 20,7 15,12 17,20 10,15 3,20 5,12 0,7 7,7" fill="${colors.freezer}" /><rect x="9" y="-2" width="2" height="10" fill="#bbb" /></svg>`;
                break;
            case 'Detector':
                svgMarkup = `<svg viewBox="0 0 20 20" width="100%" height="100%"><rect x="9" y="12" width="2" height="8" fill="#aaa" /><path d="M 2 10 Q 10 2 18 10" stroke="${colors.detector}" stroke-width="2" fill="none" stroke-linecap="round" /></svg>`;
                break;
        }
        this.element.innerHTML = svgMarkup;
    }

    dibuixar() {
        if (!this.viu) return;
        this.element.style.transform = `translate(calc(${this.x}px - 50%), calc(${this.y}px - 50%)) rotate(${this.rotacio}deg)`;
    }

    morir() {
        this.viu = false;
        if (this.element) {
            this.element.remove();
            this.element = null;
        }
    }

    distanciaA(entitat) {
        const dx = this.x - entitat.x;
        const dy = this.y - entitat.y;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    angleA(target) {
        const dx = target.x - this.x;
        const dy = target.y - this.y;
        return Math.atan2(dy, dx) * 180 / Math.PI + 90;
    }
}

// === 2. CLASSE ENEMIC (MODIFICADA AMB TOTS ELS TIPUS) ===
class Enemic extends Entitat {
    constructor(gameLayer, path, type) {
        super(gameLayer, path[0].x, path[0].y, 0); 
        this.path = path;
        this.type = type;
        this.puntDelPath = 1; 
        
        this.crearSVG(this.type);
        this.setStats(); // Ara crida a setStats DESPRÉS de crearSVG
        
        this.element.classList.add('enemic');
    }

    setStats() {
        this.slowTimer = 0;
        let midaEnemic = 30;

        // === NOVES PROPIETATS PER ESTATS ===
        this.isInvisible = false;
        this.isBuffed = false;
        this.auraRadius = 0;
        // === FI NOVES PROPIETATS ===

        switch(this.type) {
            case 'Bug': // GDD: Vides 1, Vel mitjana
                this.salut = 10; this.salutMax = 10; // (Pujem vides a 10 per Sentry)
                this.velocitat = 1.5;
                this.recompensa = 5;
                break;
            case 'Troia': // GDD: Vides 15, Vel lenta
                midaEnemic = 40;
                this.salut = 150; this.salutMax = 150; // (Pujem vides a 150 per Canó)
                this.velocitat = 0.8;
                this.recompensa = 20;
                break;
            case 'Spyware': // GDD: Vides 3, Vel molt ràpida
                midaEnemic = 20;
                this.salut = 30; this.salutMax = 30;
                this.velocitat = 3;
                this.recompensa = 10;
                break;
            case 'Adware': // GDD: Vides 5, Vel lenta, Aura
                this.salut = 50; this.salutMax = 50;
                this.velocitat = 0.7;
                this.recompensa = 15;
                this.auraRadius = 100; // Aura que accelera
                break;
            case 'Worm': // GDD: Vides 10, Vel mitjana, Spawns
                midaEnemic = 35;
                this.salut = 100; this.salutMax = 100;
                this.velocitat = 1.2;
                this.recompensa = 10;
                // La lògica de spawn es farà a 'main.js' en morir
                break;
            case 'Phisher': // GDD: Camuflat
                this.salut = 40; this.salutMax = 40;
                this.velocitat = 1.5;
                this.recompensa = 20;
                this.isInvisible = true; // És invisible!
                break;
            case 'Bot': // GDD: Vides 1, Vel ràpida, Eixam
                midaEnemic = 15;
                this.salut = 1; this.salutMax = 1;
                this.velocitat = 2.5;
                this.recompensa = 1;
                break;
            default: // Per si de cas
                this.salut = 10; this.salutMax = 10;
                this.velocitat = 1.5;
                this.recompensa = 5;
                break;
        }

        this.velocitatOriginal = this.velocitat; // Guardem la velocitat base
        this.mida = midaEnemic;
        this.element.style.width = `${midaEnemic}px`;
        this.element.style.height = `${midaEnemic}px`;

        this.healthBar = document.createElement('div');
        this.healthBar.className = 'health-bar';
        this.healthBarInner = document.createElement('div');
        this.healthBarInner.className = 'health-bar-inner';
        this.healthBar.appendChild(this.healthBarInner);
        this.element.appendChild(this.healthBar);

        // Actualitzem l'estat visual (p.ex. invisible)
        this.updateVisualState();
    }
    
    // === NOVA FUNCIÓ per actualitzar estats visuals ===
    updateVisualState() {
        if (!this.viu) return;
        
        // Invisibilitat
        if (this.isInvisible) {
            this.element.classList.add('invisible');
        } else {
            this.element.classList.remove('invisible');
        }
        
        // Aura
        if (this.isBuffed) {
            this.element.classList.add('aura-buffed');
        } else {
            this.element.classList.remove('aura-buffed');
        }
        
        // Alentiment
        if (this.slowTimer > 0) {
             this.element.classList.add('slowed');
        } else {
             this.element.classList.remove('slowed');
        }
    }

    aplicarSlow(duradaSegons, percentatge) {
        this.velocitat = this.velocitatOriginal * (1 - percentatge);
        this.slowTimer = duradaSegons * 60; // 60 FPS
        this.updateVisualState();
    }

    moure() {
        if (!this.viu) return;

        // Resetegem el buff d'aura (el 'main.js' el tornarà a aplicar si cal)
        this.isBuffed = false;
        this.velocitat = this.velocitatOriginal;

        if (this.slowTimer > 0) {
            this.slowTimer--;
            if (this.slowTimer <= 0) {
                // S'ha acabat l'slow, tornem a la vel original
                this.velocitat = this.velocitatOriginal;
            } else {
                // Encara està alentit
                this.velocitat = this.velocitatOriginal * (1 - this.slowPercent); // (cal tenir slowPercent)
            }
        }
        
        // Actualitzem l'estat visual abans de moure
        this.updateVisualState();

        let movimentRestant = this.velocitat;

        while (movimentRestant > 0 && this.puntDelPath < this.path.length) {
            const target = this.path[this.puntDelPath];
            const dx = target.x - this.x;
            const dy = target.y - this.y;
            const distancia = Math.sqrt(dx * dx + dy * dy);

            if (distancia === 0) {
                 this.puntDelPath++;
                 continue;
            }

            if (distancia <= movimentRestant) {
                this.x = target.x;
                this.y = target.y;
                this.puntDelPath++;
                movimentRestant -= distancia;
            } else {
                this.x += (dx / distancia) * movimentRestant;
                this.y += (dy / distancia) * movimentRestant;
                movimentRestant = 0;
            }
        }

        this.dibuixar();
    }

    haArribatAlFinal() { return this.puntDelPath >= this.path.length; }

    rebreMal(quantitat) {
        this.salut -= quantitat;
        const salutPercent = (this.salut / this.salutMax) * 100;
        
        if (this.healthBarInner) {
            this.healthBarInner.style.width = `${Math.max(0, salutPercent)}%`;
            this.healthBarInner.classList.remove('low', 'critical');
            if (salutPercent < 25) this.healthBarInner.classList.add('critical');
            else if (salutPercent < 60) this.healthBarInner.classList.add('low');
        }

        if (this.salut <= 0) {
            this.morir();
            return this.recompensa;
        }
        return 0;
    }
}

// === 3. CLASSE TORRETA (MODIFICADA AMB LOGICA DE DETECCIÓ) ===
class Torreta extends Entitat {
    constructor(gameLayer, x, y, type) {
        super(gameLayer, x, y, 40);
        this.type = type;
        this.target = null;
        this.cooldown = 0;
        this.nivell = 1;
        this.upgradePath = null;
        this.costTotal = 0;
        this.costMillora = 0;
        this.maxNivell = 5;
        this.slowPercent = 0;
        this.slowDuration = 0;

        // === NOVA Propietat per Aures/Detecció ===
        this.auraRadius = 0;

        this.setStats(); 
        this.element.classList.add('torreta');
        this.element.style.pointerEvents = 'auto';
        this.crearSVG(this.type);
    }
    
    setStats() {
        switch(this.type) {
            case 'Sentry':
                this.abast = 150; this.mal = 2;
                this.cadencia = 20; this.costTotal = 100;
                this.costMillora = 75;
                this.tipusProjectil = 'ProjectilSentry'; this.slows = false;
                break;
            case 'Cano':
                this.abast = 200; this.mal = 15;
                this.cadencia = 120; this.costTotal = 250;
                this.costMillora = 200;
                this.tipusProjectil = 'ProjectilCano'; this.slows = false;
                break;
            case 'Bit-Freezer':
                this.abast = 120; this.mal = 0; 
                this.cadencia = 30; this.costTotal = 150;
                this.costMillora = 100;
                this.tipusProjectil = 'ProjectilFreezer'; this.slows = true;
                this.slowPercent = 0.5; this.slowDuration = 2.0;
                break;
            case 'Detector': // GDD: No ataca, només detecta
                this.abast = 0; this.mal = 0; // No té abast d'atac
                this.cadencia = 0; this.costTotal = 75;
                this.costMillora = 50;
                this.tipusProjectil = null; this.slows = false;
                this.auraRadius = 200; // Té un radi d'aura per detectar
                break;
        }
    }

    aplicarMillora(path) {
        if (this.nivell >= this.maxNivell) return false;
        if (this.upgradePath === null) this.upgradePath = path;
        if (this.upgradePath !== path) return false;
        
        this.nivell++;
        this.costTotal += this.costMillora;

        switch(this.type) {
            case 'Sentry':
                if (path === 'pathA') {
                    this.cadencia = Math.max(5, this.cadencia * 0.75);
                    this.mal += 1;
                } else {
                    this.mal += 3;
                    this.cadencia = Math.max(5, this.cadencia * 0.9);
                }
                break;
            case 'Cano':
                if (path === 'pathA') {
                    this.mal += 10;
                    this.abast += 15;
                    this.cadencia *= 0.95;
                } else {
                    this.cadencia = Math.max(30, this.cadencia * 0.7);
                    this.mal += 3;
                }
                break;
            case 'Bit-Freezer':
                if (path === 'pathA') {
                    this.slowPercent = Math.min(0.9, this.slowPercent + 0.1);
                    this.slowDuration += 0.5;
                    this.abast += 5;
                } else {
                    this.abast += 25;
                    this.cadencia *= 0.85;
                }
                break;
            case 'Detector':
                if (path === 'pathA') { // GDD: Utilitat
                    this.auraRadius += 30; // Més abast de detecció
                    // TODO: Lògica 'Inhibidor' / 'Overclock' (es faria al main.js)
                } else { // GDD: Economia
                    // TODO: Lògica 'Data Mining' (es faria al main.js)
                    this.auraRadius += 15;
                }
                break;
        }

        this.costMillora = Math.floor(this.costMillora * 1.6);
        return true; 
    }

    update(enemics, projectils) {
        if (!this.viu) return;
        
        // Els Detectors no ataquen, la seva lògica va al bucleJoc
        if (this.type === 'Detector') {
            this.dibuixar(); // Només dibuixem el detector (no gira)
            return; 
        }

        if (this.target && this.target.viu && this.distanciaA(this.target) <= this.abast && !this.target.isInvisible) {
            this.rotacio = this.angleA(this.target);
        } else {
            this.target = null;
        }
        
        this.dibuixar();
        if (this.cadencia === 0) return;
        if (this.cooldown > 0) this.cooldown--;
        
        if (!this.target) {
            this.target = this.trobarObjectiu(enemics);
        }
        
        if (this.target && this.cooldown <= 0) {
            this.atacar(projectils);
            this.cooldown = this.cadencia;
        }
    }
    
    trobarObjectiu(enemics) {
        let enemicMesProper = null;
        let distanciaMinima = this.abast;
        let maxPuntDelPath = -1; 
        
        for (const enemic of enemics) {
            if (enemic.viu) {
                
                // === LA CLAU: Ignorem enemics invisibles ===
                if (enemic.isInvisible) continue;
                
                const distancia = this.distanciaA(enemic);
                if (distancia <= distanciaMinima) {
                    if (enemic.puntDelPath > maxPuntDelPath) {
                        maxPuntDelPath = enemic.puntDelPath;
                        distanciaMinima = distancia;
                        enemicMesProper = enemic;
                    } else if (enemic.puntDelPath === maxPuntDelPath) {
                        if (distancia < distanciaMinima) {
                           distanciaMinima = distancia;
                           enemicMesProper = enemic; 
                        }
                    }
                }
            }
        }
        return enemicMesProper;
    }
    
    atacar(projectils) {
        const nouProjectil = new Projectil(
            this.gameLayer, 
            this.x, this.y, 
            this.target, 
            this.mal, 
            this.tipusProjectil,
            this.slows,
            this.rotacio,
            this.slowPercent,
            this.slowDuration
        );
        projectils.push(nouProjectil);
    }
    
    vendre() {
        this.morir();
        return this.costTotal * 0.7;
    }
}

// === 4. CLASSE PROJECTIL (Sense canvis de l'última vegada) ===
class Projectil extends Entitat {
    constructor(gameLayer, x, y, target, mal, type, slows = false, rotacioInicial = 0, slowPercent = 0.5, slowDuration = 2.0) {
        super(gameLayer, x, y, 0); 
        this.target = target;
        this.mal = mal;
        this.velocitat = 8;
        this.type = type;
        this.slows = slows;
        this.rotacio = rotacioInicial;
        this.slowPercent = slowPercent;
        this.slowDuration = slowDuration;
        
        this.element.classList.add('projectil');
        if (this.type) {
            this.element.classList.add(this.type);
        }
    }
    moure() {
        if (!this.viu) return 0;
        if (!this.target || !this.target.viu) {
            this.morir(); return 0;
        }
        const dx = this.target.x - this.x;
        const dy = this.target.y - this.y;
        const distancia = Math.sqrt(dx * dx + dy * dy);
        if (distancia < this.velocitat) {
            return this.impactar();
        } else {
            this.x += (dx / distancia) * this.velocitat;
            this.y += (dy / distancia) * this.velocitat;
            this.rotacio = this.angleA(this.target); 
        }
        this.dibuixar();
        return 0;
    }
    impactar() {
        let creditsGuanyats = 0;
        if (this.target && this.target.viu) {
            if (this.mal > 0) {
                creditsGuanyats = this.target.rebreMal(this.mal);
            }
            if (this.slows) {
                this.target.aplicarSlow(this.slowDuration, this.slowPercent); 
            }
        }
        this.morir();
        return creditsGuanyats;
    }
}