// --------- Classe Base (Mare de totes) ---------
class Entitat {
  constructor(posicio, ample, alt, imgUrl) {
    this.x = posicio.x;
    this.y = posicio.y;
    this.ample = ample;
    this.alt = alt;
    this.viu = true; // Per saber si l'hem de treure del joc

    this.elementHTML = document.createElement("div");
    this.elementHTML.className = "entitat";
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
    this.elementHTML.style.width = this.ample + "px";
    this.elementHTML.style.height = this.alt + "px";
    
    // Canvi clau: Usem la imatge
    if (imgUrl) {
      this.elementHTML.style.backgroundImage = `url('${imgUrl}')`;
    }
  }

  // Mètode per actualitzar la posició a la pantalla
  dibuixar() {
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
  }

  // Mètode per eliminar l'entitat del DOM
  morir() {
    this.viu = false;
    this.elementHTML.remove();
  }
}

// --------- Classe Jugador (Hereta d'Entitat) ---------
class Jugador extends Entitat {
  constructor(nom, vides, velocitat, imgUrl) {
    const ample = 80;
    const alt = 80;
    const posicio = { x: 100, y: window.innerHeight / 2 - alt / 2 };
    
    super(posicio, ample, alt, imgUrl); // Crida al constructor de la classe pare
    
    this.nom = nom;
    this.vides = vides;
    this.velocitat = velocitat;
    this.punts = 0;
    this.derribats = 0;
    this.potDisparar = true;
    
    this.elementHTML.classList.add("jugador");
  }

  // El jugador ara es mou amb el ratolí (més fluid)
  moure(mouseY) {
    this.y = mouseY - this.alt / 2;
    // Límits de pantalla
    if (this.y < 0) this.y = 0;
    if (this.y + this.alt > window.innerHeight) this.y = window.innerHeight - this.alt;
  }
  
  // Mètode per disparar
  disparar() {
    if (this.potDisparar) {
      this.potDisparar = false;
      const projectil = new Projectil({ x: this.x + this.ample, y: this.y + this.alt / 2 - 15 });
      
      // Afegim el projectil a l'array global (definit a main.js)
      vectorProjectils.push(projectil);
      pantalla.append(projectil.elementHTML);
      
      // Cooldown de dispart
      setTimeout(() => {
        this.potDisparar = true;
      }, 300); // 300ms de cooldown
    }
  }
}

// --------- NOU! Classe Projectil (Hereta d'Entitat) ---------
class Projectil extends Entitat {
    constructor(posicio, imgUrl) { 
        // ------------------- CANVI AQUÍ -------------------
        // Mides d'un guió ("-")
        const ample = 25; // Més ample
        const alt = 5;  // Més prim
        // ----------------- FI DEL CANVI -----------------
        
        super(posicio, ample, alt, imgUrl); // Passem la imgUrl (que ara serà null)

        this.velocitat = 15;
        this.elementHTML.classList.add("projectil");
    }

    moure() {
        this.x += this.velocitat;
        if (this.x > window.innerWidth) {
            this.viu = false;
        }
    }
}

// --------- Classe Enemic (Hereta d'Entitat) ---------
class Enemic extends Entitat {
  constructor(jugador, velocitat, posicio, imgUrl) {
    super(posicio, 50, 50, imgUrl); // Mida fixa 50x50
    
    this.jugador = jugador; 
    this.velocitat = velocitat;
    this.elementHTML.classList.add("enemic");
  }

  moure() {
    this.x -= this.velocitat; // Es mou cap a l'esquerra
    if (this.x < -this.ample) {
      this.jugador.vides--; // El jugador perd una vida
      this.viu = false; // Marca'l per eliminar
    }
  }
}

// --------- NOU! Classe Boss (Hereta d'Entitat) ---------
class Boss extends Entitat {
    constructor(imgUrl) {
        const ample = 200;
        const alt = 200;
        const posicio = { x: window.innerWidth - 250, y: window.innerHeight / 2 - alt / 2 };
        
        super(posicio, ample, alt, imgUrl);
        
        this.vides = 50; // El Boss té 50 "cops" de vida
        this.velocitat = 5; // Velocitat de moviment vertical
        this.elementHTML.classList.add("boss");
    }

    moure() {
        this.y += this.velocitat;
        // Rebotar als marges verticals
        if (this.y < 0 || this.y + this.alt > window.innerHeight) {
            this.velocitat *= -1; // Inverteix la direcció
        }
    }
    
    // Funció quan el boss rep un tret
    rebreCop() {
        this.vides--;
        // Efecte visual de "cop"
        this.elementHTML.style.boxShadow = "0 0 50px 20px #FF0000";
        setTimeout(() => {
            this.elementHTML.style.boxShadow = "none";
        }, 100);
    }
}