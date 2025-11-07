document.addEventListener('DOMContentLoaded', () => {

    // --- Lògica del Modal de Valoració ---
    const ratingButton = document.getElementById('rating-button-modal');
    const modalBackdrop = document.getElementById('rating-modal-backdrop');
    const modalCloseButton = document.getElementById('modal-close-button');
    const ratingStarsContainer = document.querySelector('.rating-stars');
    const ratingStars = document.querySelectorAll('.rating-stars .star');
    
    // Obtenim el jocId des del contenidor (el PHP l'ha posat allà)
    const jocId = ratingStarsContainer ? ratingStarsContainer.dataset.jocId : 0;
    
    let currentRating = 0;
    let isSubmittingRating = false; // Per evitar doble clic

    if (ratingButton) {
        ratingButton.addEventListener('click', () => {
            if (modalBackdrop) modalBackdrop.classList.add('visible');
        });
    }

    if (modalCloseButton) {
        modalCloseButton.addEventListener('click', () => {
            if (modalBackdrop) modalBackdrop.classList.remove('visible');
        });
    }
    
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) {
                modalBackdrop.classList.remove('visible');
            }
        });
    }

    ratingStars.forEach(star => {
        star.addEventListener('mouseover', () => {
            if (isSubmittingRating) return;
            const rating = star.dataset.value;
            ratingStars.forEach(s => {
                s.classList.toggle('hover', s.dataset.value <= rating);
            });
        });

        star.addEventListener('mouseout', () => {
            if (isSubmittingRating) return;
            ratingStars.forEach(s => s.classList.remove('hover'));
        });

        star.addEventListener('click', async () => {
            if (isSubmittingRating || jocId === 0) return; // Evitem doble clic o jocId 0
            
            currentRating = star.dataset.value;
            isSubmittingRating = true; // Bloquegem
            
            ratingStars.forEach(s => {
                s.classList.toggle('selected', s.dataset.value <= currentRating);
                s.classList.remove('hover');
            });

            try {
                // === Crida a la API de Valoració ===
                const response = await fetch('api/set_rating.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin', // <-- AIXÒ ÉS LA "COOKIE DE SESSIÓN"
                    body: JSON.stringify({
                        joc_id: jocId,
                        rating: currentRating
                    })
                });

                if (!response.ok) {
                    throw new Error(response.statusText);
                }

                const result = await response.json();

                if (result.success) {
                    // Actualitzem el botó principal
                    if (ratingButton) {
                        ratingButton.innerHTML = `<i class="fas fa-star"></i> Has valorat: ${result.newRating}★`;
                    }
                    // Tanquem el modal
                    setTimeout(() => {
                        if (modalBackdrop) modalBackdrop.classList.remove('visible');
                        isSubmittingRating = false; // Desbloquegem
                    }, 500);
                }
            } catch (error) {
                console.error("Error al valorar:", error);
                alert("Error al guardar la teva valoració. Intenta-ho més tard.");
                isSubmittingRating = false; // Desbloquegem si hi ha error
            }
        });
    });

    // --- Lògica del Botó Wishlist ---
    const wishlistButton = document.getElementById('wishlist-button');
    let isSubmittingWishlist = false; // Per evitar doble clic

    if (wishlistButton) {
        wishlistButton.addEventListener('click', async () => {
            if (isSubmittingWishlist || jocId === 0) return;
            isSubmittingWishlist = true; // Bloquegem

            try {
                // === Crida a la API de Wishlist ===
                const response = await fetch('api/toggle_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin', // <-- AIXÒ ÉS LA "COOKIE DE SESSIÓN"
                    body: JSON.stringify({
                        joc_id: jocId 
                    })
                });

                if (!response.ok) {
                    throw new Error(response.statusText);
                }

                const result = await response.json();

                // Actualitzem el botó segons la resposta de l'API
                if (result.inWishlist) {
                    wishlistButton.classList.add('in-wishlist');
                    wishlistButton.innerHTML = '<i class="fas fa-check"></i> A la teva llista';
                } else {
                    wishlistButton.classList.remove('in-wishlist');
                    wishlistButton.innerHTML = '<i class="fas fa-heart"></i> Añadir a Wishlist';
                }
                
            } catch (error) {
                console.error("Error al actualitzar wishlist:", error);
                alert("Error al actualitzar la teva llista. Intenta-ho més tard.");
            } finally {
                isSubmittingWishlist = false; // Desbloquegem
            }
        });
    }
    
    // --- Lògica del Botó de "Canjear" ---
    const redeemButton = document.getElementById('redeem-button');
    const premiumButton = document.getElementById('premium-button');
    const heroActions = document.querySelector('.hero-actions');
    const jocId_redeem = heroActions ? heroActions.dataset.jocId : 0;
    
    if (redeemButton) {
        redeemButton.addEventListener('click', async () => {
            redeemButton.disabled = true;
            redeemButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Canjeando...';

            try {
                const response = await fetch('api/canjear_juego.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin', // ENVIA LA COOKIE DE SESSIÓ
                    body: JSON.stringify({ joc_id: jocId_redeem })
                });

                if (response.ok) {
                    // ÈXIT! El joc s'ha canviat
                    redeemButton.innerHTML = '<i class="fas fa-check"></i> ¡Obtenido!';
                    // Esperem 1 segon i recarreguem la pàgina
                    setTimeout(() => {
                        window.location.reload(); 
                        // En recarregar, el PHP veurà que ja tenim el joc
                        // i mostrarà el botó "Jugar".
                    }, 1000);
                } else {
                    const error = await response.json();
                    throw new Error(error.error || 'Error desconocido');
                }

            } catch (error) {
                console.error("Error al canjear:", error);
                redeemButton.disabled = false;
                redeemButton.innerHTML = '<i class="fas fa-gift"></i> Canjear (Gratis)';
                alert(`Error: ${error.message}`);
            }
        });
    }

    if (premiumButton) {
        premiumButton.addEventListener('click', () => {
            // Simplement mostrem una alerta
            alert('Aquest és un joc Premium. La funció de pagament no està implementada.');
        });
    }

});