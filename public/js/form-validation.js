document.addEventListener('DOMContentLoaded', function() {
    // Target all forms that need validation
    const forms = document.querySelectorAll('.user-form, .company-form, .application-form');
    
    forms.forEach(form => {
        // Add validation before submit
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    highlightField(field, 'Ce champ est requis');
                } else {
                    removeHighlight(field);
                }
            });
            
            // Email validation
            const emailFields = form.querySelectorAll('[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !validateEmail(field.value)) {
                    isValid = false;
                    highlightField(field, 'Format d\'email invalide');
                }
            });
            
            // Password pattern validation
            const passwordFields = form.querySelectorAll('input[name*="password"]');
            passwordFields.forEach(field => {
                if (field.value && field.hasAttribute('pattern') && !field.validity.valid) {
                    isValid = false;
                    highlightField(field, 'Le mot de passe ne respecte pas les critères de sécurité');
                }
            });
            
            // Check if password confirmation matches
            const passwordFirst = form.querySelector('input[name*="password"][name*="first"]');
            const passwordSecond = form.querySelector('input[name*="password"][name*="second"]');
            if (passwordFirst && passwordSecond && passwordFirst.value !== passwordSecond.value) {
                isValid = false;
                highlightField(passwordSecond, 'Les mots de passe ne correspondent pas');
            }
            
            if (!isValid) {
                event.preventDefault();
                
                // Scroll to first error
                const firstError = form.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }
    
    function highlightField(field, message) {
        // Remove any existing error
        removeHighlight(field);
        
        // Add error class to field
        field.classList.add('is-invalid');
        
        // Create error message element
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error text-danger mt-xs';
        errorElement.textContent = message;
        
        // Insert error message after field
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }
    
    function removeHighlight(field) {
        field.classList.remove('is-invalid');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
});
