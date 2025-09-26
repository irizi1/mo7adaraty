$(document).ready(function() {
    
    const ADVANCED_CLASSES_IDS = [5, 6];

    // --- المنطق الديناميكي للنماذج في لوحة التحكم ---

    // 1. عند تغيير "الشعبة"
    $('#division').on('change', function() {
        var divisionID = $(this).val();
        
        // إعادة تعيين الحقول
        $('#class').val('').prop('disabled', true);
        $('#track-group').hide();
        $('#track').val('').prop('disabled', true);
        $('#group').val('').prop('disabled', true);
        $('#offering').html('<option value="">-- اختر --</option>').prop('disabled', true);
        $('#subject').html('<option value="">-- اختر --</option>').prop('disabled', true);
        
        if (divisionID) {
             $('#class').prop('disabled', false); // تفعيل قائمة الفصول الثابتة
             if ($('#offering').length) {
                 $.post('../../get_admin_data.php', { division_id_for_offerings: divisionID }, function(data) {
                    $('#offering').html(data).prop('disabled', false);
                });
             }
        }
    });

    // 2. عند تغيير "الفصل"
    $('#class').on('change', function() {
        var classID = parseInt($(this).val());
        var divisionID = $('#division').val();
        
        $('#track-group').hide();
        $('#track').val('').prop('disabled', true);
        $('#group').prop('disabled', false); // تفعيل قائمة الأفواج

        if (classID && divisionID && ADVANCED_CLASSES_IDS.includes(classID)) {
            $('#track-group').show();
            $.post('../../get_admin_data.php', { division_id_for_tracks: divisionID }, function(data) {
                $('#track').html(data).prop('disabled', false);
            });
        }
    });

    // 3. عند تغيير "المقرر المتاح"
    $('#offering').on('change', function(){
        var offeringID = $(this).val();
        var $subjectSelect = $('#subject');
        var context = $subjectSelect.closest('form').data('context') || 'default';

        $subjectSelect.html('<option value="">-- اختر --</option>').prop('disabled', true);

        if (offeringID) {
            $.post('../../get_admin_data.php', { offering_id_for_subjects: offeringID, context: context }, function(subjects) {
                $subjectSelect.empty().append('<option value="">-- اختر المادة --</option>');
                if(subjects.length > 0){
                    subjects.forEach(function(subject){
                        $subjectSelect.append(`<option value="${subject.offering_subject_id}">${subject.subject_name}</option>`);
                    });
                    $subjectSelect.prop('disabled', false);
                } else {
                    let message = (context === 'professors') ? '-- كل المواد لديها أستاذ --' : '-- لا توجد مواد --';
                    $subjectSelect.append(`<option value="">${message}</option>`);
                }
            }, 'json');
        }
    });


    // 4. كود القائمة الجانبية القابلة للطي
    $('#sidebar-toggle').on('click', function() {
        $('.admin-sidebar').toggleClass('show');
    });

});