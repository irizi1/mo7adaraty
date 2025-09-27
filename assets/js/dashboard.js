// admin/assets/js/dashboard.js
import supabase from '../../assets/js/supabase.js';

$(document).ready(function() {
    // تحميل الهيدر والسايدبار
    $("#header-placeholder").load("../templates/header.html");
    $("#sidebar-placeholder").load("templates/sidebar.html");

    // التحقق من صلاحيات الأدمن
    async function checkAdmin() {
        const { data: { session } } = await supabase.auth.getSession();
        if (!session) {
            window.location.href = '../login.html';
            return;
        }

        const { data: profile, error } = await supabase
            .from('profiles')
            .select('role')
            .eq('id', session.user.id)
            .single();

        if (error || !profile || profile.role !== 'admin') {
            alert('ليس لديك صلاحية الوصول لهذه الصفحة.');
            window.location.href = '../home.html';
        }
    }
    checkAdmin();

    // جلب الإحصائيات
    async function fetchStats() {
        // المهام العاجلة
        const { count: pendingLectures } = await supabase.from('lectures').select('*', { count: 'exact', head: true }).eq('status', 'pending');
        const { count: pendingPosts } = await supabase.from('posts').select('*', { count: 'exact', head: true }).eq('status', 'pending');
        // ... أكمل باقي إحصائيات المهام العاجلة

        // الإحصائيات العامة
        const { count: totalStudents } = await supabase.from('profiles').select('*', { count: 'exact', head: true }).eq('role', 'student');
        const { count: totalProfessors } = await supabase.from('professors').select('*', { count: 'exact', head: true });
        const { count: totalLectures } = await supabase.from('lectures').select('*', { count: 'exact', head: true }).eq('status', 'approved');
        const { count: totalExams } = await supabase.from('exams').select('*', { count: 'exact', head: true });
        
        // عرض الإحصائيات
        $('#pending-lectures').text(pendingLectures || 0);
        $('#pending-posts').text(pendingPosts || 0);
        $('#total-students').text(totalStudents || 0);
        $('#total-professors').text(totalProfessors || 0);
        $('#total-lectures').text(totalLectures || 0);
        $('#total-exams').text(totalExams || 0);
    }
    fetchStats();
});
