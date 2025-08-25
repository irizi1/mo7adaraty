$(document).ready(function(){
    
    // ===================================
    // كود القوائم المنسدلة لصفحة التسجيل
    // ===================================
    
    // عند تغيير "الشعبة"
    $('#division').on('change', function(){
        var divisionID = $(this).val();
        $('#track').html('<option value="">-- اختر --</option>').prop('disabled', true);
        $('#classes-container').html('');
        $('#groups-container').html('');
        $('#subjects-display-container').html('');
        if(divisionID){
            $.post('get_data.php', { division_id: divisionID }, function(html){
                $('#track').html(html).prop('disabled', false);
            }); 
        }
    });
    
    // عند تغيير "المسار"
    $('#track').on('change', function(){
        var trackID = $(this).val();
        $('#classes-container').html('');
        $('#groups-container').html('');
        $('#subjects-display-container').html('');
        if(trackID){
            $.post('get_data.php', { track_id_for_checkboxes: trackID }, function(html){
                $('#classes-container').html(html);
            }); 
        }
    });

    // عند تغيير اختيار "الفصل" (checkbox)
    $(document).on('change', '.class-checkbox', function() {
        var classID = $(this).val();
        var className = $(this).data('class-name');
        if ($(this).is(':checked')) {
            if ($('.class-checkbox:checked').length > 2) {
                alert('لا يمكنك اختيار أكثر من فصلين.');
                $(this).prop('checked', false);
                return;
            }
            $.post('get_data.php', { class_id_for_groups: classID }, function(groupsHtml){
                var groupDropdown = 
                    '<div class="selection-block" id="group-block-' + classID + '">' +
                        '<h5>الأفواج الخاصة بفصل: <b>' + className + '</b></h5>' +
                        '<select name="group_ids[]" class="group-select" data-class-id="' + classID + '" required>' +
                            groupsHtml +
                        '</select>' +
                    '</div>';
                $('#groups-container').append(groupDropdown);
            });
        } else {
            $('#group-block-' + classID).remove();
            $('.subject-display[data-class-id="' + classID + '"]').remove();
        }
    });

    // عند تغيير اختيار "الفوج" (dropdown)
    $(document).on('change', '.group-select', function() {
        var groupID = $(this).val();
        var classID = $(this).data('class-id');
        var subjectsMainContainer = $('#subjects-display-container');
        subjectsMainContainer.find('.subject-display[data-class-id="' + classID + '"]').remove();
        if (groupID) {
            $.post('get_data.php', { group_id_for_subject: groupID }, function(subjectName){
                if (subjectName) {
                    var subjectTag = '<div class="subject-display" data-class-id="'+ classID + '">' + subjectName + '</div>';
                    subjectsMainContainer.append(subjectTag);
                }
            });
        }
    });

    // ===================================
    // كود الدردشة لصفحة تفاصيل الفصل
    // ===================================
    if ($('#chat-box').length) {
        var classId = $('#chat_class_id').val();
        var groupId = null;
        var lastMessageId = 0;
        var chatInterval;
        var parentMessageId = null; 

        function cancelReply() {
            parentMessageId = null;
            $('#reply-preview-container').hide();
            $('#chat_message').attr('placeholder', 'اكتب رسالتك هنا...');
            $('.reply-indicator').remove();
        }

        $.post('../chat/get_chat_group_id.php', { class_id: classId }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.group_id) {
                    groupId = data.group_id;
                    loadMessages(true);
                    chatInterval = setInterval(function() { loadMessages(false); }, 5000); // تحديث كل 5 ثوانٍ
                } else {
                    console.error('No chat group found:', data.error);
                }
            } catch (e) { console.error('Error parsing group ID response:', e); }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error fetching group ID:', error);
        });

        function loadMessages(isInitialLoad) {
            if (!groupId) return;
            var chatBox = $('#chat-box');
            var atBottom = (chatBox[0].scrollHeight - chatBox.scrollTop() <= chatBox.outerHeight() + 20);

            $.post('../chat/get_chat_messages.php', { group_id: groupId, last_id: lastMessageId }, function(response) {
                try {
                    var messages = JSON.parse(response);
                    if (messages.length > 0) {
                        $('.chat-loading').remove();
                        messages.forEach(function(msg) {
                            if ($('.chat-message[data-message-id="' + msg.message_id + '"]').length === 0) {
                                
                                // === [   التعديل المهم هنا   ] ===
                                // استخدام المسار النسبي الصحيح الذي يعمل من داخل مجلد student
                                var imageSrc = '../uploads/profile_pictures/' + msg.profile_picture;
                                // =================================
                                
                                var messageHtml = '<div class="chat-message" data-message-id="' + msg.message_id + '" data-type="' + msg.type + '">' +
                                    '<img src="' + imageSrc + '" alt="Profile" class="sender-avatar" onerror="this.onerror=null; this.src=\'../uploads/profile_pictures/default_profile.png\';">' +
                                    '<div class="message-content">' +
                                    '<span class="message-sender">' + msg.username + '</span>' +
                                    '<p class="message-text">' + msg.message_text + '</p>' +
                                    '<span class="timestamp">' + new Date(msg.sent_at).toLocaleString() + '</span>' +
                                    '</div></div>';
                                chatBox.append(messageHtml);
                                if (msg.message_id > lastMessageId) {
                                    lastMessageId = msg.message_id;
                                }
                            }
                        });
                        if (isInitialLoad || atBottom) {
                            chatBox.scrollTop(chatBox[0].scrollHeight);
                        }
                    }
                } catch (e) { console.error('Error parsing messages:', e, response); }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error fetching messages:', error);
            });
        }

        $('#chat-form').on('submit', function(e) {
            e.preventDefault();
            var message = $('#chat_message').val().trim();
            if (message !== '') {
                $.post('../chat/send_chat_message.php', { group_id: groupId, message: message, parent_message_id: parentMessageId }, function(response) {
                    $('#chat_message').val('');
                    cancelReply();
                    loadMessages(false);
                });
            }
        });
    }

    // ===================================
    // كود التفاعلات والإبلاغ (class_lectures.php)
    // ===================================
    $(document).on('click', '.reaction-btn', function() {
        var button = $(this);
        var contentId = button.data('content-id');
        var contentType = button.data('content-type');
        var reactionType = button.data('reaction-type');
        $.post('../handle_reaction.php', { content_id: contentId, content_type: contentType, reaction_type: reactionType }, function(response) {
            try {
                var data = JSON.parse(response);
                if(data.success) {
                    button.siblings('.reaction-btn[data-reaction-type=like]').find('span').text('(' + data.likes + ')');
                    button.siblings('.reaction-btn[data-reaction-type=dislike]').find('span').text('(' + data.dislikes + ')');
                } else {
                    alert(data.message);
                }
            } catch (e) { console.error("Error parsing reaction response:", response); }
        });
    });

    var modal = $("#reportModal");
    $(document).on('click', '.report-btn', function() {
        var contentId = $(this).data('content-id');
        var contentType = $(this).data('content-type');
        $('#report_content_id').val(contentId);
        $('#report_content_type').val(contentType);
        modal.show();
    });
    
    $(".close-btn").click(function() {
        modal.hide();
    });

    $(window).click(function(event) {
        if (event.target == modal[0]) {
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
            } catch (e) {
                console.error("خطأ:", response);
                alert("حدث خطأ غير متوقع.");
            }
        });
    });
});