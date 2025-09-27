// mo7adaraty/assets/js/supabase-client.js

// 1. تعريف مفاتيح مشروع Supabase
// ************************************************
// !! هام جداً: يجب استبدال هذه القيم بالمعلومات الخاصة بمشروعك !!
// ************************************************
const SUPABASE_URL = 'https://fvekwrzefkpdoxmtitqo.supabase.co'; // مثال: https://xyz123abc.supabase.co
const SUPABASE_ANON_KEY = 'sb_publishable_sVx5DmMUlx_aCSA2wOI4yQ_eTMORwVZ'; // مفتاح anon العام لمشروعك

// 2. تهيئة عميل Supabase
// يتم استخدام window.supabase (الذي يتم تحميله عبر CDN)
const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// 3. اختبار الاتصال (اختياري)
console.log('Supabase Client Initialized.');

// يمكن إضافة دوال مساعدة هنا، مثل دالة للتحقق من الأدمن، أو غيرها.

// مثال لدالة تحقق من حالة المصادقة وحماية صفحة الأدمن
async function checkAdminAuth() {
    const { data, error } = await supabase.auth.getSession();

    if (error || !data.session) {
        // لا يوجد مستخدم مسجل الدخول
        window.location.href = '/mo7adaraty/login.html';
        return false;
    }

    // هنا يجب إضافة منطق للتحقق من دور المستخدم (Admin Role)
    // هذا يتطلب وجود حقل 'role' في جدول المستخدمين أو في بيانات الـ metadata الخاصة بـ Supabase
    const { data: userData, error: userError } = await supabase
        .from('users') // نفترض وجود جدول 'users' يضم الأدوار
        .select('role')
        .eq('user_id', data.session.user.id)
        .single();
    
    if (userError || !userData || userData.role !== 'admin') {
        alert('ليس لديك صلاحيات المسؤول للوصول لهذه الصفحة.');
        window.location.href = '/mo7adaraty/login.html';
        return false;
    }
    
    return true; // المستخدم هو مسؤول ومصادق عليه
}