const canvas = document.getElementById('traffic-graph');
const ctx = canvas.getContext('2d');

const users = [0, 5000, 10000, 15000, 20000, 21589];
const offers = [0, 2000, 4000, 6000, 8000, 10000];

ctx.beginPath();
ctx.moveTo(50, 250);
ctx.lineTo(550, 250);
ctx.moveTo(50, 50);
ctx.lineTo(50, 250);
ctx.strokeStyle = '#666666';
ctx.stroke();

ctx.fillStyle = '#666666';
ctx.font = '12px Roboto';
ctx.fillText('0K', 30, 250);
ctx.fillText('5K', 30, 200);
ctx.fillText('10K', 30, 150);
ctx.fillText('15K', 30, 100);
ctx.fillText('20K', 30, 50);
ctx.fillText('25K', 30, 0);

ctx.beginPath();
ctx.strokeStyle = '#6B5B95';
users.forEach((value, index) => {
    const x = 50 + index * 100;
    const y = 250 - (value / 25000) * 200;
    if (index === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
});
ctx.stroke();

ctx.beginPath();
ctx.strokeStyle = '#FF6F61';
ctx.setLineDash([5, 5]);
offers.forEach((value, index) => {
    const x = 50 + index * 100;
    const y = 250 - (value / 25000) * 200;
    if (index === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
});
ctx.stroke();
ctx.setLineDash([]);

ctx.beginPath();
ctx.arc(550, 250 - (21589 / 25000) * 200, 5, 0, 2 * Math.PI);
ctx.fillStyle = '#6B5B95';
ctx.fill();

ctx.fillStyle = '#333333';
ctx.fillText('21,589', 560, 250 - (21589 / 25000) * 200 - 10);

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
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