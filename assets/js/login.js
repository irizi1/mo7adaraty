// assets/js/login.js
import supabase from './supabase.js';

$(document).ready(function() {
    const $loginForm = $('#login-form');
    const $statusMessage = $('#status-message');
    const $submitButton = $loginForm.find('button[type="submit"]');

    // وظيفة إظهار الرسائل
    function showMessage(message, isError = false) {
        $statusMessage
            .text(message)
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .show();
    }

    // التعامل مع إرسال النموذج
    $loginForm.on('submit', async function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const password = $('#password').val();

        // تعطيل الزر وتغيير النص
        $submitButton.prop('disabled', true).text('جارٍ تسجيل الدخول...');

        // تسجيل الدخول باستخدام Supabase
        const { data, error } = await supabase.auth.signInWithPassword({
            email: email,
            password: password,
        });

        if (error) {
            // في حالة وجود خطأ
            showMessage('البريد الإلكتروني أو كلمة المرور غير صحيحة.', true);
            $submitButton.prop('disabled', false).text('دخول'); // إعادة تفعيل الزر
        } else if (data.user) {
            // في حالة نجاح تسجيل الدخول
            showMessage('تم تسجيل الدخول بنجاح! جارٍ توجيهك...');
            // توجيه المستخدم إلى الصفحة الرئيسية بعد ثانية واحدة
            setTimeout(() => {
                window.location.href = 'home.html';
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
