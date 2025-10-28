// --------- Pantalla i Info ---------
const pantalla = document.querySelector("#pantalla");
const infoPartida = document.querySelector("#infoPartida");

// --------- Variables Globals del Joc ---------
const pantallaAmple = window.innerWidth;
const pantallaAlt = window.innerHeight;
const fotogrames = 1000 / 60; // 60 FPS

// --------- Configuració de la Partida (Nivell 1) ---------
// TODO: Carregar amb fetch()
const nivell = 1;
const maxPuntsNivell1 = 100; // Objectiu per fer aparèixer el Boss
let maxEnemics = 10;

// Arrays per guardar les entitats
let vectorEnemics = [];
let vectorProjectils = [];
let boss = null;
let bossActiu = false;

// ================================================================
// ========= AQUÍ ESTÀ LA CLAU: DEFINEIX LES TEVES IMATGES =========
// ================================================================
// Posa els noms exactes dels teus arxius (compte amb majúscules/minúscules)
// La ruta './assets/' funciona perquè està a la mateixa carpeta que 'index.html'.

const JUGADOR_IMG = './assets/player.png';   // Posa el nom del teu jugador
const ENEMIC_IMG = './assets/enemy1.png';    // Posa el nom del teu enemic
const BOSS_IMG = './assets/boss.png';        // Posa el nom del teu boss
const PROJECTIL_IMG = null;
// ================================================================

// --------- 1. CREACIÓ DELS OBJECTES ---------
// Modifiquem el constructor per passar-li també la imatge del projectil
const jugador = new Jugador("Player1", 5, 10, JUGADOR_IMG, PROJECTIL_IMG);
pantalla.append(jugador.elementHTML);

// Funció per crear enemics
function crearEnemic() {
  let posX = pantallaAmple + Math.floor(Math.random() * pantallaAmple);
  let posY = Math.floor(Math.random() * (pantallaAlt - 50));
  let velocitat = Math.floor(Math.random() * 5) + 3;
  
  const nouEnemic = new Enemic(jugador, velocitat, { x: posX, y: posY }, ENEMIC_IMG);
  vectorEnemics.push(nouEnemic);
  pantalla.append(nouEnemic.elementHTML);
}
// Creem els enemics inicials
for (let i = 0; i < maxEnemics; i++) crearEnemic();


// --------- 2. CREACIÓ DEL PANELL D'INFORMACIÓ ---------
// (Aquesta part es queda exactament igual que abans)
const elementNom = document.createElement("p");
const elementPunts = document.createElement("p");
const elementDerribats = document.createElement("p");
const elementVides = document.createElement("p");

elementNom.innerHTML = `Jugador: ${jugador.nom}`;
elementPunts.innerHTML = `Punts: ${jugador.punts}`;
elementDerribats.innerHTML = `Kills: ${jugador.derribats}`;
elementVides.innerHTML = `Vides: ${jugador.vides}`;
infoPartida.append(elementNom, elementPunts, elementDerribats, elementVides);


// --------- 3. GESTIÓ D'ESDEVENIMENTS (RATOLÍ I TECLAT) ---------
// (Aquesta part es queda exactament igual que abans)
window.addEventListener("mousemove", (event) => {
  jugador.moure(event.clientY);
});
window.addEventListener("keydown", (event) => {
  if (event.code === "Space") jugador.disparar();
});
window.addEventListener("mousedown", () => {
  jugador.disparar();
});

// --------- 4. LÒGICA DE COL·LISIONS I PUNTUACIÓ ---------
// (Aquesta part es queda exactament igual que abans)
function comprovarCollisions() {
  // A. Col·lisions Projectil vs Enemic
  vectorProjectils.forEach((p) => {
    vectorEnemics.forEach((e) => {
      if (p.viu && e.viu &&
        p.x < e.x + e.ample &&
        p.x + p.ample > e.x &&
        p.y < e.y + e.alt &&
        p.y + p.alt > e.y
      ) {
        p.morir();
        e.morir();
        jugador.punts += 10;
        jugador.derribats++;
      }
    });

    // B. Col·lisions Projectil vs Boss
    if (bossActiu && p.viu &&
        p.x < boss.x + boss.ample &&
        p.x + p.ample > boss.x &&
        p.y < boss.y + boss.alt &&
        p.y + p.alt > boss.y
    ) {
        p.morir();
        boss.rebreCop();
        jugador.punts += 5;
        
        if (boss.vides <= 0) {
            boss.morir();
            bossActiu = false;
            alert("NIVELL 2 SUPERAT! Has guanyat!");
            jugador.punts += 1000;
        }
    }
  });
  
  // C. Col·lisions Jugador vs Enemic (xocar)
    vectorEnemics.forEach((e) => {
      if (e.viu &&
        jugador.x < e.x + e.ample &&
        jugador.x + jugador.ample > e.x &&
        jugador.y < e.y + e.alt &&
        jugador.y + jugador.alt > e.y
      ) {
        e.morir();
        jugador.vides--;
      }
    });
}

// --------- 5. LÒGICA DE NIVELL (SPAWN BOSS) ---------
function spawnBoss() {
    bossActiu = true;
    vectorEnemics.forEach(e => e.morir());
    vectorEnemics = [];
    
    boss = new Boss(BOSS_IMG); // Usem la constant del Boss
    pantalla.append(boss.elementHTML);
    
    elementNom.innerHTML = "!!! BOSS ALERT !!!";
}

// --------- 6. BUCLE D'ANIMACIÓ DEL JOC (GAME LOOP) ---------
// (Aquesta part es queda exactament igual que abans)
let gameInterval = setInterval(() => {
  // 0. Gestió de col·lisions
  comprovarCollisions();

  // 1. Gestió del jugador
  elementVides.innerHTML = `Vides: ${jugador.vides}`;
  elementPunts.innerHTML = `Punts: ${jugador.punts}`;
  elementDerribats.innerHTML = `Kills: ${jugador.derribats}`;

  if (jugador.vides < 0) {
    clearInterval(gameInterval);
    alert("Game Over!");
  }

  jugador.dibuixar();

  // 2. Gestió dels Enemics
  vectorEnemics = vectorEnemics.filter(e => e.viu);
  if (!bossActiu && vectorEnemics.length < maxEnemics) {
      crearEnemic();
  }
  vectorEnemics.forEach((enemic) => {
    enemic.dibuixar();
    enemic.moure();
  });

  // 3. Gestió dels Projectils
  vectorProjectils = vectorProjectils.filter(p => p.viu);
  vectorProjectils.forEach((projectil) => {
    projectil.dibuixar();
    projectil.moure();
  });
  
  // 4. Gestió del Boss
  if (bossActiu) {
      boss.dibuixar();
      boss.moure();
  }
  
  // 5. Comprovar si toca treure el Boss
  if (jugador.punts >= maxPuntsNivell1 && !bossActiu && boss === null) {
      spawnBoss();
  }

}, fotogrames);