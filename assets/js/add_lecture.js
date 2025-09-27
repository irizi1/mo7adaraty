// mo7adaraty/admin/js/add_lecture.js

// افتراض أن كائن 'supabase' تم تعريفه وتحميله في supabase-client.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('add-lecture-form');
    const titleInput = document.getElementById('title');
    const categoryInput = document.getElementById('category');
    const descriptionInput = document.getElementById('description');
    const urlInput = document.getElementById('url');
    const statusInput = document.getElementById('status');
    const mainContent = document.getElementById('main-content'); // لإظهار الرسائل فيه

    // ------------------------------------------------------------------
    // 1. دالة عرض الرسائل (نجاح/خطأ)
    // ------------------------------------------------------------------
    function displayMessage(message, type = 'success') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type}`;
        messageDiv.textContent = message;
        
        // إزالة أي رسالة سابقة
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        mainContent.insertBefore(messageDiv, mainContent.firstChild);

        // إخفاء الرسالة بعد 5 ثوانٍ
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    // ------------------------------------------------------------------
    // 2. دالة معالجة إرسال النموذج
    // ------------------------------------------------------------------
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // جمع البيانات
        const newLecture = {
            title: titleInput.value.trim(),
            category: categoryInput.value,
            description: descriptionInput.value.trim(),
            url: urlInput.value.trim(),
            status: statusInput.value,
            // يمكن إضافة حقول أخرى هنا (مثل user_id للمسؤول الذي أنشأ المحاضرة)
        };

        // التحقق الأساسي من البيانات
        if (!newLecture.title || !newLecture.description || !newLecture.url) {
            displayMessage('الرجاء تعبئة جميع الحقول المطلوبة.', 'error');
            return;
        }

        // تعطيل الزر لمنع الإرسال المتعدد
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'جاري الحفظ...';
        
        try {
            // 3. الإدراج في قاعدة البيانات (نفترض اسم الجدول هو 'lectures')
            const { data, error } = await supabase
                .from('lectures')
                .insert([newLecture])
                .select(); // لاسترجاع البيانات المدرجة

            if (error) {
                console.error('Supabase Error:', error);
                displayMessage(`فشل في إضافة المحاضرة: ${error.message}`, 'error');
                return;
            }

            // 4. معالجة النجاح
            displayMessage('✅ تم إضافة المحاضرة بنجاح!');
            
            // مسح النموذج وإعادة توجيه المستخدم بعد فترة قصيرة
            form.reset();
            setTimeout(() => {
                // العودة لصفحة قائمة المحاضرات
                window.location.href = 'index.html'; 
            }, 1500); 

        } catch (err) {
            console.error('General Error:', err);
            displayMessage('حدث خطأ غير متوقع أثناء الحفظ.', 'error');
        } finally {
            // إعادة تفعيل الزر في حالة الفشل
            submitButton.disabled = false;
            submitButton.textContent = 'حفظ ونشر المحاضرة';
        }
    });

    // ملاحظة: يجب التأكد من وجود تهيئة لـ Supabase Authentication هنا أو في load_components.js
    // للتحقق من أن المستخدم لديه صلاحية الإدارة قبل تنفيذ أي شيء.
});