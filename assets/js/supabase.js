// استيراد مكتبة Supabase من الـ CDN الرسمي
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';

// =================================================================
// ⚠️ هام: استبدل القيم التالية بالبيانات الخاصة بمشروعك
// يمكنك العثور عليها في لوحة تحكم مشروعك على Supabase
// اذهب إلى Settings > API
// =================================================================

const supabaseUrl = 'YOUR_SUPABASE_URL';         // الصق هنا Project URL
const supabaseKey = 'YOUR_SUPABASE_ANON_KEY';    // الصق هنا Project API Key (anon public)

// إنشاء وتصدير العميل (client) الخاص بـ Supabase
const supabase = createClient(supabaseUrl, supabaseKey);

export default supabase;
