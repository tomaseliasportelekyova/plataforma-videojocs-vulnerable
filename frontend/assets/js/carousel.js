// En frontend/assets/js/carousel.js

document.addEventListener('DOMContentLoaded', async () => {
  const slideA = document.getElementById('slide-a');
  const slideB = document.getElementById('slide-b');

  const allImageUrls = [
    '../frontend/imatges/imagenes_registro/cod6.png',
    '../frontend/imatges/imagenes_registro/gta5.webp',
    '../frontend/imatges/imagenes_registro/elden_ring.jpg',
    '../frontend/imatges/imagenes_registro/death_stranding.png',
    '../frontend/imatges/imagenes_registro/satisfactory.jpg',
  ];

  // --- FUNCIÓN DE PRECARGA (La mantenemos, es una buena práctica) ---
  function preloadImages(urls) {
    const promises = urls.map(url => {
      return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve(url);
        img.onerror = () => {
          console.warn(`Error al cargar: ${url}. Se omitirá.`);
          resolve(null);
        };
        img.src = url;
      });
    });
    return Promise.all(promises);
  }

  // --- LÓGICA PRINCIPAL DEL CARRUSEL DE FUNDIDO ---
  async function initializeCarousel() {
    const validUrls = (await preloadImages(allImageUrls)).filter(url => url !== null);
    if (validUrls.length < 2) {
      console.error("Se necesitan al menos 2 imágenes válidas.");
      // Si solo hay una imagen, la ponemos fija y terminamos
      if (validUrls.length === 1) slideA.style.backgroundImage = `url('${validUrls[0]}')`;
      return;
    }

    // Barajamos para que el orden sea aleatorio
    for (let i = validUrls.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [validUrls[i], validUrls[j]] = [validUrls[j], validUrls[i]];
    }

    let currentImageIndex = 0;
    let isSlideA_Active = true;

    // Establecemos la primera imagen
    slideA.style.backgroundImage = `url('${validUrls[currentImageIndex]}')`;
    currentImageIndex++;

    setInterval(() => {
      const nextImageUrl = validUrls[currentImageIndex];
      
      if (isSlideA_Active) {
        // La imagen activa es A. Ponemos la nueva en B y la hacemos visible.
        slideB.style.backgroundImage = `url('${nextImageUrl}')`;
        slideB.style.opacity = 1;
      } else {
        // La imagen activa es B. Ponemos la nueva en A y la hacemos visible.
        slideA.style.backgroundImage = `url('${nextImageUrl}')`;
        slideA.style.opacity = 1;
      }

      // Después de un breve instante, ocultamos la imagen antigua para la próxima transición
      setTimeout(() => {
        if (isSlideA_Active) {
          slideA.style.opacity = 0;
        } else {
          slideB.style.opacity = 0;
        }
        // Invertimos el slide activo
        isSlideA_Active = !isSlideA_Active;
      }, 50); // Un pequeño retardo para asegurar que la transición empieza correctamente

      // Avanzamos al siguiente índice de imagen
      currentImageIndex = (currentImageIndex + 1) % validUrls.length;
    }, 7000); // Cambia de imagen cada 7 segundos
  }

  initializeCarousel();
});