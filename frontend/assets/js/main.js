// --------- Pantalla del Joc ---------
const pantalla = document.querySelector("#pantalla");
const infoPartida = document.querySelector("#infoPartida");

// --------- Objecte Jugador ---------
const jugador = new Entitat({x: 100, y: 300}, 150, 100);
jugador.elementHTML.classList.add("nau", "jugador");
pantalla.append(jugador.elementHTML);

// --------- Objecte Enemic ---------
const enemic = new Entitat({x: 500, y: 200}, 75, 75);
enemic.elementHTML.classList.add("nau","enemic");
pantalla.append(enemic.elementHTML);

// ------- Objecte asteroide -------
const asteroides = [];
for (let i = 0; i < 100; i++) {
  let posX = Math.floor(Math.random() * 1200);
  let posY = Math.floor(Math.random() * 800);
  const asteroide = new Entitat({x: posX, y: posY}, 5, 5);
  asteroide.elementHTML.classList.add("asteroide");
  pantalla.append(asteroide.elementHTML);
  asteroides.push(asteroide);
}

// ------- Informació de la partida -------
const elementNom = document.createElement("p");
const elementPunts = document.createElement("p");
const elementDerribats = document.createElement("p");
const elementVides = document.createElement("p");
elementNom.innerHTML = `Jugador: Pepet`;
infoPartida.append(elementNom);
elementPunts.innerHTML = `Punts: 100`;
infoPartida.append(elementPunts);
elementDerribats.innerHTML = `Kills: 12`;
infoPartida.append(elementDerribats);
elementVides.innerHTML = `Vides: 2`;
infoPartida.append(elementVides);