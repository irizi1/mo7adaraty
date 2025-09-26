// assets/js/login.js
import supabase from './supabase.js';

$(document).ready(function() {
    const $loginForm = $('#login-form');
    const $statusMessage = $('#status-message');
    const $submitButton = $loginForm.find('button[type="submit"]');

    function showMessage(message, isError = false) {
        $statusMessage
            .text(message)
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .show();
    }

    $loginForm.on('submit', async function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const password = $('#password').val();

        $submitButton.prop('disabled', true).text('جارٍ تسجيل الدخول...');

        // الخطوة 1: تسجيل الدخول
        const { data: loginData, error: loginError } = await supabase.auth.signInWithPassword({
            email: email,
            password: password,
        });

        if (loginError) {
            showMessage('البريد الإلكتروني أو كلمة المرور غير صحيحة.', true);
            $submitButton.prop('disabled', false).text('دخول');
            return;
        }

        if (loginData.user) {
            // الخطوة 2: إذا نجح الدخول، جلب "الدور" من جدول profiles
            const { data: profile, error: profileError } = await supabase
                .from('profiles')
                .select('role')
                .eq('id', loginData.user.id)
                .single(); // .single() لجلب سجل واحد فقط

            if (profileError) {
                showMessage('لم نتمكن من تحديد دورك. يرجى مراجعة الدعم.', true);
                $submitButton.prop('disabled', false).text('دخول');
                return;
            }

            showMessage('تم تسجيل الدخول بنجاح! جارٍ توجيهك...');

            // الخطوة 3: التوجيه بناءً على الدور
            setTimeout(() => {
                if (profile.role === 'admin') {
                    // إذا كان أدمن، اذهب إلى لوحة التحكم
                    window.location.href = 'admin/index.html';
                } else {
                    // إذا كان طالباً، اذهب إلى الملف الشخصي
                    window.location.href = 'student/profil.html';
                }
            }, 1000);
        }
    });

    // وظيفة إظهار/إخفاء كلمة المرور
    $('#toggle-password').on('click', function() {
        const $passwordInput = $('#password');
        const type = $passwordInput.attr('type') === 'password' ? 'text' : 'password';
        $passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
});
