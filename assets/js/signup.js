// assets/js/signup.js
import supabase from './supabase.js';

$(document).ready(function() {
    // --- عناصر أساسية ---
    const $statusMessage = $('#status-message');
    const $divisionSelect = $('#division');
    const $enrollmentContainer = $('#enrollment-container');
    const $addEnrollmentBtn = $('#add-enrollment-btn');
    const $signupForm = $('#signupForm');
    const $submitButton = $signupForm.find('button[type="submit"]');
    const ADVANCED_CLASSES_IDS = [5, 6];

    // --- دوال مساعدة ---
    function showMessage(message, isError = false) {
        $statusMessage.text(message).removeClass('success error').addClass(isError ? 'error' : 'success').show();
    }

    // --- تحميل البيانات الأولية ---
    async function loadDivisions() {
        const { data, error } = await supabase.from('divisions').select('*').order('division_name');
        if (error) return console.error('Error fetching divisions:', error);
        divisions.forEach(div => {
            $divisionSelect.append(`<option value="${div.division_id}">${div.division_name}</option>`);
        });
    }
    loadDivisions();

    // --- منطق النموذج الديناميكي ---
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
        
        const { data, error } = await supabase.rpc('get_classes_by_division', { p_division_id: divisionID });

        if (error) return console.error('Error fetching classes:', error);
        $classSelect.html('<option value="">-- اختر الفصل --</option>');
        data.forEach(cls => {
            $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
        });
    }

    // --- Event listener for changing class ---
    $enrollmentContainer.on('change', '.class-select', async function() {
        const $block = $(this).closest('.enrollment-block');
        const classID = parseInt($(this).val());
        const $trackGroup = $block.find('.track-group');
        const $trackSelect = $block.find('.track-select');
        const $groupSelect = $block.find('.group-select');

        $trackGroup.hide();
        $trackSelect.html('<option value="">-- اختر المسار --</option>').prop('required', false);
        $groupSelect.html('<option value="">-- اختر --</option>');

        if (ADVANCED_CLASSES_IDS.includes(classID)) {
            $trackGroup.show();
            $trackSelect.prop('required', true);
            const divisionID = $divisionSelect.val();
            const { data, error } = await supabase.rpc('get_tracks_by_division', { p_division_id: divisionID });
            if (error) return console.error('Error fetching tracks:', error);
            data.forEach(track => {
                $trackSelect.append(`<option value="${track.track_id}">${track.track_name}</option>`);
            });
        } else {
            const { data, error } = await supabase.rpc('get_groups_by_class', { p_class_id: classID });
            if (error) return console.error('Error fetching groups:', error);
            data.forEach(group => {
                $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
            });
        }
    });

    // --- Event listener for changing track ---
    $enrollmentContainer.on('change', '.track-select', async function() {
        const $block = $(this).closest('.enrollment-block');
        const classID = $block.find('.class-select').val();
        const trackID = $(this).val();
        const $groupSelect = $block.find('.group-select');

        $groupSelect.html('<option value="">-- اختر --</option>');
        if (trackID) {
            const { data, error } = await supabase.rpc('get_groups_by_class_and_track', { p_class_id: classID, p_track_id: trackID });
            if (error) return console.error('Error fetching groups by track:', error);
            data.forEach(group => {
                $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
            });
        }
    });

    // --- Form Submission Logic ---
    $signupForm.on('submit', async function(e) {
        // ... (نفس كود إرسال النموذج الذي أرسلته سابقاً) ...
        e.preventDefault();
        $submitButton.prop('disabled', true).text('جارٍ إنشاء الحساب...');

        const username = $('#username').val();
        const email = $('#email').val();
        const password = $('#password').val();
        const profilePictureFile = $('#profile_picture')[0].files[0];
        const offeringIds = $('select[name="offering_id[]"]').map((_, el) => $(el).val()).get();

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

        if (profilePictureFile) {
            const filePath = `public/${user.id}/${Date.now()}_${profilePictureFile.name}`;
            const { error: uploadError } = await supabase.storage.from('profile_pictures').upload(filePath, profilePictureFile);
            if (!uploadError) {
                const { data } = supabase.storage.from('profile_pictures').getPublicUrl(filePath);
                profilePictureUrl = data.publicUrl;
            }
        }
        
        const { error: profileError } = await supabase
            .from('profiles')
            .update({ username: username, profile_picture: profilePictureUrl })
            .eq('id', user.id);

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