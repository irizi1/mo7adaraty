$(document).ready(function() {
    
    // --- المنطق الديناميكي لنموذج إضافة محاضرة ---

    const $form = $('form[data-context="lectures"]');
    const $divisionSelect = $form.find('#division');
    const $offeringSelect = $form.find('#offering');
    const $subjectSelect = $form.find('#subject');

    // 1. عند تغيير "الشعبة"
    $divisionSelect.on('change', function() {
        const divisionID = $(this).val();
        
        // إعادة تعيين وإلغاء تفعيل القوائم التابعة
        $offeringSelect.html('<option value="">-- اختر الشعبة أولاً --</option>').prop('disabled', true);
        $subjectSelect.html('<option value="">-- اختر المقرر أولاً --</option>').prop('disabled', true);

        if (divisionID) {
            $offeringSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
            
            // طلب AJAX لجلب المقررات المتاحة
            $.post('get_data.php', { action: 'get_offerings', division_id: divisionID }, function(data) {
                $offeringSelect.html('<option value="">-- اختر المقرر --</option>');
                if (data.length > 0) {
                    data.forEach(function(offering) {
                        let trackInfo = offering.track_name ? ` / ${offering.track_name}` : '';
                        let displayText = `${offering.class_name} / ${offering.group_name}${trackInfo}`;
                        $offeringSelect.append(`<option value="${offering.offering_id}">${displayText}</option>`);
                    });
                } else {
                    $offeringSelect.html('<option value="">-- لا توجد مقررات --</option>');
                }
            }, 'json').fail(function() {
                $offeringSelect.html('<option value="">-- حدث خطأ --</option>');
            });
        }
    });

    // 2. عند تغيير "المقرر المتاح"
    $offeringSelect.on('change', function() {
        const offeringID = $(this).val();
        
        $subjectSelect.html('<option value="">-- اختر المقرر أولاً --</option>').prop('disabled', true);

        if (offeringID) {
            $subjectSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
            
            // طلب AJAX لجلب المواد المتاحة للمقرر
            $.post('get_data.php', { action: 'get_subjects', offering_id: offeringID }, function(data) {
                $subjectSelect.html('<option value="">-- اختر المادة --</option>');
                if (data.length > 0) {
                    data.forEach(function(subject) {
                        $subjectSelect.append(`<option value="${subject.offering_subject_id}">${subject.subject_name}</option>`);
                    });
                } else {
                    $subjectSelect.html('<option value="">-- لا توجد مواد --</option>');
                }
            }, 'json').fail(function() {
                $subjectSelect.html('<option value="">-- حدث خطأ --</option>');
            });
        }
    });

});