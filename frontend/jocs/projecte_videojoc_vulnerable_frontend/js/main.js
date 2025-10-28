class Mario {
    constructor(element) {
      this.element = element;
      this.posicio = { x: 50, y: 55 };
      this.estaSaltant = false;
      this.alcadaSalt = 150;
      this.velocitatSalt = 5;
      this.pas = 10;
      this.frame = 5;
      this.element.style.backgroundPosition = `-${this.frame * 16}px 0px`;
      this.actualitzarPosicio();
    }
  
    moure(direccio) {
      const jocElement = document.querySelector('.joc'); 
      if (direccio === 'esquerra' && this.posicio.x > 0) {
        this.posicio.x -= this.pas;
        this.element.style.transform = 'scale(-2, 2)'; 
        
        const posicioFonsActual = parseInt(getComputedStyle(jocElement).backgroundPositionX || 0, 10);
        jocElement.style.backgroundPositionX = `${posicioFonsActual + this.pas}px`;
      } else if (direccio === 'dreta') {
        if (this.posicio.x < (jocElement.clientWidth)/2 ) {
          this.posicio.x += this.pas;
          this.element.style.transform = 'scale(2)'; 
        }
        const posicioFonsActual = parseInt(getComputedStyle(jocElement).backgroundPositionX || 0, 10);
        jocElement.style.backgroundPositionX = `${posicioFonsActual - this.pas}px`;
      }
    
      this.frame = Math.floor((this.frame + 1) % 9);
      if (this.frame <= 5) this.frame = 6;
      console.log(this.frame);
      this.element.style.backgroundPosition = `-${this.frame * 16}px 0px`;
    
      this.actualitzarPosicio();
    }
  
    saltar() {
      if (this.estaSaltant) return;
  
      this.estaSaltant = true;
      let intervalAmunt = setInterval(() => {
        if (this.posicio.y >= this.alcadaSalt) {
          clearInterval(intervalAmunt);
          let intervalAvall = setInterval(() => {
            if (this.posicio.y <= 55) {
              clearInterval(intervalAvall);
              this.posicio.y = 55;
              this.estaSaltant = false;
            } else {
              this.posicio.y -= this.velocitatSalt;
            }
            this.actualitzarPosicio();
          }, 20);
        } else {
          this.posicio.y += this.velocitatSalt;
          this.actualitzarPosicio();
        }
      }, 20);
    }
  
    actualitzarPosicio() {
      this.element.style.left = `${this.posicio.x}px`;
      this.element.style.bottom = `${this.posicio.y}px`;
    }
  }
  
  const elementMario = document.querySelector('.mario');
  const mario = new Mario(elementMario);
  
  document.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      mario.moure('esquerra');
    } else if (event.key === 'ArrowRight') {
      mario.moure('dreta');
    } else if (event.key === 'ArrowUp') {
      mario.saltar();
    }
  });
  
  let gameState = 'play'; 
  document.addEventListener('DOMContentLoaded', () => {
    const elementGoomba = document.querySelector('.goomba');
    const goomba = new Goomba(elementGoomba, { x: 300, y: 55 });
  
    function gameLoop(now = performance.now()) {
      goomba.moure(now);
    
      
      const marioRect = mario.element.getBoundingClientRect();
      const goombaRect = goomba.element.getBoundingClientRect();
    
      if (
        marioRect.left < goombaRect.right &&
        marioRect.right > goombaRect.left &&
        marioRect.top < goombaRect.bottom &&
        marioRect.bottom > goombaRect.top
      ) {
        lives -= 1;
        updateInfoPanel();
    
        if (lives <= 0) {
          gameOver();
          return;
        }
      }
    
      requestAnimationFrame(gameLoop);
    }
  
    gameLoop();
  });

  let score = 0;
let level = 1;
let lives = 3;
let startTime = Date.now();

function updateInfoPanel() {
  document.getElementById('score').textContent = score;
  document.getElementById('level').textContent = level;
  document.getElementById('lives').textContent = lives;

  const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
  const minutes = String(Math.floor(elapsedTime / 60)).padStart(2, '0');
  const seconds = String(elapsedTime % 60).padStart(2, '0');
  document.getElementById('time').textContent = `${minutes}:${seconds}`;
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'ArrowRight') {
    score += 10; 
  } else if (event.key === 'ArrowLeft') {
    lives -= 1;
  }
});

setInterval(updateInfoPanel, 1000);

function gameLoop(now = performance.now()) {
  goomba.moure(now);

  const marioRect = mario.element.getBoundingClientRect();
  const goombaRect = goomba.element.getBoundingClientRect();

  if (
    marioRect.left < goombaRect.right &&
    marioRect.right > goombaRect.left &&
    marioRect.top < goombaRect.bottom &&
    marioRect.bottom > goombaRect.top &&
    mario.velocitatY >= 0
  ) {
    lives -= 1; 
    updateInfoPanel();

    if (lives <= 0) {
      gameOver(); 
      return;
    }
  }

  requestAnimationFrame(gameLoop);
}

function gameOver() {
  gameState = 'gameOver'; 

  document.body.style.backgroundColor = 'red';

  const gameOverDiv = document.createElement('div');
  gameOverDiv.innerHTML = `
    <div>Game Over</div>
    <div>Puntuació final: ${score}</div>
    <div>Prem enter per reiniciar</div>
  `;
  gameOverDiv.style.position = 'absolute';
  gameOverDiv.style.top = '50%';
  gameOverDiv.style.left = '50%';
  gameOverDiv.style.transform = 'translate(-50%, -50%)';
  gameOverDiv.style.color = 'white';
  gameOverDiv.style.fontSize = '32px';
  gameOverDiv.style.fontFamily = 'Press Start 2P, monospace';
  gameOverDiv.style.textAlign = 'center';
  document.body.appendChild(gameOverDiv);

  goomba.velocitat.x = 0;
  mario.velocitatY = 0;

  document.removeEventListener('keydown', handleMarioMovement);

  cancelAnimationFrame(gameLoop);

  document.addEventListener('keydown', restartGameOnSpace);
}
function handleMarioMovement(event) {
  if (gameState !== 'play') return; 

  if (event.key === 'ArrowLeft') {
    mario.moure('esquerra');
  } else if (event.key === 'ArrowRight') {
    mario.moure('dreta');
  } else if (event.key === 'ArrowUp') {
    mario.saltar();
  }
}

function restartGame() {

  score = 0;
  level = 1;
  lives = 3;
  startTime = Date.now();

  
  document.body.style.backgroundColor = '#87CEEB';


  const gameOverDiv = document.querySelector('div[style*="Game Over"]');
  if (gameOverDiv) {
    gameOverDiv.remove();
  }


  document.addEventListener('keydown', handleMarioMovement);

  goomba.posicio.x = 300;
  goomba.velocitat.x = -1;

  requestAnimationFrame(gameLoop);
}
document.addEventListener('keydown', handleMarioMovement);
function restartGameOnSpace(event) {
  if (event.key === 'enter') {
    document.removeEventListener('keydown', restartGameOnSpace);

    
    gameState = 'play';


    restartGame();
  }

}