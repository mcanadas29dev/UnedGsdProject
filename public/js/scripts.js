document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-sidebar');
    const sidebar = UIkit.offcanvas('#sidebar');
    
    toggleBtn.addEventListener('click', () => {
        console.log("click ");
        sidebar.toggle();
    });
});
