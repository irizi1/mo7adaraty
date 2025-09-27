// mo7adaraty/admin/js/load_components.js

document.addEventListener('DOMContentLoaded', () => {
    const sidebarContainer = document.getElementById('sidebar-container');
    const headerContainer = document.getElementById('header-container');

    // ------------------------------------------------------------------
    // 1. دالة جلب وتحميل المكونات
    // ------------------------------------------------------------------
    async function loadComponent(container, filePath, isSidebar = false) {
        if (!container) return; 

        try {
            // المسار النسبي: نبحث في مجلد components/ الذي هو بجوار مجلد js/
            const response = await fetch(`../components/${filePath}`); 
            const html = await response.text();
            container.innerHTML = html;

            if (isSidebar) {
                activateSidebarLink(container);
            }
        } catch (error) {
            console.error(`Error loading component from components/${filePath}:`, error);
        }
    }

    // ------------------------------------------------------------------
    // 2. دالة تفعيل رابط الشريط الجانبي وإضافة منطق تسجيل الخروج
    // ------------------------------------------------------------------
    function activateSidebarLink(container) {
        const currentPath = window.location.pathname; 
        const navItems = container.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const itemPath = item.querySelector('a').getAttribute('href'); 
            
            // تحقق من أن العنصر هو جزء من المسار الحالي
            if (currentPath.includes(itemPath) && itemPath !== '/mo7adaraty/admin/index.html') {
                 item.classList.add('active');
            }
            
            // حالة خاصة للصفحة الرئيسية
            if (itemPath === '/mo7adaraty/admin/index.html') {
                if (currentPath.endsWith('/admin/index.html') || currentPath.endsWith('/admin/')) {
                    item.classList.add('active');
                }
            }
        });
        
        // جلب اسم المستخدم وعرضه
        if (typeof supabase !== 'undefined') {
             supabase.auth.getSession().then(({ data: { session } }) => {
                if (session && session.user) {
                    const usernameDisplay = document.getElementById('admin-username-display');
                    if (usernameDisplay) {
                        // استخدام البريد الإلكتروني كاسم مستخدم مؤقت
                         usernameDisplay.textContent = session.user.email.split('@')[0] || 'المدير';
                    }
                }
            });
        }


        // منطق تسجيل الخروج
        const logoutBtn = document.getElementById('admin-logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (typeof supabase !== 'undefined') {
                    const { error } = await supabase.auth.signOut(); 
                    if (!error) {
                        window.location.href = '/mo7adaraty/login.html'; 
                    } else {
                        console.error('Logout error:', error);
                        alert('فشل في تسجيل الخروج.');
                    }
                }
            });
        }
    }

    // 3. استدعاء التحميل (مسار الملف من مجلد components/)
    loadComponent(sidebarContainer, 'sidebar.html', true); 
    loadComponent(headerContainer, 'header.html');
});