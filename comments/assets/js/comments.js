$(document).ready(function(){
    
    /**
     * هذا الكود مخصص لنظام التعليقات المستقل
     * ويحتوي على وظائف الإضافة، العرض، التعديل، والحذف
     */

    // ===================================
    // 1. دالة لجلب وعرض التعليقات
    // ===================================
    function loadComments(container) {
        // استخدام data attributes لجلب المعلومات اللازمة
        var contentId = container.data('content-id');
        var contentType = container.data('content-type');
        
        // التأكد من وجود البيانات قبل إرسال الطلب
        if (contentId && contentType) {
            $.get(
                '../comments/get_comments.php', 
                { content_id: contentId, content_type: contentType }, 
                function(data) {
                    container.html(data);
                }
            );
        }
    }

    // ===================================
    // 2. جلب التعليقات عند تحميل الصفحة
    // ===================================
    $('.comments-container').each(function() {
        loadComments($(this));
    });

    // ===================================
    // 3. عند إرسال تعليق جديد
    // ===================================
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault(); // منع إعادة تحميل الصفحة
        var form = $(this);
        var commentsContainer = form.siblings('.comments-container');

        $.ajax({
            type: "POST",
            url: form.attr('action'), // '../comments/add_comment.php'
            data: form.serialize(),
            success: function() {
                form.find('textarea').val(''); // إفراغ مربع النص
                loadComments(commentsContainer); // إعادة تحميل التعليقات في القسم الصحيح
            },
            error: function() {
                alert('حدث خطأ أثناء إرسال التعليق.');
            }
        });
    });

    // ===================================
    // 4. عند الضغط على زر "تعديل"
    // ===================================
    $(document).on('click', '.edit-comment-btn', function(e) {
        e.preventDefault();
        var commentContentDiv = $(this).closest('.comment-content');
        var commentTextP = commentContentDiv.find('.comment-text');
        // استخدام .html() بدلاً من .text() للحفاظ على فواصل الأسطر عند التعديل
        var originalText = commentTextP.html().replace(/<br\s*[\/]?>/gi, "\n").trim(); 
        var commentId = $(this).data('comment-id');

        // استبدال النص بمربع تعديل
        commentTextP.html(
            '<form class="edit-comment-form">' +
                '<input type="hidden" name="comment_id" value="' + commentId + '">' +
                '<textarea name="comment_text" rows="2" required>' + originalText + '</textarea>' +
                '<button type="submit">حفظ</button>' +
                '<button type="button" class="cancel-edit">إلغاء</button>' +
            '</form>'
        );
    });
    
    // ===================================
    // 5. عند الضغط على زر "إلغاء" التعديل
    // ===================================
    $(document).on('click', '.cancel-edit', function() {
        var commentsContainer = $(this).closest('.comments-container');
        loadComments(commentsContainer); // إعادة تحميل التعليقات لإرجاعها لحالتها الأصلية
    });

    // ===================================
    // 6. عند إرسال نموذج التعديل
    // ===================================
    $(document).on('submit', '.edit-comment-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var commentsContainer = form.closest('.comments-container');

        $.ajax({
            type: "POST",
            url: '../comments/update_comment.php',
            data: form.serialize(),
            success: function() {
                loadComments(commentsContainer); // إعادة تحميل كل التعليقات
            },
            error: function() {
                alert('حدث خطأ أثناء تعديل التعليق.');
            }
        });
    });
});