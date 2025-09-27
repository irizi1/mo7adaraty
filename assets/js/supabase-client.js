// mo7adaraty/assets/js/supabase-client.js

// 1. تعريف مفاتيح مشروع Supabase
// ************************************************
// !! هام جداً: يجب استبدال هذه القيم بالمعلومات الخاصة بمشروعك !!
// ************************************************
const SUPABASE_URL = 'https://fvekwrzefkpdoxmtitqo.supabase.co'; // مثال: https://xyz123abc.supabase.co
const SUPABASE_ANON_KEY = 'sb_publishable_sVx5DmMUlx_aCSA2wOI4yQ_eTMORwVZ'; // مفتاح anon العام لمشروعك
// mo7adaraty/assets/js/supabase-client.js

// 1. تعريف مفاتيح مشروع Supabase
// ************************************************
// !! هام جداً: يجب استبدال هذه القيم بالمعلومات الخاصة بمشروعك !!
// 2. تهيئة عميل Supabase
// يتم استخدام window.supabase (الذي يتم تحميله عبر CDN)
const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// 3. دالة التحقق من صلاحية الأدمن
// هذه الدالة ضرورية لحماية صفحة الأدمن بالكامل
async function checkAdminAuth() {
    // 1. تحقق من وجود جلسة
    const { data, error } = await supabase.auth.getSession();

    if (error || !data.session) {
        window.location.href = '/mo7adaraty/login.html';
        return false;
    }

    // 2. جلب دور المستخدم والتحقق منه
    // يفترض وجود جدول 'users' يضم عمود 'role' ومعرف 'user_id'
    const { data: userData, error: userError } = await supabase
        .from('users') 
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

console.log('Supabase Client Initialized.');