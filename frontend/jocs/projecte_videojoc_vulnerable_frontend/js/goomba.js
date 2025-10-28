class Goomba {
    constructor(element, posicioInicial) {
      this.element = element;
      this.posicio = { x: posicioInicial.x, y: posicioInicial.y };
      this.velocitat = { x: -1, y: 0 };
      this.morir = false;
  
      this.frameWidth = 16; 
      this.frameHeight = 32; 
      this.numFotogrames = 3; 
      this.fotogramaActual = 0;
      this.fps = 6; 
      this.ultimCanvi = performance.now();
  
      this.desplacamentVertical = -4; 
  
      this.actualitzarPosicio();
    }
  
    actualitzarPosicio() {
      this.element.style.left = `${this.posicio.x}px`;
      this.element.style.bottom = `${this.posicio.y}px`;
    }
  
    actualitzarFotograma(now = performance.now()) {
      if (this.morir) return;
  
      if (now - this.ultimCanvi > 1000 / this.fps) {
        this.ultimCanvi = now;
        this.fotogramaActual = (this.fotogramaActual + 1) % this.numFotogrames;
        const x = this.fotogramaActual * this.frameWidth; 
        const y = this.desplacamentVertical; 
        this.element.style.backgroundPosition = `-${x}px ${y}px`;
      }
    }
  
    moure(now = performance.now()) {
      if (this.morir) return;
  
      this.posicio.x += this.velocitat.x;
  
      if (this.posicio.x <= 0 || this.posicio.x >= 800 - this.frameWidth * 4) {
        this.velocitat.x *= -1;
        this.element.style.transform = this.velocitat.x < 0 ? 'scale(2)' : 'scale(-2, 2)';
      }
  
      this.actualitzarPosicio();
      this.actualitzarFotograma(now);
    }
  
    stomp() {
      this.morir = true;
      const x = 2 * this.frameWidth; 
      const y = this.desplacamentVertical; 
      this.element.style.backgroundPosition = `-${x}px ${y}px`;
  
      setTimeout(() => {
        this.element.remove(); 
      }, 500);
    }
  }