document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating label');
    const starInputs = document.querySelectorAll('.star-rating input');
    
    // Au chargement de la page, afficher les étoiles déjà sélectionnées en jaune
    function initializeRating() {
        const checkedInput = document.querySelector('.star-rating input:checked');
        if (checkedInput) {
            const rating = parseInt(checkedInput.value);
            
            stars.forEach(star => {
                const input = document.getElementById(star.getAttribute('for'));
                const starValue = parseInt(input.value);
                const icon = star.querySelector('i');
                
                if (starValue <= rating) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        }
    }
    
    initializeRating();
    
    // Mettre les étoiles en surbrillance lors du survol
    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const hoveredValue = parseInt(document.getElementById(this.getAttribute('for')).value);
            
            stars.forEach(s => {
                const input = document.getElementById(s.getAttribute('for'));
                const value = parseInt(input.value);
                const icon = s.querySelector('i');
                
                if (value <= hoveredValue) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });
    });
    
    // Réinitialiser l'état quand on quitte la zone
    const ratingContainer = document.querySelector('.star-rating');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            initializeRating();
        });
    }
    
    // S'assurer que les bonnes étoiles sont sélectionnées au clic
    starInputs.forEach(input => {
        input.addEventListener('click', function() {
            const selectedValue = parseInt(this.value);
            
            stars.forEach(star => {
                const starInput = document.getElementById(star.getAttribute('for'));
                const value = parseInt(starInput.value);
                const icon = star.querySelector('i');
                
                if (value <= selectedValue) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });
    });
});
