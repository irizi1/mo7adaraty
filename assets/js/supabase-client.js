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
// ************************************************

// 2. تهيئة عميل Supabase
// تم استخدام window.supabase لحل خطأ 'Cannot access 'supabase' before initialization'
// حيث أن مكتبة CDN تعرف الكائن في window.
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

console.log('Supabase Client Initialized.');

// 3. دالة التحقق من صلاحية الأدمن (مع الإضافات التشخيصية)
async function checkAdminAuth() {
    // 1. تحقق من وجود جلسة
    const { data: sessionData, error: sessionError } = await supabase.auth.getSession();

    if (sessionError || !sessionData.session) {
        window.location.href = '/mo7adaraty/login.html';
        return false;
    }

    const userId = sessionData.session.user.id;
    // [إضافة تشخيصية]
    console.log('User ID:', userId); 

    // 2. جلب دور المستخدم والتحقق منه
    const { data: userData, error: userError } = await supabase
        .from('users') 
        .select('role')
        .eq('user_id', userId) 
        .single();
    
    // [إضافة تشخيصية]
    if (userError) {
        console.error('RLS/Fetch Error (Policy or data access problem):', userError.message);
    } else {
        console.log('Fetched User Data:', userData);
        console.log('Fetched Role:', userData ? userData.role : 'N/A');
    }
    
    // 3. التحقق النهائي من الدور
    if (userError || !userData || userData.role !== 'admin') {
        alert('ليس لديك صلاحيات المسؤول للوصول لهذه الصفحة.');
        window.location.href = '/mo7adaraty/login.html';
        return false;
    }
    
    return true; // المستخدم هو مسؤول ومصادق عليه
}
