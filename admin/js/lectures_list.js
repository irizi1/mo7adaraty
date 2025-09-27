// mo7adaraty/admin/lectures/js/lectures_list.js

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('lectures-table-body');
    const table = document.getElementById('lectures-table');
    const loadingMessage = document.getElementById('loading-message');
    const noDataMessage = document.getElementById('no-data-message');

    // دالة جلب المحاضرات من Supabase
    async function fetchLectures() {
        loadingMessage.style.display = 'block';
        table.style.display = 'none';
        noDataMessage.style.display = 'none';
        tableBody.innerHTML = ''; // تفريغ الجدول

        try {
            // جلب البيانات: اسم المحاضرة، تاريخ النشر، الحالة، والمقرر (للتصنيف)
            const { data: lectures, error } = await supabase
                .from('lectures')
                .select('lecture_id, title, status, published_at, offering_subjects(offering_id, offering_name:offerings(name))')
                .order('published_at', { ascending: false }); // ترتيب تنازلي حسب التاريخ

            if (error) {
                console.error('Error fetching lectures:', error);
                loadingMessage.textContent = 'حدث خطأ أثناء جلب المحاضرات.';
                return;
            }

            if (!lectures || lectures.length === 0) {
                loadingMessage.style.display = 'none';
                noDataMessage.style.display = 'block';
                return;
            }

            // ملء الجدول بالبيانات
            let rowNumber = 1;
            lectures.forEach(lecture => {
                const category = lecture.offering_subjects?.offering_name?.name || 'غير محدد';
                const statusClass = lecture.status === 'published' ? 'status-published' : 
                                    lecture.status === 'pending' ? 'status-pending' : 'status-draft';
                const statusText = lecture.status === 'published' ? 'منشورة' : 
                                   lecture.status === 'pending' ? 'مراجعة' : 'مسودة';

                const row = `
                    <tr>
                        <td>${rowNumber++}</td>
                        <td>${lecture.title}</td>
                        <td>${category}</td>
                        <td>${lecture.published_at ? new Date(lecture.published_at).toLocaleDateString('ar-EG', { year: 'numeric', month: 'numeric', day: 'numeric' }) : '---'}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <a href="edit.html?id=${lecture.lecture_id}" class="action-btn btn-edit">تعديل</a>
                            <button class="action-btn btn-delete" data-id="${lecture.lecture_id}">حذف</button>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });

            // إظهار الجدول وإخفاء رسالة التحميل
            loadingMessage.style.display = 'none';
            table.style.display = 'table';
            
            // إضافة مستمعي أحداث للحذف (يفترض وجود دالة handleDeleteLecture)
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', (e) => handleDeleteLecture(e.target.dataset.id));
            });

        } catch (e) {
            console.error('General error in fetchLectures:', e);
            loadingMessage.textContent = 'حدث خطأ عام أثناء التحميل.';
        }
    }
    
    // دالة معالجة الحذف (يجب أن تستدعي Supabase)
    async function handleDeleteLecture(lectureId) {
        if (!confirm(`هل أنت متأكد من حذف المحاضرة رقم ${lectureId}؟`)) {
            return;
        }
        
        try {
            const { error } = await supabase
                .from('lectures')
                .delete()
                .eq('lecture_id', lectureId);

            if (error) {
                alert('فشل في عملية الحذف: ' + error.message);
                return;
            }
            
            alert('تم حذف المحاضرة بنجاح.');
            fetchLectures(); // إعادة تحميل القائمة
            
        } catch (e) {
            console.error('Deletion error:', e);
            alert('حدث خطأ أثناء الاتصال بالخادم للحذف.');
        }
    }

    // بدء جلب البيانات
    fetchLectures();
});