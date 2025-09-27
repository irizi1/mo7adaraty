// load_components.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. تحديد العناصر المستهدفة
    const sidebarContainer = document.getElementById('sidebar-container');
    const headerContainer = document.getElementById('header-container');

    // 2. دالة جلب المحتوى
    async function loadComponent(container, filePath) {
        if (!container) return; // تأكد من وجود العنصر

        try {
            const response = await fetch(filePath);
            const html = await response.text();
            container.innerHTML = html;
        } catch (error) {
            console.error(`Error loading component from ${filePath}:`, error);
        }
    }

    // 3. استدعاء التحميل
    loadComponent(sidebarContainer, '../components/sidebar.html');
    loadComponent(headerContainer, '../components/header.html');
});