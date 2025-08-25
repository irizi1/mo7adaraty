function updateFileName(input) {
    const preview = document.getElementById('image-preview');
    if (input.files.length > 0) {
        preview.textContent = 'تم اختيار الصورة: ' + input.files[0].name;
    } else {
        preview.textContent = '';
    }
}

$(document).ready(function() {
    // ===================================
    // 1. دالة لجلب وعرض التعليقات
    // ===================================
    function loadComments(container) {
        var contentId = container.data('content-id');
        var contentType = container.data('content-type');
        if (contentId && contentType) {
            // المسار صحيح لأن هذا الملف بجانب ملف get_comments.php
            $.get('get_comments.php', { content_id: contentId, content_type: contentType }, function(data) {
                container.html(data);
            });
        }
    }

    // ===================================
    // 2. زر الإعجاب
    // ===================================
    $('.like-btn').on('click', function() {
        var button = $(this);
        var postId = button.data('content-id');
        $.post('handle_reaction.php', { // المسار صحيح لأنه خارج مجلد community
            content_id: postId,
            content_type: 'post',
            reaction_type: 'like'
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    button.find('.likes-count').text(data.likes);
                    button.toggleClass('liked', data.user_reaction === 'like');
                }
            } catch(e) { console.error("Error parsing like response: ", response); }
        });
    });

    // ===================================
    // 3. زر إظهار/إخفاء التعليقات
    // ===================================
    $('.comment-toggle-btn').on('click', function() {
        var commentsSection = $(this).closest('.post-card').find('.comments-section');
        commentsSection.slideToggle(function() {
            if ($(this).is(':visible')) {
                loadComments($(this).find('.comments-container'));
            }
        });
    });

    // ===================================
    // 4. إرسال تعليق جديد
    // ===================================
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            type: "POST",
            url: form.attr('action'), // Action is "add_comment.php"
            data: form.serialize(),
            success: function() {
                form.find('textarea').val('');
                loadComments(form.siblings('.comments-container'));
            },
            error: function() { alert('حدث خطأ أثناء إرسال التعليق.'); }
        });
    });

    // ===================================
    // 5. الضغط على زر "تعديل" تعليق
    // ===================================
    $(document).on('click', '.edit-comment-btn', function(e) {
        e.preventDefault();
        var commentContentDiv = $(this).closest('.comment-content');
        var commentTextP = commentContentDiv.find('.comment-text');
        var originalText = commentTextP.html().replace(/<br\s*[\/]?>/gi, "\n").trim(); 
        var commentId = $(this).data('comment-id');

        commentTextP.html(
            `<form class="edit-comment-form" action="update_comment.php" method="POST">
                <input type="hidden" name="comment_id" value="${commentId}">
                <textarea name="comment_text" rows="2" required>${originalText}</textarea>
                <button type="submit">حفظ</button>
                <button type="button" class="cancel-edit">إلغاء</button>
            </form>`
        );
    });

    // ===================================
    // 6. إرسال نموذج التعديل
    // ===================================
    $(document).on('submit', '.edit-comment-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var commentsContainer = form.closest('.comments-container');
        $.ajax({
            type: "POST",
            url: form.attr('action'), // Action is "update_comment.php"
            data: form.serialize(),
            success: function() {
                loadComments(commentsContainer);
            },
            error: function() { alert('حدث خطأ أثناء تعديل التعليق.'); }
        });
    });

    // ===================================
    // 7. إلغاء التعديل
    // ===================================
    $(document).on('click', '.cancel-edit', function() {
        loadComments($(this).closest('.comments-container'));
    });

    // ===================================
    // 8. زر الإبلاغ
    // ===================================
    var modal = $("#reportModal");
    $('.report-btn').on('click', function() {
        $('#report_content_id').val($(this).data('content-id'));
        modal.show();
    });
    
    $(".close-btn, .modal").on('click', function(e) {
        if ($(e.target).is('.modal, .close-btn')) {
            modal.hide();
        }
    });

    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.post(form.attr('action'), form.serialize(), function(response) {
            try {
                var data = JSON.parse(response);
                alert(data.message); 
                if (data.success) {
                    modal.hide();
                    form[0].reset();
                }
            } catch (ex) { console.error("Error parsing report response: ", response); }
        }).fail(function() { alert("فشل إرسال البلاغ."); });
    });
});