document.addEventListener('DOMContentLoaded', function() {
    // Sélection des formulaires à valider
    const forms = document.querySelectorAll('.user-form, .company-form, .application-form');
    
    forms.forEach(form => {
        // Ajout de la validation au moment de la soumission
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validation des champs obligatoires
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    highlightField(field, 'Ce champ est requis');
                } else {
                    removeHighlight(field);
                }
            });
            
            // Validation du format d'email
            const emailFields = form.querySelectorAll('[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !validateEmail(field.value)) {
                    isValid = false;
                    highlightField(field, 'Format d\'email invalide');
                }
            });
            
            // Validation du format de mot de passe
            const passwordFields = form.querySelectorAll('input[name*="password"]');
            passwordFields.forEach(field => {
                if (field.value && field.hasAttribute('pattern') && !field.validity.valid) {
                    isValid = false;
                    highlightField(field, 'Le mot de passe ne respecte pas les critères de sécurité');
                }
            });
            
            // Vérification de la correspondance des mots de passe
            const passwordFirst = form.querySelector('input[name*="password"][name*="first"]');
            const passwordSecond = form.querySelector('input[name*="password"][name*="second"]');
            if (passwordFirst && passwordSecond && passwordFirst.value !== passwordSecond.value) {
                isValid = false;
                highlightField(passwordSecond, 'Les mots de passe ne correspondent pas');
            }
            
            if (!isValid) {
                event.preventDefault();
                
                // Défilement vers la première erreur
                const firstError = form.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    // Fonction pour valider le format d'email
    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }
    
    // Fonction pour mettre en évidence les champs avec erreur
    function highlightField(field, message) {
        removeHighlight(field);
        field.classList.add('is-invalid');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error text-danger mt-xs';
        errorElement.textContent = message;
        
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }
    
    // Fonction pour retirer la mise en évidence des erreurs
    function removeHighlight(field) {
        field.classList.remove('is-invalid');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
});
