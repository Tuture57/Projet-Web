document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating label');
    const starInputs = document.querySelectorAll('.star-rating input');
    
    // Initialise l'affichage des étoiles en fonction de la note déjà sélectionnée
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
    
    // Gestion du survol des étoiles
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
    
    // Rétablissement de l'état initial quand on quitte la zone de notation
    const ratingContainer = document.querySelector('.star-rating');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            initializeRating();
        });
    }
    
    // Gestion du clic sur une étoile
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
