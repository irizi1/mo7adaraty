// mo7adaraty/admin/lectures/js/lectures_page.js

document.addEventListener('DOMContentLoaded', () => {
    // التحقق من صلاحية الأدمن (يفترض وجود دالة checkAdminAuth في supabase-client.js)
    if (typeof checkAdminAuth === 'function') {
        checkAdminAuth().then(isAdmin => {
            if (isAdmin) {
                initializePage();
            } else {
                // إذا لم يكن مسؤولاً، سيتم التوجيه من داخل checkAdminAuth
            }
        });
    } else {
        console.warn("checkAdminAuth function not found. Proceeding without explicit admin check.");
        initializePage();
    }
});

function initializePage() {
    const divisionSelect = document.getElementById('division');
    const offeringSelect = document.getElementById('offering');
    const subjectSelect = document.getElementById('subject');
    const form = document.getElementById('add-lecture-form');
    const formMessage = document.getElementById('form-message');

    // ------------------------------------------------------------------
    // 1. منطق ملء القوائم المنسدلة (النموذج)
    // ------------------------------------------------------------------

    async function fetchDivisions() {
        const { data, error } = await supabase.from('divisions').select('division_id, division_name').order('division_name');
        if (error) {
            console.error('Error fetching divisions:', error);
            return;
        }
        data.forEach(div => {
            divisionSelect.innerHTML += `<option value="${div.division_id}">${div.division_name}</option>`;
        });
    }

    async function fetchOfferings(divisionId) {
        offeringSelect.innerHTML = '<option value="">-- اختر --</option>';
        offeringSelect.disabled = true;
        if (!divisionId) return;

        // جلب المقررات المتاحة بناءً على الشعبة
        const { data, error } = await supabase
            .from('course_offerings')
            .select('offering_id, class_id, group_id, track_id, classes(class_name), groups(group_name), tracks(track_name)')
            .eq('division_id', divisionId);

        if (error) {
            console.error('Error fetching offerings:', error);
            return;
        }
        
        data.forEach(offering => {
            const classInfo = offering.classes?.class_name || '';
            const groupInfo = offering.groups?.group_name || '';
            const trackInfo = offering.tracks?.track_name ? ` / ${offering.tracks.track_name}` : '';
            const offeringName = `${classInfo} - ${groupInfo}${trackInfo}`;
            offeringSelect.innerHTML += `<option value="${offering.offering_id}">${offeringName}</option>`;
        });
        offeringSelect.disabled = false;
    }

    async function fetchSubjects(offeringId) {
        subjectSelect.innerHTML = '<option value="">-- اختر --</option>';
        subjectSelect.disabled = true;
        if (!offeringId) return;

        // جلب المواد (offering_subjects) بناءً على المقرر المتاح
        const { data, error } = await supabase
            .from('offering_subjects')
            .select('offering_subject_id, subjects(subject_name), professors(professor_name)')
            .eq('offering_id', offeringId);

        if (error) {
            console.error('Error fetching subjects:', error);
            return;
        }

        data.forEach(sub => {
            const subjectName = sub.subjects?.subject_name || 'مادة غير معروفة';
            const profName = sub.professors?.professor_name ? ` (${sub.professors.professor_name})` : '';
            subjectSelect.innerHTML += `<option value="${sub.offering_subject_id}">${subjectName}${profName}</option>`;
        });
        subjectSelect.disabled = false;
    }

    // مستمعي الأحداث لتحديث القوائم المنسدلة
    divisionSelect.addEventListener('change', () => {
        fetchOfferings(divisionSelect.value);
        subjectSelect.innerHTML = '<option value="">-- اختر مقرر أولاً --</option>'; // تفريغ المادة عند تغيير الشعبة
        subjectSelect.disabled = true;
    });

    offeringSelect.addEventListener('change', () => {
        fetchSubjects(offeringSelect.value);
    });
    
    // ------------------------------------------------------------------
    // 2. منطق جلب وعرض جدول المحاضرات (تجميع البيانات)
    // ------------------------------------------------------------------
    
    async function fetchAndRenderLectures() {
        const container = document.getElementById('lectures-list-container');
        const loading = document.getElementById('loading-message');
        const noData = document.getElementById('no-lectures-message');

        loading.style.display = 'block';
        container.innerHTML = '';
        noData.style.display = 'none';

        try {
            // الاستعلام المعقد لجلب جميع البيانات المطلوبة لإنشاء الجدول
            const { data: lectures, error } = await supabase
                .from('lectures')
                .select(`
                    lecture_id, title, upload_date,
                    uploader_user_id,
                    uploader:users!lectures_uploader_user_id_fkey(username),
                    offering_subject_id,
                    offering_subjects (
                        subjects (subject_name),
                        professors (professor_name),
                        offerings:course_offerings (
                            division:divisions (division_name),
                            class:classes (class_name),
                            group:groups (group_name),
                            track:tracks (track_name)
                        )
                    )
                `)
                .eq('status', 'approved') // فقط المحاضرات الموافق عليها
                .order('upload_date', { ascending: false });

            if (error) throw error;
            
            if (!lectures || lectures.length === 0) {
                loading.style.display = 'none';
                noData.style.display = 'block';
                return;
            }

            // تجميع البيانات حسب الشعبة ثم الفصل (Division ثم Class)
            const structuredLectures = lectures.reduce((acc, lecture) => {
                const offering = lecture.offering_subjects.offerings;
                const divisionName = offering.division.division_name;
                const className = offering.class.class_name;
                
                if (!acc[divisionName]) {
                    acc[divisionName] = {};
                }
                if (!acc[divisionName][className]) {
                    acc[divisionName][className] = [];
                }
                acc[divisionName][className].push(lecture);
                return acc;
            }, {});

            renderLecturesTable(structuredLectures);

        } catch (e) {
            console.error('Error fetching and rendering lectures:', e);
            container.innerHTML = `<p style="color: red;">فشل في تحميل المحاضرات: ${e.message}</p>`;
        } finally {
            loading.style.display = 'none';
        }
    }

    function renderLecturesTable(structuredLectures) {
        const container = document.getElementById('lectures-list-container');
        let html = '';

        for (const [divisionName, classes] of Object.entries(structuredLectures)) {
            // عنوان الشعبة
            html += `<h4 class="division-title">${divisionName}</h4>`;
            
            for (const [className, lectures] of Object.entries(classes)) {
                // عنوان الفصل
                html += `<h5>الفصل: ${className}</h5>`;
                
                // بدء الجدول
                html += `
                    <table>
                        <thead> 
                            <tr> 
                                <th>عنوان المحاضرة</th>
                                <th>المادة</th> 
                                <th>الأستاذ</th>
                                <th>المقرر (الفوج/المسار)</th>
                                <th>الناشر</th> 
                                <th>الإجراءات</th> 
                            </tr> 
                        </thead>
                        <tbody>
                `;

                // صفوف المحاضرات
                lectures.forEach(lecture => {
                    const subjectName = lecture.offering_subjects.subjects.subject_name;
                    const professorName = lecture.offering_subjects.professors?.professor_name || 'غير معين';
                    const groupName = lecture.offering_subjects.offerings.group.group_name;
                    const trackName = lecture.offering_subjects.offerings.track?.track_name;
                    const courseInfo = groupName + (trackName ? ` / ${trackName}` : '');
                    const uploaderName = lecture.uploader.username;

                    html += `
                        <tr>
                            <td>${lecture.title}</td>
                            <td>${subjectName}</td>
                            <td>${professorName}</td>
                            <td>${courseInfo}</td>
                            <td>${uploaderName}</td>
                            <td>
                                <button class="action-btn btn-delete" data-id="${lecture.lecture_id}">حذف</button>
                            </td>
                        </tr>
                    `;
                });
                
                // نهاية الجدول
                html += `
                        </tbody>
                    </table>
                `;
            }
        }
        
        container.innerHTML = html;
        
        // إضافة مستمعي أحداث للحذف بعد إدخال الـ HTML
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', (e) => handleDeleteLecture(e.target.dataset.id));
        });
    }
    
    // ------------------------------------------------------------------
    // 3. منطق إضافة محاضرة جديدة
    // ------------------------------------------------------------------
    
    form.addEventListener('submit', handleFormSubmission);

    async function handleFormSubmission(e) {
        e.preventDefault();
        formMessage.textContent = 'جاري إضافة المحاضرة...';
        formMessage.style.color = '#007bff';
        
        const formData = new FormData(form);
        const file = formData.get('lecture_file');
        const title = formData.get('title');
        const description = formData.get('description');
        const offeringSubjectId = formData.get('offering_subject_id');

        if (!file || !offeringSubjectId) {
            formMessage.textContent = 'الرجاء ملء جميع الحقول المطلوبة.';
            formMessage.style.color = 'red';
            return;
        }

        try {
            // 1. تحديد مسار واسم الملف في Supabase Storage
            const fileExtension = file.name.split('.').pop();
            const fileName = `${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExtension}`;
            const filePath = `lectures/${fileName}`;
            
            // 2. رفع الملف إلى Supabase Storage
            const { data: uploadData, error: uploadError } = await supabase.storage
                .from('lectures_bucket') // افترض أن اسم الباكت هو lectures_bucket
                .upload(filePath, file);

            if (uploadError) throw uploadError;

            // 3. جلب معرف المستخدم الحالي (الأدمن)
            const { data: sessionData } = await supabase.auth.getSession();
            const uploaderUserId = sessionData.session.user.id;
            
            // 4. إدراج بيانات المحاضرة في قاعدة البيانات
            const { error: insertError } = await supabase
                .from('lectures')
                .insert([{
                    title: title,
                    description: description,
                    file_path: filePath,
                    uploader_user_id: uploaderUserId,
                    offering_subject_id: offeringSubjectId,
                    status: 'approved', // يتم الموافقة عليها تلقائياً من قبل الأدمن
                    upload_date: new Date().toISOString()
                }]);

            if (insertError) throw insertError;
            
            formMessage.textContent = 'تم إضافة المحاضرة بنجاح!';
            formMessage.style.color = 'green';
            form.reset();
            fetchAndRenderLectures(); // تحديث قائمة المحاضرات

        } catch (e) {
            console.error('Submission error:', e);
            formMessage.textContent = `فشل في إضافة المحاضرة: ${e.message || 'خطأ غير معروف'}`;
            formMessage.style.color = 'red';
        }
    }

    // ------------------------------------------------------------------
    // 4. منطق حذف المحاضرة
    // ------------------------------------------------------------------
    
    async function handleDeleteLecture(lectureId) {
        if (!confirm('هل أنت متأكد من حذف هذه المحاضرة؟ سيتم حذف الملف من التخزين أيضاً.')) {
            return;
        }
        
        try {
            // 1. جلب مسار الملف أولاً لحذفه من التخزين
            const { data: lectureData, error: fetchError } = await supabase
                .from('lectures')
                .select('file_path')
                .eq('lecture_id', lectureId)
                .single();
            
            if (fetchError || !lectureData) throw new Error("Could not find file path or lecture data.");

            // 2. حذف السجل من قاعدة البيانات (يجب أن يتم أولاً في بعض الحالات لتجنب مشاكل القيود)
            const { error: deleteDBError } = await supabase
                .from('lectures')
                .delete()
                .eq('lecture_id', lectureId);
                
            if (deleteDBError) throw deleteDBError;

            // 3. حذف الملف من Supabase Storage
            if (lectureData.file_path) {
                const { error: deleteStorageError } = await supabase.storage
                    .from('lectures_bucket')
                    .remove([lectureData.file_path]);

                if (deleteStorageError) {
                    // تحذير: إذا فشل حذف الملف، يجب أن نسجل ذلك ولكن لا نوقف العملية إذا نجح حذف السجل
                    console.error('Failed to delete file from storage:', deleteStorageError);
                }
            }
            
            alert('تم حذف المحاضرة بنجاح.');
            fetchAndRenderLectures(); // إعادة تحميل القائمة

        } catch (e) {
            console.error('Deletion error:', e);
            alert(`فشل في عملية الحذف: ${e.message}`);
        }
    }


    // بدء تهيئة الصفحة
    fetchDivisions();
    fetchAndRenderLectures();
}