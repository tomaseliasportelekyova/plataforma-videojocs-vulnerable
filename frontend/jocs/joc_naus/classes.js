// Arxiu: frontend/jocs/joc_naus/classes.js

// Classe Base (Entitat) - Afegim referència a pantalla per límits
class Entitat {
  constructor(posicio, ample, alt, imgUrl, pantallaElement) {
    this.x = posicio.x;
    this.y = posicio.y;
    this.ample = ample;
    this.alt = alt;
    this.viu = true;
    this.imgUrl = imgUrl; // Guardem URL
    this.pantalla = pantallaElement; // Guardem referència al contenidor del joc

    this.elementHTML = document.createElement("div");
    this.elementHTML.className = "entitat";
    this.actualitzarEstil(); // Funció per aplicar estils inicials i d'imatge

    // Afegim l'element a la pantalla directament aquí
    if (this.pantalla) {
        this.pantalla.append(this.elementHTML);
    } else {
        console.error("Element pantalla no proporcionat a Entitat");
    }
  }

  // Funció per posar/actualitzar estils
  actualitzarEstil() {
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
    this.elementHTML.style.width = this.ample + "px";
    this.elementHTML.style.height = this.alt + "px";
    if (this.imgUrl) {
      this.elementHTML.style.backgroundImage = `url('${this.imgUrl}')`;
    }
  }

  // Dibuixar només actualitza posició
  dibuixar() {
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
  }

  morir() {
    this.viu = false;
    this.elementHTML.remove();
  }
}

// Classe Jugador (Mou esquerra/dreta a baix)
class Jugador extends Entitat {
  constructor(nom, vides, velocitat, imgUrl, projectilImgUrl, pantallaElement) {
    const ample = 60; // Més petit potser?
    const alt = 60;
    // Posició inicial: a baix, centrat horitzontalment
    const posicioInicialX = pantallaElement ? pantallaElement.offsetWidth / 2 - ample / 2 : 100;
    const posicioInicialY = pantallaElement ? pantallaElement.offsetHeight - alt - 10 : 500;
    
    super({ x: posicioInicialX, y: posicioInicialY }, ample, alt, imgUrl, pantallaElement);
    
    this.nom = nom;
    this.vides = vides;
    this.velocitat = velocitat;
    this.punts = 0;
    this.derribats = 0;
    this.potDisparar = true;
    this.projectilImgUrl = projectilImgUrl; // Guardem URL per disparar
    
    this.elementHTML.classList.add("jugador");
  }

  // Mou només horitzontalment (esquerra/dreta)
  moure(direccio) {
      if (!this.pantalla) return;
      const limitEsquerre = 0;
      const limitDret = this.pantalla.offsetWidth - this.ample;

      if (direccio === 'esquerra' && this.x > limitEsquerre) {
          this.x -= this.velocitat;
      } else if (direccio === 'dreta' && this.x < limitDret) {
          this.x += this.velocitat;
      }
      // Ajusta si se'n surt (per si velocitat és molt alta)
      if (this.x < limitEsquerre) this.x = limitEsquerre;
      if (this.x > limitDret) this.x = limitDret;
  }
  
  // Dispara cap a dalt
  disparar() {
    if (this.potDisparar && this.pantalla) {
      this.potDisparar = false;
      // Posició inicial del projectil: sobre el jugador, centrat
      const projectilX = this.x + this.ample / 2 - 5; // Amplada projectil 10
      const projectilY = this.y - 30; // Alçada projectil 30
      
      const projectil = new Projectil({ x: projectilX, y: projectilY }, this.projectilImgUrl, this.pantalla);
      vectorProjectils.push(projectil); // Array global de main.js
      
      // Cooldown
      setTimeout(() => { this.potDisparar = true; }, 300);
    }
  }
}

// Classe Projectil (Puja)
class Projectil extends Entitat {
    constructor(posicio, imgUrl, pantallaElement) {
        const ample = 5;  // Més prim
        const alt = 25; // Més curt
        super(posicio, ample, alt, imgUrl, pantallaElement); // imgUrl serà null pel làser CSS

        this.velocitat = 15; // Velocitat cap amunt (negativa en Y)
        this.elementHTML.classList.add("projectil");
        // Sobreescriu estil si és làser CSS (sense imatge)
        if (!imgUrl) {
             this.elementHTML.style.backgroundColor = '#FF0000';
             this.elementHTML.style.boxShadow = '0 0 5px #FF0000, 0 0 10px #FF0000';
             this.elementHTML.style.borderRadius = '2px';
        }
    }

    moure() {
        this.y -= this.velocitat; // Mou cap a dalt
        // Si surt per dalt, marca'l per eliminar
        if (this.y + this.alt < 0) {
            this.viu = false;
        }
    }
}

// ================================================================
// ========= CLASSE ENEMIC (MODIFICADA) =========
// ================================================================
class Enemic extends Entitat {
  // Afegim 'config' al constructor
  constructor(jugador, velocitat, posicioX, imgUrl, pantallaElement, config) {
    const ample = 40;
    const alt = 40;
    super({ x: posicioX, y: -alt }, ample, alt, imgUrl, pantallaElement);
    
    this.jugador = jugador; 
    this.velocitat = velocitat; 
    this.elementHTML.classList.add("enemic");

    // --- NOU: Dificultat de Dispar ---
    // Agafem la cadència del config del nivell, o posem 0.5% per defecte
    this.cadenciaDispar = config.cadenciaDisparEnemic || 0.005; 
  }

  moure() {
      if (!this.pantalla) return;
      this.y += this.velocitat; // Cau
      
      // Si surt per baix
      if (this.y > this.pantalla.offsetHeight) {
          this.jugador.vides--;
          this.viu = false;
      }

      // --- NOU: Lògica de Disparar ---
      // Comprova si dispara en aquest frame (només si està dins la pantalla)
      if (this.viu && this.y > 0 && Math.random() < this.cadenciaDispar) {
          this.disparar();
      }
  }

  // --- NOU: Funció de Disparar ---
  disparar() {
    if (this.viu && this.pantalla) {
      // Posició inicial del projectil: sota l'enemic
      const projectilX = this.x + this.ample / 2 - 2.5; // Amplada projectil 5
      const projectilY = this.y + this.alt; // Sota la nau
      
      // Creem el projectil (sense imatge, només CSS)
      const projectil = new ProjectilEnemic({ x: projectilX, y: projectilY }, null, this.pantalla);
      
      // L'afegim a l'array global d'enemics (declarat a main.js)
      vectorProjectilsEnemics.push(projectil);
    }
  }
}

// Classe Boss (Es mou esquerra/dreta a dalt)
class Boss extends Entitat {
    constructor(videsBoss, imgUrl, pantallaElement) {
        const ample = 100; // Més petit
        const alt = 100;
        // Posició inicial: a dalt, centrat
        const posicioInicialX = pantallaElement ? pantallaElement.offsetWidth / 2 - ample / 2 : 250;
        super({ x: posicioInicialX, y: 10 }, ample, alt, imgUrl, pantallaElement);
        
        this.vides = videsBoss;
        this.videsMax = videsBoss;
        this.velocitat = 4; // Velocitat horitzontal
        this.elementHTML.classList.add("boss");
    }

    moure() {
        if (!this.pantalla) return;
        this.x += this.velocitat;
        // Rebotar als marges laterals
        const limitEsquerre = 0;
        const limitDret = this.pantalla.offsetWidth - this.ample;
        if (this.x < limitEsquerre || this.x > limitDret) {
            this.velocitat *= -1; // Inverteix direcció
            // Ajust petit per evitar que es quedi enganxat
            this.x = Math.max(limitEsquerre, Math.min(this.x, limitDret)); 
        }
    }
    
    // Barra de vida (igual que abans, però horitzontal potser?)
    rebreCop() {
        this.vides--;
        const vidaPercent = Math.max(0, (this.vides / this.videsMax) * 100);
        // Barra de vida vermella a sota (inset shadow)
        this.elementHTML.style.boxShadow = `inset 0px -${this.alt * (1 - vidaPercent / 100)}px 0px 0px rgba(255,0,0,0.7)`;
    }
}
// ================================================================
// ========= ¡NOVA CLASSE: PROJECTIL DE L'ENEMIC! =========
// ================================================================
class ProjectilEnemic extends Entitat {
    constructor(posicio, imgUrl, pantallaElement) {
        const ample = 5;
        const alt = 15; // Més curt que el del jugador
        super(posicio, ample, alt, imgUrl, pantallaElement);

        this.velocitat = 7; // Velocitat cap avall (positiva)
        this.elementHTML.classList.add("projectil"); // Reutilitzem la classe CSS
        
        // Estil per al projectil enemic (p.ex., verd)
        this.elementHTML.style.backgroundColor = '#00FF00';
        this.elementHTML.style.boxShadow = '0 0 5px #00FF00, 0 0 10px #00FF00';
    }

    moure() {
        this.y += this.velocitat; // Mou cap avall
        // Si surt per baix, marca'l per eliminar
        if (this.y > this.pantalla.offsetHeight) {
            this.viu = false;
        }
    }
}