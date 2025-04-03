document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (!sidebarToggle) {
        console.error("L'élément avec l'ID 'sidebar-toggle' n'a pas été trouvé!");
        return;
    }
    
    if (!sidebar) {
        console.error("L'élément avec la classe 'sidebar' n'a pas été trouvé!");
        return;
    }
    
    // Restaure l'état de la barre latérale à partir du stockage local
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
    // Récupération des éléments du DOM
    const filterBtn = document.querySelector('.filter-btn');
    const filterPanel = document.getElementById('filterPanel');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterOverlay = document.getElementById('filterOverlay');
    const activeFilters = document.getElementById('activeFilters');
    
    if (!filterBtn || !filterPanel || !closeFilterBtn || !filterOverlay) {
        console.error("Éléments manquants pour les filtres:", {
            filterBtn: !!filterBtn, 
            filterPanel: !!filterPanel, 
            closeFilterBtn: !!closeFilterBtn, 
            filterOverlay: !!filterOverlay
        });
        return;
    }
    
    // Ouvre le panneau de filtres
    function openFilterPanel() {
        filterPanel.classList.add('open');
        filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Empêche le défilement
    }
    
    // Ferme le panneau de filtres
    function closeFilterPanel() {
        filterPanel.classList.remove('open');
        filterOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restaure le défilement
    }
    
    filterBtn.addEventListener('click', openFilterPanel);
    closeFilterBtn.addEventListener('click', closeFilterPanel);
    
    filterOverlay.addEventListener('click', function(event) {
        if (event.target === filterOverlay) {
            closeFilterPanel();
        }
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 767 && filterPanel.classList.contains('open')) {
            closeFilterPanel();
        }
    });
    
    // Affiche les filtres actifs dans l'interface
    function displayActiveFilters() {
        if (!activeFilters) return;
        
        activeFilters.innerHTML = '';
        
        const urlParams = new URLSearchParams(window.location.search);
        
        // Labels pour les différents filtres
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
        
        // Valeurs de tri pour l'affichage
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
        
        // Paramètres à ne pas afficher comme filtres
        const ignoredParams = ['search', 'page', '_page', 'limit', 'per_page', 'offset'];
        
        // Création des tags pour chaque filtre actif
        for (const [key, value] of urlParams.entries()) {
            if (value && !ignoredParams.includes(key)) {
                let displayValue = value;
                
                if (key === 'minRating') {
                    displayValue = '★'.repeat(parseInt(value));
                } else if (key === 'sortBy') {
                    displayValue = sortValues[value] || value;
                }
                
                const filterLabel = filterLabels[key] || key;
                
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
        
        // Ajout d'événements pour pouvoir supprimer les filtres
        document.querySelectorAll('.remove-filter').forEach(button => {
            button.addEventListener('click', function() {
                const filterToRemove = this.getAttribute('data-filter');
                removeFilter(filterToRemove);
            });
        });
    }
    
    // Supprime un filtre et recharge la page
    function removeFilter(filterName) {
        const url = new URL(window.location.href);
        url.searchParams.delete(filterName);
        window.location.href = url.toString();
    }
    
    displayActiveFilters();

    // Configuration de la soumission automatique du formulaire
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

    // Ajout des filtres spécifiques aux offres
    function addSpecificFilters() {
        if (document.getElementById('company') && document.getElementById('activeFilters')) {
            const filterLabels = {
                'company': 'Entreprise',
                'skill': 'Compétence',
                ...filterLabels
            };
        }
    }

    addSpecificFilters();
});