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
            // المسار هنا نسبي لملف load_components.js (يجب أن يكونا في نفس المجلد admin/js/)
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
    // 2. دالة تفعيل رابط الشريط الجانبي
    // ------------------------------------------------------------------
    function activateSidebarLink(container) {
        // نستخدم pathname لأنه يعمل بشكل أفضل مع GitHub Pages
        const currentPath = window.location.pathname; 
        const navItems = container.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const itemPath = item.querySelector('a').getAttribute('href'); 
            
            // تحقق إذا كان مسار العنصر جزءًا من المسار الحالي في URL
            if (currentPath.includes(itemPath) && itemPath !== '/mo7adaraty/admin/index.html') {
                 item.classList.add('active');
            }
            
            // حالة خاصة للصفحة الرئيسية (تطابق كامل أو تطابق مسار الجذر /admin/)
            if (itemPath === '/mo7adaraty/admin/index.html') {
                if (currentPath.endsWith('/admin/index.html') || currentPath.endsWith('/admin/')) {
                    item.classList.add('active');
                }
            }
        });
        
        // جلب اسم المستخدم وعرضه
        // يجب أن نعتمد على Supabase هنا لجلب بيانات المستخدم
        supabase.auth.getSession().then(({ data: { session } }) => {
            if (session && session.user) {
                // نفترض أن اسم المستخدم مخزن في metadata أو أننا سنجلبه لاحقاً
                // نضع قيمة افتراضية مؤقتة
                const usernameDisplay = document.getElementById('admin-username-display');
                if (usernameDisplay) {
                     usernameDisplay.textContent = session.user.email.split('@')[0] || 'المدير';
                }
            }
        });

        // ------------------------------------------------------------------
        // 3. منطق تسجيل الخروج
        // ------------------------------------------------------------------
        const logoutBtn = document.getElementById('admin-logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const { error } = await supabase.auth.signOut(); 
                if (!error) {
                    // التوجيه لصفحة تسجيل الدخول (يجب تعديل المسار ليتناسب مع موقع ملف login.html)
                    window.location.href = '/mo7adaraty/login.html'; 
                } else {
                    console.error('Logout error:', error);
                    alert('فشل في تسجيل الخروج.');
                }
            });
        }
    }

    // 4. استدعاء التحميل (مع تحديد اسم الملف فقط هنا، والدالة تتولى إضافة المسار)
    loadComponent(sidebarContainer, 'sidebar.html', true); 
    loadComponent(headerContainer, 'header.html');
});