// استيراد مكتبة Supabase من الـ CDN الرسمي
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';

// =================================================================
// ⚠️ هام: استبدل القيم التالية بالبيانات الخاصة بمشروعك
// يمكنك العثور عليها في لوحة تحكم مشروعك على Supabase
// اذهب إلى Settings > API
// =================================================================

const supabaseUrl = 'https://fvekwrzefkpdoxmtitqo.supabase.co';         // الصق هنا Project URL
const supabaseKey = 'sb_publishable_sVx5DmMUlx_aCSA2wOI4yQ_eTMORwVZ';    // الصق هنا Project API Key (anon public)

// إنشاء وتصدير العميل (client) الخاص بـ Supabase
const supabase = createClient(supabaseUrl, supabaseKey);

export default supabase;
