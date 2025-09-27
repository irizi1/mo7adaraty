// mo7adaraty/admin/js/dashboard.js
// هذا الملف مسؤول عن جلب بيانات لوحة القيادة من Supabase وعرضها

document.addEventListener('DOMContentLoaded', async () => {
    // ------------------------------------------------------------------
    // دوال مساعدة لإنشاء وإظهار البيانات
    // ------------------------------------------------------------------
    const getElement = (id) => document.getElementById(id);
    const updateStat = (id, count) => {
        const element = getElement(id);
        if (element) {
            element.textContent = count !== null ? count.toLocaleString('ar-EG') : 'خطأ';
        }
    };
    const showLoading = (id) => {
        const element = getElement(id);
        if (element) {
            element.textContent = '...';
        }
    };

    // ------------------------------------------------------------------
    // 1. جلب الإحصائيات العامة والمهام العاجلة
    // ------------------------------------------------------------------
    async function fetchBasicStats() {
        // نستخدم Promise.all لطلب جميع عمليات العد (COUNT) في وقت واحد لتحسين الأداء
        
        // المهام العاجلة (البيانات التي كانت تأتي من PHP)
        const pendingLecturesPromise = supabase.from('lectures').select('*', { count: 'exact', head: true }).eq('status', 'pending');
        const pendingEnrollmentRequestsPromise = supabase.from('enrollment_change_requests').select('*', { count: 'exact', head: true }).eq('status', 'pending');
        const pendingMessagesPromise = supabase.from('admin_messages').select('*', { count: 'exact', head: true }).eq('status', 'pending_reply');
        const pendingPostsPromise = supabase.from('posts').select('*', { count: 'exact', head: true }).eq('status', 'pending');
        const pendingReportsPromise = supabase.from('reports').select('*', { count: 'exact', head: true }).eq('status', 'pending');

        // الإحصاءات العامة
        const totalStudentsPromise = supabase.from('users').select('*', { count: 'exact', head: true }).eq('role', 'student');
        const totalProfessorsPromise = supabase.from('professors').select('*', { count: 'exact', head: true });
        // تم تغيير 'approved' إلى 'published' أو 'approved' بناءً على تصميم قاعدة بياناتك الفعلية. سنستخدم 'approved' بناءً على كود PHP.
        const totalLecturesPromise = supabase.from('lectures').select('*', { count: 'exact', head: true }).eq('status', 'approved'); 
        const totalExamsPromise = supabase.from('exams').select('*', { count: 'exact', head: true });

        const results = await Promise.all([
            pendingLecturesPromise,
            pendingEnrollmentRequestsPromise,
            pendingMessagesPromise,
            pendingPostsPromise,
            pendingReportsPromise,
            totalStudentsPromise,
            totalProfessorsPromise,
            totalLecturesPromise,
            totalExamsPromise,
        ]);
        
        // عرض النتائج
        updateStat('pending_lectures_count', results[0].count);
        updateStat('pending_enrollment_requests_count', results[1].count);
        updateStat('pending_messages_count', results[2].count);
        updateStat('pending_posts_count', results[3].count);
        updateStat('pending_reports_count', results[4].count);
        
        updateStat('total_students_count', results[5].count);
        updateStat('total_professors_count', results[6].count);
        updateStat('total_lectures_count', results[7].count);
        updateStat('total_exams_count', results[8].count);
    }

    // ------------------------------------------------------------------
    // 2. جلب إحصاءات الشعبة (يتطلب دالة قاعدة بيانات)
    // ------------------------------------------------------------------
    async function fetchDivisionStats() {
        const tableContainer = getElement('division-stats-table-container');
        tableContainer.innerHTML = '<p>جاري تحميل إحصاءات الشعب...</p>';

        try {
            // !! هام !!
            // هذا الاستدعاء يتطلب منك إنشاء دالة (RPC) في Supabase بالاسم التالي.
            // الدالة يجب أن تقوم بتنفيذ استعلامات JOIN و COUNT المعقدة التي كانت في ملف index.php الأصلي.
            const { data: divisionStats, error: statsError } = await supabase.rpc('get_division_dashboard_stats');
            
            if (statsError) {
                console.error('Supabase RPC Error:', statsError);
                tableContainer.innerHTML = '<p class="alert alert-error">فشل في جلب إحصاءات الشعب. (تحقق من دالة Supabase: <code>get_division_dashboard_stats</code>)</p>';
                return;
            }

            if (!divisionStats || divisionStats.length === 0) {
                tableContainer.innerHTML = '<p>لا توجد بيانات لعرضها. قم بإضافة شعب دراسية ومقررات أولاً.</p>';
                return;
            }

            // بناء جدول HTML
            let tableHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>الشعبة الدراسية</th>
                            <th><i class="fa-solid fa-users"></i> عدد الطلاب</th>
                            <th><i class="fa-solid fa-file-alt"></i> المحاضرات المنشورة</th>
                            <th><i class="fa-solid fa-layer-group"></i> المقررات المتاحة</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            divisionStats.forEach(stats => {
                tableHTML += `
                    <tr>
                        <td><strong>${stats.division_name}</strong></td>
                        <td>${stats.student_count || 0}</td>
                        <td>${stats.lecture_count || 0}</td>
                        <td>${stats.offering_count || 0}</td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            tableContainer.innerHTML = tableHTML;
            
        } catch (e) {
            console.error('General Fetch Error:', e);
            tableContainer.innerHTML = '<p class="alert alert-error">حدث خطأ عام أثناء تحميل البيانات.</p>';
        }
    }

    // تشغيل الدوال (بعد التأكد من تهيئة Supabase)
    fetchBasicStats();
    fetchDivisionStats();
});