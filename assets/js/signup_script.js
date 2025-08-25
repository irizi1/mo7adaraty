$(document).ready(function(){
    
    // تعريف العناصر الرئيسية
    const $division = $('#division');
    const $classesContainer = $('#classes-container');
    const $trackGroup = $('#track-group');
    const $trackSelect = $('#track');
    const $groupsContainer = $('#groups-container');
    const $subjectsContainer = $('#subjects-display-container');

    // دالة لإعادة تعيين الحقول
    function resetFields(level) {
        if (level <= 1) {
            $classesContainer.html('');
        }
        if (level <= 2) {
            $trackGroup.hide();
            $trackSelect.html('');
        }
        if (level <= 3) {
            $groupsContainer.html('');
            $subjectsContainer.html('');
        }
    }

    // 1. عند تغيير "الشعبة"
    $division.on('change', function(){
        const divisionID = $(this).val();
        resetFields(1); // إعادة تعيين كل الحقول التابعة

        if (divisionID) {
            // جلب الفصول التابعة للشعبة
            $.post('get_data.php', { division_id_for_classes: divisionID }, function(html){
                $classesContainer.html(html);
            }); 
        }
    });

    // 2. عند تغيير اختيار "الفصل" (checkbox)
    $classesContainer.on('change', '.class-checkbox', function() {
        // إعادة تعيين حقول المسار والأفواج والمواد
        resetFields(2);

        // التحقق من عدد الفصول المختارة
        if ($('.class-checkbox:checked').length > 2) {
            alert('لا يمكنك اختيار أكثر من فصلين.');
            $(this).prop('checked', false);
            return;
        }

        let hasAdvancedClass = false;
        // بناء واجهة اختيار الأفواج لكل فصل
        $('.class-checkbox:checked').each(function() {
            const classID = $(this).val();
            const className = $(this).data('class-name');
            const isAdvanced = $(this).data('advanced');

            if (isAdvanced) {
                hasAdvancedClass = true;
            }
            
            // إضافة حاوية لكل فوج
            const groupBlock = `
                <div class="selection-block" id="group-block-${classID}">
                    <h5>الأفواج الخاصة بفصل: <b>${className}</b></h5>
                    <select name="group_ids[]" class="group-select" data-class-id="${classID}" required>
                        </select>
                </div>`;
            $groupsContainer.append(groupBlock);
        });

        // إذا كان هناك فصل متقدم، أظهر حقل المسار
        if (hasAdvancedClass) {
            $trackGroup.show();
            // جلب المسارات للفصل المتقدم الأول (يمكن تحسين هذا الجزء إذا كان هناك أكثر من فصل متقدم)
            const firstAdvancedClass = $('.class-checkbox:checked[data-advanced="true"]').first();
            if (firstAdvancedClass.length) {
                $.post('get_data.php', { class_id_for_tracks: firstAdvancedClass.val() }, function(html){
                    $trackSelect.html(html);
                    // بعد تحميل المسارات، قم بتحميل الأفواج
                    loadGroupsForSelectedClasses();
                });
            }
        } else {
            // إذا لم يكن هناك فصل متقدم، قم بتحميل الأفواج مباشرة
            loadGroupsForSelectedClasses();
        }
    });

    // 3. عند تغيير "المسار"
    $trackSelect.on('change', function() {
        // عند تغيير المسار، أعد تحميل الأفواج
        loadGroupsForSelectedClasses();
    });

    // 4. دالة لتحميل الأفواج بناءً على الفصول (والمسار إذا كان موجودًا)
    function loadGroupsForSelectedClasses() {
        $('.class-checkbox:checked').each(function() {
            const classID = $(this).val();
            const isAdvanced = $(this).data('advanced');
            const $groupSelect = $(`#group-block-${classID} select`);
            
            let postData = { class_id_for_groups: classID };
            
            // إذا كان الفصل متقدمًا، أرسل المسار المختار
            if (isAdvanced) {
                postData.track_id = $trackSelect.val();
            }

            $.post('get_data.php', postData, function(html) {
                $groupSelect.html(html);
                $groupSelect.trigger('change'); // تحديث المواد عند تحميل الأفواج
            });
        });
    }

    // 5. عند تغيير اختيار "الفوج"
    $groupsContainer.on('change', '.group-select', function() {
        updateSubjectsDisplay();
    });

    // 6. دالة لتحديث عرض المواد
    function updateSubjectsDisplay() {
        $subjectsContainer.html('<i>جارٍ تحديث المواد...</i>');
        const selectedGroups = $('.group-select').map(function() {
            return $(this).val();
        }).get().filter(val => val); // فلترة القيم الفارغة

        if (selectedGroups.length > 0) {
            let subjectsText = [];
            let completedRequests = 0;
            
            selectedGroups.forEach(groupID => {
                $.post('get_data.php', { group_id_for_subject: groupID }, function(subjectName) {
                    if (subjectName.trim()) {
                        subjectsText.push(subjectName.trim());
                    }
                    completedRequests++;
                    if (completedRequests === selectedGroups.length) {
                        $subjectsContainer.html(subjectsText.join('<br>'));
                    }
                });
            });
        } else {
            $subjectsContainer.html('');
        }
    }
});