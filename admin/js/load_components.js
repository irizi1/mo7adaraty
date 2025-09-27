// mo7adaraty/admin/js/load_components.js

document.addEventListener('DOMContentLoaded', () => {
    const sidebarContainer = document.getElementById('sidebar-container');
    const headerContainer = document.getElementById('header-container');

    // ------------------------------------------------------------------
    // 1. دالة جلب وتحميل المكونات - تم استخدام المسار النسبي القياسي
    // ------------------------------------------------------------------
    async function loadComponent(container, filePath, isSidebar = false) {
        if (!container) return; 

        try {
            // المسار النسبي القياسي: نصعد خطوة من admin/js/ إلى admin/، ثم ننزل إلى components/
            const relativePath = `../components/${filePath}`;
            const response = await fetch(relativePath);
            
            // التعامل مع خطأ 404 صراحةً
            if (!response.ok) {
                 throw new Error(`Failed to load component: ${relativePath} Status: ${response.status}`);
            }
            
            const html = await response.text();
            container.innerHTML = html;

            if (isSidebar) {
                activateSidebarLink(container);
            }
        } catch (error) {
            console.error('Error loading component:', error);
            // لعرض الخطأ في الواجهة لمزيد من الوضوح
            container.innerHTML = `<p style="color: red; padding: 20px;">خطأ في تحميل المكون: ${error.message}</p>`;
        }
    }

    // ------------------------------------------------------------------
    // 2. دالة تفعيل رابط الشريط الجانبي وإضافة منطق تسجيل الخروج
    // ------------------------------------------------------------------
    function activateSidebarLink(container) {
        const currentPath = window.location.pathname; 
        const navItems = container.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            // نستخدم سمة href من الرابط لتحديد المسار
            const itemHref = item.querySelector('a').getAttribute('href'); 
            
            // تحقق من التطابق (باستثناء /admin/index.html لتجنب تضارب التفعيل)
            if (currentPath.includes(itemHref) && itemHref !== '/mo7adaraty/admin/index.html') {
                 item.classList.add('active');
            }
            
            // حالة خاصة للصفحة الرئيسية (تطابق المسار الكامل أو مسار الجذر /admin/)
            if (itemHref === '/mo7adaraty/admin/index.html') {
                if (currentPath.endsWith('/admin/index.html') || currentPath.endsWith('/admin/')) {
                    item.classList.add('active');
                }
            }
        });
        
        // جلب اسم المستخدم وعرضه ومنطق تسجيل الخروج
        // نفحص أولاً وجود الكائن supabase لتفادي الأخطاء
        if (typeof supabase !== 'undefined') {
             // جلب اسم المستخدم وعرضه
             supabase.auth.getSession().then(({ data: { session } }) => {
                if (session && session.user) {
                    const usernameDisplay = document.getElementById('admin-username-display');
                    if (usernameDisplay) {
                         // استخدام البريد الإلكتروني كاسم مستخدم مؤقت
                         usernameDisplay.textContent = session.user.email.split('@')[0] || 'المدير';
                    }
                }
            });


            // منطق تسجيل الخروج
            const logoutBtn = document.getElementById('admin-logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const { error } = await supabase.auth.signOut(); 
                    if (!error) {
                        window.location.href = '/mo7adaraty/login.html'; 
                    } else {
                        console.error('Logout error:', error);
                        alert('فشل في تسجيل الخروج.');
                    }
                });
            }
        } else {
            console.error("Supabase client is not defined. Check if supabase-client.js is loaded correctly.");
        }
    }

    // 3. استدعاء التحميل
    loadComponent(sidebarContainer, 'sidebar.html', true); 
    loadComponent(headerContainer, 'header.html');
});