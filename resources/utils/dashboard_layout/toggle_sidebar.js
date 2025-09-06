const roundedSidebar = document.getElementById('rounded-sidebar');
const sidebar = document.getElementById('sidebar');
const btn = document.getElementById('sidebar-menu-btn');
const sidebarBg = document.getElementById('sidebar-bg-addition');

const openSidebar = () => {
    roundedSidebar.classList.remove('z-[-1]');
    roundedSidebar.classList.add('z-10');
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebarBg.classList.remove('hidden');
    sidebarBg.classList.add('block');
    btn.setAttribute('aria-expanded', 'true');
    document.body.classList.add("overflow-hidden"); // Prevent background scrolling
}

const closeSidebar = () => {
    roundedSidebar.classList.remove('z-10');
    roundedSidebar.classList.add('z-[-1]');
    sidebar.classList.remove('translate-x-0');
    sidebar.classList.add('-translate-x-full');
    sidebarBg.classList.remove('block');
    sidebarBg.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove("overflow-hidden"); // Restore background scrolling
}

const toggleSidebar = () => {
    const isOpen = sidebar.classList.contains('translate-x-0');
    if (isOpen) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

btn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleSidebar();
});

sidebarBg?.addEventListener('click', () => {
    closeSidebar();
});