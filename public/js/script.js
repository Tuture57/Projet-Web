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
