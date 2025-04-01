document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Vérification des éléments
    if (!sidebarToggle) {
        console.error("L'élément avec l'ID 'sidebar-toggle' n'a pas été trouvé!");
        return;
    }
    
    if (!sidebar) {
        console.error("L'élément avec la classe 'sidebar' n'a pas été trouvé!");
        return;
    }
    
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
    }
    sidebarToggle.addEventListener('click', function() {
        if (window.innerWidth <= 767) {
            sidebar.classList.toggle('open');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : 'auto';
        } else {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    });
    const menuItems = document.querySelectorAll('.nav-menu li a');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 767) {
                sidebar.classList.remove('open');
                document.body.style.overflow = 'auto';
            }
        });
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 767) {
            sidebar.classList.remove('open');
            document.body.style.overflow = 'auto';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const filterBtn = document.querySelector('.filter-btn');
    const filterPanel = document.getElementById('filterPanel');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterOverlay = document.getElementById('filterOverlay');
    const activeFilters = document.getElementById('activeFilters');
    
    // Quitter si les éléments requis n'existent pas
    if (!filterBtn || !filterPanel || !closeFilterBtn || !filterOverlay) {
        console.error("Éléments manquants pour les filtres:", {
            filterBtn: !!filterBtn, 
            filterPanel: !!filterPanel, 
            closeFilterBtn: !!closeFilterBtn, 
            filterOverlay: !!filterOverlay
        });
        return;
    }
    
    // Fonction pour ouvrir le panneau de filtres
    function openFilterPanel() {
        filterPanel.classList.add('open');
        filterOverlay.style.display = 'block';
        setTimeout(() => {
            filterOverlay.style.opacity = '1';
        }, 10);
    }
    
    // Fonction pour fermer le panneau de filtres
    function closeFilterPanel() {
        filterPanel.classList.remove('open');
        filterOverlay.style.opacity = '0';
        setTimeout(() => {
            filterOverlay.style.display = 'none';
        }, 300);
    }
    
    // Ouvrir le panneau de filtres
    filterBtn.addEventListener('click', openFilterPanel);
    
    // Fermer le panneau de filtres
    closeFilterBtn.addEventListener('click', closeFilterPanel);
    filterOverlay.addEventListener('click', closeFilterPanel);
    
    // Afficher les filtres actifs
    function displayActiveFilters() {
        if (!activeFilters) return;
        
        activeFilters.innerHTML = '';
        
        // Récupérer les paramètres de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        
        // Définir les labels pour les filtres (à personnaliser selon les entités)
        const filterLabels = {
            // Entreprises
            'country': 'Pays',
            'city': 'Ville',
            'minRating': 'Note min.',
            
            // Étudiants
            'promotion': 'Promotion',
            'campus': 'Campus',
            
            // Pilotes
            'department': 'Département',
            
            // Commun
            'sortBy': 'Tri'
        };
        
        const sortValues = {
            // Entreprises
            'name_asc': 'Nom (A-Z)',
            'name_desc': 'Nom (Z-A)',
            'rating_desc': 'Meilleure note',
            'rating_asc': 'Note la plus basse',
            
            // Étudiants
            'lastName_asc': 'Nom (A-Z)',
            'lastName_desc': 'Nom (Z-A)',
            'promotion_asc': 'Promotion (croissant)',
            'promotion_desc': 'Promotion (décroissant)',
            
            // Pilotes
            'department_asc': 'Département (A-Z)',
            'department_desc': 'Département (Z-A)'
        };
        
        // Paramètres à ignorer
        const ignoredParams = ['search', 'page', '_page', 'limit', 'per_page', 'offset'];
        
        // Créer un tag pour chaque filtre actif
        for (const [key, value] of urlParams.entries()) {
            if (value && !ignoredParams.includes(key)) {
                let displayValue = value;
                
                // Format spécifique pour certains filtres
                if (key === 'minRating') {
                    displayValue = '★'.repeat(parseInt(value));
                } else if (key === 'sortBy') {
                    displayValue = sortValues[value] || value;
                }
                
                const filterLabel = filterLabels[key] || key;
                
                // Créer le tag
                const tagElement = document.createElement('div');
                tagElement.className = 'filter-tag';
                tagElement.innerHTML = `
                    <span class="filter-name">${filterLabel}:</span>
                    <span class="filter-value">${displayValue}</span>
                    <span class="remove-filter" data-filter="${key}">
                        <i class="fas fa-times"></i>
                    </span>
                `;
                
                activeFilters.appendChild(tagElement);
            }
        }
        
        // Ajouter les événements pour supprimer les filtres
        document.querySelectorAll('.remove-filter').forEach(button => {
            button.addEventListener('click', function() {
                const filterToRemove = this.getAttribute('data-filter');
                removeFilter(filterToRemove);
            });
        });
    }
    
    // Fonction pour supprimer un filtre
    function removeFilter(filterName) {
        const url = new URL(window.location.href);
        url.searchParams.delete(filterName);
        window.location.href = url.toString();
    }
    
    // Initialiser l'affichage des filtres
    displayActiveFilters();

    // Soumettre automatiquement le formulaire lors du changement de filtre
    const filterSelects = document.querySelectorAll('.filter-select');
    if (filterSelects.length > 0) {
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                const filterForm = document.getElementById('filters-form');
                if (filterForm) {
                    filterForm.submit();
                }
            });
        });
    }

    // Ajouter les filtres spécifiques aux offres
    function addSpecificFilters() {
        // Si nous sommes sur la page des offres avec un filtre d'entreprise
        if (document.getElementById('company') && document.getElementById('activeFilters')) {
            const filterLabels = {
                'company': 'Entreprise',
                'skill': 'Compétence',
                // Ajouter à la liste existante
                ...filterLabels
            };

            // Le reste de la logique pour afficher les filtres est déjà géré
            // par la fonction displayActiveFilters()
        }
    }

    // Appeler cette fonction après displayActiveFilters()
    addSpecificFilters();
});