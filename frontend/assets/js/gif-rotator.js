// En frontend/assets/js/gif-rotator.js

document.addEventListener('DOMContentLoaded', () => {
    const gifElement = document.getElementById('background-gif');
    if (!gifElement) return;

    // === ¡AQUÍ ESTÁ LA CLAVE! ===
    // Reemplaza los nombres de archivo con los nombres REALES de tus GIFs.
    // Asegúrate de que estos archivos estén en la carpeta /frontend/gifs/
    const gifSources = [
        '../frontend/gifs/silksong-shaman-crest-pharloom-gif-anim.gif',
        '../frontend/gifs/animated-gif-showing-some-of-the-gameplay-in-grand-theft-auto-vi.webp',
        // Añade aquí todos los GIFs que quieras
    ];

    // --- El resto del código no necesita cambios ---

    let currentGifIndex = 0;

    // Función para cambiar el GIF
    function changeGif() {
        if (gifSources.length === 0) return; // No hacer nada si no hay GIFs
        
        // Pasamos al siguiente GIF en la lista
        currentGifIndex = (currentGifIndex + 1) % gifSources.length;
        
        // Cambiamos la fuente del <img>
        gifElement.src = gifSources[currentGifIndex];
    }

    // Establecemos el primer GIF al cargar la página
    if (gifSources.length > 0) {
        gifElement.src = gifSources[0];
    }

    // Cambiamos de GIF cada 10 segundos (10000 milisegundos)
    setInterval(changeGif, 10000);
});