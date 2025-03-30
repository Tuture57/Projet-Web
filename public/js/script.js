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
    const filterBtn = document.querySelector('.filter-btn');
    const filterPanel = document.getElementById('filterPanel');
    const closeFilterPanel = document.getElementById('closeFilterPanel');

    filterBtn.addEventListener('click', function() {
        filterPanel.style.display = 'block';
    });

    closeFilterPanel.addEventListener('click', function() {
        filterPanel.style.display = 'none';
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const filterBtn = document.querySelector('.filter-btn');
    const filterPanel = document.getElementById('filterPanel');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterOverlay = document.getElementById('filterOverlay');
    const activeFilters = document.getElementById('activeFilters');
    
    // Fonction pour ouvrir le panneau
    function openFilterPanel() {
        filterPanel.classList.add('active');
        filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Fonction pour fermer le panneau
    function closeFilterPanel() {
        filterPanel.classList.remove('active');
        filterOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    
    // Ouvrir le panneau lorsqu'on clique sur le bouton de filtre
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            openFilterPanel();
        });
    }
    
    // Fermer le panneau lorsqu'on clique sur le bouton de fermeture
    if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', function() {
            closeFilterPanel();
        });
    }
    
    // Fermer le panneau lorsqu'on clique sur l'overlay
    if (filterOverlay) {
        filterOverlay.addEventListener('click', function() {
            closeFilterPanel();
        });
    }
    
    // Afficher les filtres actifs sous forme de tags
    function displayActiveFilters() {
        if (!activeFilters) return;
        
        // Vider le conteneur
        activeFilters.innerHTML = '';
        
        // Récupérer les paramètres de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const filterLabels = {
            'country': 'Pays',
            'city': 'Ville',
            'minRating': 'Note min.',
            'sortBy': 'Tri'
        };
        
        const sortValues = {
            'name_asc': 'Nom (A-Z)',
            'name_desc': 'Nom (Z-A)',
            'rating_desc': 'Meilleure note',
            'rating_asc': 'Note la plus basse'
        };
        
        // Liste des paramètres à ignorer (non considérés comme filtres)
        const ignoredParams = ['search', '_page', 'page', 'p', 'per_page', 'limit', 'offset'];
        
        // Créer un tag pour chaque filtre actif
        for (const [key, value] of urlParams.entries()) {
            // Vérifier si le paramètre n'est pas à ignorer et a une valeur
            if (value && !ignoredParams.includes(key)) {
                let displayValue = value;
                
                // Format spécifique pour certains filtres
                if (key === 'minRating') {
                    let stars = '';
                    for (let i = 0; i < parseInt(value); i++) {
                        stars += '★';
                    }
                    displayValue = stars;
                } else if (key === 'sortBy') {
                    displayValue = sortValues[value] || value;
                }
                
                const filterLabel = filterLabels[key] || key;
                
                // Créer le tag
                const tagElement = document.createElement('div');
                tagElement.className = 'filter-tag';
                tagElement.innerHTML = `
                    ${filterLabel}: ${displayValue}
                    <span class="remove-filter" data-filter="${key}">
                        <i class="fas fa-times"></i>
                    </span>
                `;
                
                activeFilters.appendChild(tagElement);
            }
        }
        
        // Ajouter des event listeners pour supprimer les filtres
        document.querySelectorAll('.remove-filter').forEach(button => {
            button.addEventListener('click', function() {
                const filterToRemove = this.getAttribute('data-filter');
                removeFilter(filterToRemove);
            });
        });
    }
    
    // Fonction pour supprimer un filtre et recharger la page
    function removeFilter(filterName) {
        const url = new URL(window.location.href);
        url.searchParams.delete(filterName);
        window.location.href = url.toString();
    }
    
    // Initialiser l'affichage des filtres actifs au chargement
    displayActiveFilters();
});