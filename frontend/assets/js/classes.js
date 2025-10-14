// --------- Classe Base ---------
class Entitat {
  constructor(posicio = {x: 0, y:0}, ample = 50, alt = 50) {
    this.x = posicio.x;
    this.y = posicio.y;
    this.ample = ample;
    this.alt = alt;
    //Crear l'element HTML
    this.elementHTML = document.createElement("div");
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
    this.elementHTML.style.width = this.ample + "px";
    this.elementHTML.style.height = this.alt + "px";
  }

  // Modifica la posició de l'element a la pantalla
  dibuixar() {
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
  }

  moure() {
    // Implementar la lògica de moviment
  }
}