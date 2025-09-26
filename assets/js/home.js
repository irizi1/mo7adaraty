// assets/js/home.js
import supabase from './supabase.js';

// دالة لجلب الإحصائيات من Supabase
async function fetchStats() {
    // جلب عدد الطلاب
    const { count: studentsCount, error: studentsError } = await supabase
        .from('users')
        .select('*', { count: 'exact', head: true })
        .eq('role', 'student');

    // جلب عدد المحاضرات المقبولة
    const { count: lecturesCount, error: lecturesError } = await supabase
        .from('lectures')
        .select('*', { count: 'exact', head: true })
        .eq('status', 'approved');
        
    // جلب عدد الامتحانات
    const { count: examsCount, error: examsError } = await supabase
        .from('exams')
        .select('*', { count: 'exact', head: true });

    // جلب عدد المشاركات المقبولة
    const { count: postsCount, error: postsError } = await supabase
        .from('posts')
        .select('*', { count: 'exact', head: true })
        .eq('status', 'approved');

    // تحديث الأرقام في الصفحة
    document.getElementById('students-count').setAttribute('data-target', studentsCount || 0);
    document.getElementById('lectures-count').setAttribute('data-target', lecturesCount || 0);
    document.getElementById('exams-count').setAttribute('data-target', examsCount || 0);
    document.getElementById('posts-count').setAttribute('data-target', postsCount || 0);
    
    // إعادة تشغيل عداد الأرقام
    animateCounters();
}

// دالة لتحديث الهيدر وأزرار CTA بناءً على حالة تسجيل الدخول
async function updateUserStatus() {
    const { data: { session } } = await supabase.auth.getSession();
    const ctaButtonsContainer = document.querySelector('.cta-buttons');

    if (session) {
        // المستخدم مسجل دخوله
        ctaButtonsContainer.innerHTML = `
            <a href="student/profil.html" data-aos="fade-up" data-aos-delay="100">الذهاب إلى ملفي الشخصي</a>
        `;
    } else {
        // المستخدم زائر
        ctaButtonsContainer.innerHTML = `
            <a href="login.html" data-aos="fade-up" data-aos-delay="100">تسجيل الدخول</a>
            <a href="signup.html" class="secondary" data-aos="fade-up" data-aos-delay="200">إنشاء حساب جديد</a>
        `;
    }
}

// دالة تشغيل عداد الأرقام
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;

    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            let count = +counter.innerText;
            const inc = Math.ceil(target / speed);

            if (count < target) {
                count += inc;
                if (count > target) {
                    count = target;
                }
                counter.innerText = count;
                setTimeout(updateCount, 10);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });
}


// --- تشغيل الدوال عند تحميل الصفحة ---
document.addEventListener('DOMContentLoaded', function() {
    // تحميل الهيدر والفوتر
    $('#header-placeholder').load('templates/header.html');
    $('#footer-placeholder').load('templates/footer.html');

    // تهيئة مكتبة AOS للأنيميشن
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // جلب الإحصائيات وتحديث حالة المستخدم
    fetchStats();
    updateUserStatus();
});
