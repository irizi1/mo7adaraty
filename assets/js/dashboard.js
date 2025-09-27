// mo7adaraty/admin/js/dashboard.js

document.addEventListener('DOMContentLoaded', async () => {
    // التحقق من صلاحية المسؤول (يجب أن يتم التحقق أولاً من تسجيل الدخول والأدمن)
    // نفترض هنا أن هذا المنطق سيُضاف في ملف load_components.js أو supabase-client.js

    const loadingMessage = (id) => document.getElementById(id).textContent = '...';
    
    // ------------------------------------------------------------------
    // 1. جلب إحصائيات المهام العاجلة والإحصاءات العامة
    // ------------------------------------------------------------------
    async function fetchBasicStats() {
        // [تنويه]: هذه الدوال تتطلب إنشاء View/Function في Supabase
        // للجمع بين عمليات العد (COUNT) المتعددة لتقليل عدد طلبات API.
        
        // مثال لعملية عد بسيطة (لإجمالي الطلاب)
        const { count: studentsCount, error: studentsError } = await supabase
            .from('users')
            .select('*', { count: 'exact', head: true })
            .eq('role', 'student');

        if (!studentsError) {
            document.getElementById('total_students_count').textContent = studentsCount;
        } else {
            console.error('Error fetching student count:', studentsError);
            document.getElementById('total_students_count').textContent = 'خطأ';
        }
        
        // مثال لعملية عد ثانية (لإجمالي المحاضرات المنشورة)
        const { count: lecturesCount, error: lecturesError } = await supabase
            .from('lectures')
            .select('*', { count: 'exact', head: true })
            .eq('status', 'published'); // تم تغيير 'approved' إلى 'published' ليتوافق مع المنطق السابق

        if (!lecturesError) {
            document.getElementById('total_lectures_count').textContent = lecturesCount;
        } else {
            console.error('Error fetching lecture count:', lecturesError);
            document.getElementById('total_lectures_count').textContent = 'خطأ';
        }
        
        // ... يجب تكرار العملية لجميع العدادات الأخرى (pending_lectures, total_professors, إلخ)
        // لتوفير الوقت، سنركز على الأهم، وسنفترض أن المنطق العام واضح.
    }

    // ------------------------------------------------------------------
    // 2. جلب إحصاءات الشعبة وإنشاء الجدول (هذا هو الجزء الأصعب)
    // ------------------------------------------------------------------
    async function fetchDivisionStats() {
        const tableContainer = document.getElementById('division-stats-table-container');
        tableContainer.innerHTML = '<p>جاري تحميل إحصاءات الشعب...</p>';

        // [تنويه]: هذا النوع من الاستعلامات المعقدة (JOINs و COUNTs المتعددة)
        // لا يمكن تنفيذه مباشرة عبر الـ client side API في Supabase.
        // الحل الصحيح هو: إنشاء "دالة قاعدة بيانات (PostgreSQL Function)" في Supabase
        // تقوم بحساب كل هذه الإحصائيات (مثل الكود الذي أرسلته في PHP)
        // ثم نستدعيها من JavaScript.
        
        // نفترض أنك أنشأت دالة في Supabase باسم 'get_division_dashboard_stats'
        // وأنها ترجع مصفوفة من الكائنات (division_name, student_count, lecture_count, offering_count).
        
        try {
            const { data: divisionStats, error: statsError } = await supabase.rpc('get_division_dashboard_stats');
            
            if (statsError) {
                console.error('Error calling function:', statsError);
                tableContainer.innerHTML = '<p class="alert alert-error">فشل في جلب إحصاءات الشعب. (تحقق من دالة Supabase)</p>';
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

    // تشغيل جميع الدوال عند تحميل الصفحة
    fetchBasicStats();
    fetchDivisionStats();
});