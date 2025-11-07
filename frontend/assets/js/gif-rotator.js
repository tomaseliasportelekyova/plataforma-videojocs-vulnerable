// En frontend/assets/js/gif-rotator.js
document.addEventListener('DOMContentLoaded', async () => { // Marcamos la función como async
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

// --- FUNCIÓN DE PRECARGA PARA GIFs ---
    function preloadGifs(urls) {
        console.log("Precargando GIFs...");
        const promises = urls.map(url => {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(url); // Resuelve cuando el GIF se carga
                img.onerror = () => {
                    console.warn(`Error al cargar GIF: ${url}. Se omitirá.`);
                    resolve(null); // Resuelve con null si hay error
                };
                img.src = url;
            });
        });
        // Espera a que todos los GIFs se carguen (o fallen)
        return Promise.all(promises);
    }

    // --- INICIO DE LA LÓGICA ---
    
    // 1. Precargamos los GIFs y filtramos los que fallaron
    const loadedGifUrls = await preloadGifs(gifSources);
    const validGifUrls = loadedGifUrls.filter(url => url !== null);

    if (validGifUrls.length === 0) {
        console.error("No se pudo cargar ningún GIF válido.");
        return; // Detenemos si no hay GIFs
    }
    
    console.log(`GIFs cargados: ${validGifUrls.length}. Iniciando rotación...`);

    let currentGifIndex = 0;

    // Función para cambiar el GIF
    function changeGif() {
        // Pasamos al siguiente GIF en la lista VÁLIDA
        currentGifIndex = (currentGifIndex + 1) % validGifUrls.length;
        
        // Cambiamos la fuente del <img>
        gifElement.src = validGifUrls[currentGifIndex];
    }

    // Establecemos el primer GIF
    gifElement.src = validGifUrls[0];

    // Cambiamos de GIF cada 10 segundos (ajusta si es necesario)
    setInterval(changeGif, 10000);
});