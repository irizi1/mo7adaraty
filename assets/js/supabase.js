// استيراد مكتبة Supabase من الـ CDN الرسمي
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';

// =================================================================
// ⚠️ هام: استبدل القيم التالية بالبيانات الخاصة بمشروعك
// يمكنك العثور عليها في لوحة تحكم مشروعك على Supabase
// اذهب إلى Settings > API
// =================================================================

const supabaseUrl = 'https://fvekwrzefkpdoxmtitqo.supabase.co';         // الصق هنا Project URL
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZ2ZWt3cnplZmtwZG94bXRpdHFvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTg4MjU1MzgsImV4cCI6MjA3NDQwMTUzOH0.B8sUHr3mS2xGLZg4X0jgrj7S2h9jhQxPLCzyBv8Qa6g';    // الصق هنا Project API Key (anon public)

// إنشاء وتصدير العميل (client) الخاص بـ Supabase
const supabase = createClient(supabaseUrl, supabaseKey);

export default supabase;
