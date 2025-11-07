// En frontend/assets/js/interactive-dots.js

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('interactive-dots-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // --- CAMBIO: Ahora el contenedor de la animación es el panel de login ---
    const animationContainer = document.querySelector('.login-panel');
    if (!animationContainer) return;

    let mouse = { x: null, y: null };
    let dots = [];
    let movers = [];

    const options = {
        dotColor: 'rgba(0, 0, 0, 0.15)',
        dotRadius: 1.5,
        gridGap: 25,
        interactionRadius: 80,
        repelForce: 0.5,
        returnForce: 0.03,
        moverColor: 'rgba(0, 0, 0, 0.2)',
        moverRadius: 6,
        moverSpeed: 0.5,
        wanderFactor: 0.1, 
    };

    function resizeCanvas() {
        // El canvas toma el tamaño de su contenedor, el .login-panel
        canvas.width = animationContainer.clientWidth;
        canvas.height = animationContainer.clientHeight;
        createDots();
        if (movers.length === 0) {
            createMovers(3); // 3 círculos dentro del panel
        }
    }

    function createDots() {
        dots = [];
        for (let x = options.gridGap; x < canvas.width; x += options.gridGap) {
            for (let y = options.gridGap; y < canvas.height; y += options.gridGap) {
                dots.push({ originX: x, originY: y, x: x, y: y, vx: 0, vy: 0 });
            }
        }
    }

    function createMovers(count) {
        for (let i = 0; i < count; i++) {
            let angle = Math.random() * Math.PI * 2;
            movers.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                angle: angle,
                vx: Math.cos(angle) * options.moverSpeed,
                vy: Math.sin(angle) * options.moverSpeed,
            });
        }
    }

    // Los eventos del ratón ahora se escuchan en el panel de login
    animationContainer.addEventListener('mousemove', (e) => {
        const rect = animationContainer.getBoundingClientRect();
        mouse.x = e.clientX - rect.left;
        mouse.y = e.clientY - rect.top;
    });

    animationContainer.addEventListener('mouseleave', () => {
        mouse.x = null;
        mouse.y = null;
    });

    // La función animate() no necesita cambios
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        movers.forEach(mover => {
            mover.angle += (Math.random() - 0.5) * options.wanderFactor;
            mover.vx = Math.cos(mover.angle) * options.moverSpeed;
            mover.vy = Math.sin(mover.angle) * options.moverSpeed;
            mover.x += mover.vx;
            mover.y += mover.vy;
            if (mover.x < options.moverRadius) { mover.x = options.moverRadius; mover.angle = Math.PI - mover.angle; }
            else if (mover.x > canvas.width - options.moverRadius) { mover.x = canvas.width - options.moverRadius; mover.angle = Math.PI - mover.angle; }
            if (mover.y < options.moverRadius) { mover.y = options.moverRadius; mover.angle = -mover.angle; }
            else if (mover.y > canvas.height - options.moverRadius) { mover.y = canvas.height - options.moverRadius; mover.angle = -mover.angle; }
            ctx.beginPath();
            ctx.arc(mover.x, mover.y, options.moverRadius, 0, Math.PI * 2);
            ctx.fillStyle = options.moverColor;
            ctx.fill();
        });
        dots.forEach(dot => {
            let totalForceX = 0;
            let totalForceY = 0;
            let dxMouse = mouse.x - dot.x;
            let dyMouse = mouse.y - dot.y;
            let distanceMouse = Math.sqrt(dxMouse * dxMouse + dyMouse * dyMouse);
            if (distanceMouse < options.interactionRadius) {
                let force = (options.interactionRadius - distanceMouse) / options.interactionRadius;
                totalForceX -= (dxMouse / distanceMouse) * force * options.repelForce;
                totalForceY -= (dyMouse / distanceMouse) * force * options.repelForce;
            }
            movers.forEach(mover => {
                let dxMover = mover.x - dot.x;
                let dyMover = mover.y - dot.y;
                let distanceMover = Math.sqrt(dxMover * dxMover + dyMover * dyMover);
                if (distanceMover < options.interactionRadius) {
                    let force = (options.interactionRadius - distanceMover) / options.interactionRadius;
                    totalForceX -= (dxMover / distanceMover) * force * options.repelForce;
                    totalForceY -= (dyMover / distanceMover) * force * options.repelForce;
                }
            });
            dot.vx += (dot.originX - dot.x) * options.returnForce + totalForceX;
            dot.vy += (dot.originY - dot.y) * options.returnForce + totalForceY;
            dot.vx *= 0.9;
            dot.vy *= 0.9;
            dot.x += dot.vx;
            dot.y += dot.vy;
            ctx.beginPath();
            ctx.arc(dot.x, dot.y, options.dotRadius, 0, Math.PI * 2);
            ctx.fillStyle = options.dotColor;
            ctx.fill();
        });
        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
    animate();
});