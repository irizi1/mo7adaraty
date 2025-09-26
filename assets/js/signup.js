// assets/js/signup.js
import supabase from './supabase.js';

$(document).ready(function() {
    // --- متغيرات وعناصر أساسية ---
    const $statusMessage = $('#status-message');
    const $divisionSelect = $('#division');
    const $enrollmentContainer = $('#enrollment-container');
    const $addEnrollmentBtn = $('#add-enrollment-btn');
    const $signupForm = $('#signupForm');
    const $submitButton = $signupForm.find('button[type="submit"]');

    let enrollmentCounter = 0;
    const ADVANCED_CLASSES_IDS = [5, 6]; // احتفظ بهذه المعرفات كما هي

    // --- دوال مساعدة ---
    function showMessage(message, isError = false) {
        $statusMessage.text(message).removeClass('success error').addClass(isError ? 'error' : 'success').show();
    }

    // --- تحميل البيانات الأولية ---
    async function loadDivisions() {
        const { data: divisions, error } = await supabase.from('divisions').select('*');
        if (error) {
            console.error('Error fetching divisions:', error);
            return;
        }
        divisions.forEach(div => {
            $divisionSelect.append(`<option value="${div.division_id}">${div.division_name}</option>`);
        });
    }
    loadDivisions();

    // --- منطق النموذج الديناميكي ---
    
    // عند تغيير الشعبة
    $divisionSelect.on('change', function() {
        $enrollmentContainer.empty();
        enrollmentCounter = 0;
        if ($(this).val()) {
            $addEnrollmentBtn.show();
            addEnrollmentBlock();
        } else {
            $addEnrollmentBtn.hide();
        }
    });

    // زر إضافة مقرر آخر
    $addEnrollmentBtn.on('click', addEnrollmentBlock);

    function addEnrollmentBlock() {
        if (enrollmentCounter >= 2) return;
        enrollmentCounter++;
        const blockHtml = `
            <div class="enrollment-block" id="enrollment-block-${enrollmentCounter}">
                <h4>المقرر ${enrollmentCounter}</h4>
                <div class="form-group">
                    <label>الفصل:</label>
                    <select class="class-select" required><option value="">-- اختر الفصل --</option></select>
                </div>
                <div class="form-group track-group">
                    <label>المسار:</label>
                    <select class="track-select"><option value="">-- اختر المسار --</option></select>
                </div>
                <div class="form-group">
                    <label>الفوج:</label>
                    <select name="offering_id[]" class="group-select" required><option value="">-- اختر --</option></select>
                </div>
            </div>`;
        $enrollmentContainer.append(blockHtml);
        populateClasses(enrollmentCounter);
        $addEnrollmentBtn.toggle(enrollmentCounter < 2);
    }

    async function populateClasses(counter) {
        const divisionID = $divisionSelect.val();
        const $classSelect = $(`#enrollment-block-${counter} .class-select`);
        
        // ملاحظة: هذا الاستعلام يفترض أنك أنشأت علاقة بين الجداول في Supabase
        const { data: classes, error } = await supabase.rpc('get_classes_by_division', { p_division_id: divisionID });

        if (error) console.error('Error fetching classes:', error);
        if (classes) {
            classes.forEach(cls => {
                $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
            });
        }
    }

    // عند تغيير الفصل أو المسار (يجب إكمال هذا المنطق بنفس طريقة PHP)
    $enrollmentContainer.on('change', '.class-select, .track-select', async function() {
        // ... سيتم هنا جلب الأفواج بناءً على الفصل والمسار المختار ...
    });


    // --- التعامل مع إرسال النموذج ---
    $signupForm.on('submit', async function(e) {
        e.preventDefault();
        $submitButton.prop('disabled', true).text('جارٍ إنشاء الحساب...');

        const username = $('#username').val();
        const email = $('#email').val();
        const password = $('#password').val();
        const profilePictureFile = $('#profile_picture')[0].files[0];
        const offeringIds = $('select[name="offering_id[]"]').map((_, el) => $(el).val()).get();

        // 1. إنشاء حساب المستخدم
        const { data: authData, error: authError } = await supabase.auth.signUp({
            email: email,
            password: password,
            options: { data: { username: username } }
        });

        if (authError) {
            showMessage('خطأ: ' + authError.message, true);
            $submitButton.prop('disabled', false).text('إنشاء الحساب');
            return;
        }

        const user = authData.user;
        let profilePictureUrl = null;

        // 2. رفع الصورة الشخصية (إذا وجدت)
        if (profilePictureFile) {
            const filePath = `public/${user.id}/${profilePictureFile.name}`;
            const { error: uploadError } = await supabase.storage.from('profile_pictures').upload(filePath, profilePictureFile);
            if (!uploadError) {
                const { data } = supabase.storage.from('profile_pictures').getPublicUrl(filePath);
                profilePictureUrl = data.publicUrl;
            }
        }
        
        // 3. تحديث بيانات المستخدم بالصورة واسم المستخدم (في جدول profiles)
        const { error: profileError } = await supabase
            .from('profiles') // أنت بحاجة لإنشاء هذا الجدول
            .update({
                username: username,
                profile_picture_url: profilePictureUrl
            })
            .eq('id', user.id);

        // 4. إضافة تسجيلات الطالب
        const enrollments = offeringIds.map(id => ({ user_id: user.id, offering_id: id }));
        const { error: enrollmentError } = await supabase.from('student_enrollments').insert(enrollments);

        if (profileError || enrollmentError) {
             showMessage('حدث خطأ أثناء حفظ بياناتك، ولكن تم إنشاء حسابك.', true);
        } else {
             showMessage('تم إنشاء حسابك بنجاح! سيتم توجيهك لصفحة الدخول.');
             setTimeout(() => { window.location.href = 'login.html'; }, 2000);
        }
    });
});
