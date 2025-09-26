// assets/js/home.js
import supabase from './supabase.js';

// انتظر حتى يتم تحميل الصفحة بالكامل
$(document).ready(function() {

    // --- 1. تحميل الهيدر والفوتر ---
    // تأكد من أن هذه المسارات صحيحة
    $('#header-placeholder').load('templates/header.html');
    $('#footer-placeholder').load('templates/footer.html');

    // --- 2. تهيئة مكتبة AOS للأنيميشن ---
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // --- 3. جلب الإحصائيات من Supabase ---
    async function fetchStats() {
        // جلب عدد الطلاب
        const { count: studentsCount } = await supabase
            .from('users')
            .select('*', { count: 'exact', head: true })
            .eq('role', 'student');

        // جلب عدد المحاضرات المقبولة
        const { count: lecturesCount } = await supabase
            .from('lectures')
            .select('*', { count: 'exact', head: true })
            .eq('status', 'approved');
            
        // جلب عدد الامتحانات
        const { count: examsCount } = await supabase
            .from('exams')
            .select('*', { count: 'exact', head: true });

        // جلب عدد المشاركات المقبولة
        const { count: postsCount } = await supabase
            .from('posts')
            .select('*', { count: 'exact', head: true })
            .eq('status', 'approved');

        // تحديث الأرقام في الصفحة
        $('#students-count').attr('data-target', studentsCount || 0);
        $('#lectures-count').attr('data-target', lecturesCount || 0);
        $('#exams-count').attr('data-target', examsCount || 0);
        $('#posts-count').attr('data-target', postsCount || 0);
        
        // إعادة تشغيل عداد الأرقام
        animateCounters();
    }

    // --- 4. تحديث حالة المستخدم (أزرار الدخول/الخروج) ---
    async function updateUserStatus() {
        const { data: { session } } = await supabase.auth.getSession();
        const ctaButtonsContainer = $('.cta-buttons');

        if (session) {
            // المستخدم مسجل دخوله
            ctaButtonsContainer.html(`
                <a href="student/profil.html" data-aos="fade-up" data-aos-delay="100">الذهاب إلى ملفي الشخصي</a>
            `);
        } else {
            // المستخدم زائر
            ctaButtonsContainer.html(`
                <a href="login.html" data-aos="fade-up" data-aos-delay="100">تسجيل الدخول</a>
                <a href="signup.html" class="secondary" data-aos="fade-up" data-aos-delay="200">إنشاء حساب جديد</a>
            `);
        }
    }

    // --- 5. دالة تشغيل عداد الأرقام ---
    function animateCounters() {
        $('.stat-number').each(function () {
            const $this = $(this);
            const target = parseInt($this.attr('data-target'));
            $this.text('0'); // إعادة تعيين
            
            $({ countNum: $this.text()}).animate({
                countNum: target
            }, {
                duration: 2000,
                easing:'linear',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                }
            });
        });
    }

    // --- 6. تشغيل الدوال ---
    fetchStats();
    updateUserStatus();

});
