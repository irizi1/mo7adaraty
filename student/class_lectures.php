<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال معرّف المقرر (offering_id)
$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: profil.php?error=رابط المقرر غير صحيح");
    exit();
}

try {
    // 3. التحقق من أن الطالب مسجل في هذا المقرر المحدد
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);
    if ($stmt_check->fetchColumn() == 0 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        header("Location: profil.php?error=ليس لديك صلاحية الوصول لهذا المقرر");
        exit();
    }
    
    // 4. جلب معلومات المقرر كاملة للعرض
    $stmt_offering_info = $pdo->prepare("
        SELECT c.class_name 
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        WHERE co.offering_id = ?
    ");
    $stmt_offering_info->execute([$offering_id]);
    $class_info = $stmt_offering_info->fetch();
    if (!$class_info) {
        die("خطأ: لم يتم العثور على المقرر.");
    }

    // 5. استعلام لجلب المواد والمحاضرات
    $stmt_lectures = $pdo->prepare("
        SELECT 
            s.subject_name, p.professor_name,
            l.lecture_id, l.title, l.description, l.file_path, l.file_type, l.upload_date,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = l.lecture_id AND content_type = 'lecture' AND reaction_type = 'like') as likes_count,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = l.lecture_id AND content_type = 'lecture' AND reaction_type = 'dislike') as dislikes_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id = l.lecture_id AND content_type = 'lecture' AND user_id = :user_id) as user_reaction
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        LEFT JOIN lectures l ON os.offering_subject_id = l.offering_subject_id AND l.status = 'approved'
        LEFT JOIN users u ON l.uploader_user_id = u.user_id
        WHERE os.offering_id = :offering_id
        ORDER BY s.subject_name, l.upload_date DESC
    ");
    $stmt_lectures->execute(['user_id' => $user_id, 'offering_id' => $offering_id]);
    $results = $stmt_lectures->fetchAll(PDO::FETCH_ASSOC);

    // إعادة هيكلة البيانات للعرض
    $subjects_with_lectures = [];
    foreach ($results as $row) {
        $subject_key = $row['subject_name'] . ' - ' . ($row['professor_name'] ?? 'غير محدد');
        if (!isset($subjects_with_lectures[$subject_key])) {
            $subjects_with_lectures[$subject_key] = [];
        }
        if ($row['lecture_id']) {
            $subjects_with_lectures[$subject_key][] = $row;
        }
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
  <title>محاضرات <?php echo htmlspecialchars($class_info['class_name']); ?> - Mo7adaraty</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --brand-color: #007bff; --bg-color: #f4f8fc; --card-bg: #ffffff;
      --border-color: #e9ecef; --text-secondary: #6c757d; --green-color: #28a745;
      --red-color: #dc3545; --brand-darker: #0056b3; --warning-color: #ffc107;
    }
    body { font-family: 'Tajawal', sans-serif; background: var(--bg-color); margin:0; }
    .container { max-width: 1200px; margin:auto; padding:20px; }
    .page-header { background: var(--card-bg); border-radius:12px; padding:20px; margin-bottom:25px; box-shadow:0 4px 15px rgba(0,0,0,0.07); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .page-header-title { display: flex; align-items: center; gap: 10px; }
    .page-header h1 { margin:0; font-size:22px; color:var(--brand-color); }
    .page-header a { color: var(--brand-color); text-decoration:none; font-weight: 500;}
    .upload-btn { background-color: var(--green-color); color: white !important; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .class-nav { display:flex; justify-content:center; gap:20px; background:var(--card-bg); border-radius:12px; padding:10px; margin-bottom:25px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .class-nav a { color:var(--text-secondary); text-decoration:none; padding:10px 15px; border-radius:8px; font-weight:600; }
    .class-nav a.active { background:var(--brand-color); color:#fff; }
    .content-layout { display: flex; align-items: flex-start; gap: 20px; }
    .subjects-sidebar { flex: 0 0 280px; background: var(--card-bg); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 10px; transition: all 0.3s ease; }
    .subject-link { display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-radius: 8px; text-decoration: none; color: #333; font-weight: 600; margin-bottom: 5px; border-right: 4px solid transparent; transition: all 0.2s ease; }
    .subject-link:hover { background-color: #f8f9fa; color: var(--brand-color); }
    .subject-link.active { background-color: #e7f1ff; color: var(--brand-color); border-right-color: var(--brand-color); }
    .lectures-main-content { flex: 1; min-width: 0; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .lectures-grid { display:grid; gap:20px; }
    .lecture-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); display: flex; flex-direction: column; }
    .card-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
    .file-icon { font-size: 28px; color: var(--brand-color); }
    .header-info h3 { margin: 0; font-size: 18px; }
    .header-info p { margin: 4px 0 0; font-size: 14px; color: var(--text-secondary); }
    .card-body { padding: 20px; flex-grow: 1; }
    .card-body p { margin: 0; line-height:1.7; }
    .card-footer { padding: 15px 20px; background-color: #f8f9fa; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .actions-left a, .actions-left button { text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 14px; font-weight: 600; color: white; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; }
    .view-btn { background-color: #6c757d; }
    .download-btn { background-color: var(--brand-color); margin-right: 5px; }
    .report-btn { background-color: var(--warning-color); color: #212529; }
    .actions-right { display: flex; gap: 10px; }
    .reaction-btn { background: #e9ecef; color: var(--text-secondary); padding: 8px 12px; border: 1px solid transparent; border-radius: 6px; cursor: pointer; transition: all 0.2s ease;}
    .reaction-btn.active.like { background-color: var(--brand-color); color: white; border-color: var(--brand-darker); }
    .reaction-btn.active.dislike { background-color: var(--red-color); color: white; border-color: #b02a37; }
    .comments-section { padding: 20px; border-top: 1px solid var(--border-color); }
    .comment { display: flex; align-items: flex-start; margin-bottom: 15px; }
    .comment-author-info { flex-shrink: 0; margin-left: 12px; }
    .comment-author-info img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
    .comment-form { display: flex; gap: 10px; }
    .comment-form textarea { flex-grow: 1; border: 1px solid #ccc; border-radius: 5px; padding: 10px; }
    .comment-form button { border: none; background-color: var(--green-color); color: white; padding: 0 15px; border-radius: 5px; cursor: pointer; }
    
    /* === [   CSS للنافذة المنبثقة (Modal)   ] === */
    .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px 30px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px; }
    .modal-header h2 { margin: 0; font-size: 20px; }
    .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-body .form-group label { font-weight: bold; }
    .modal-body .form-group textarea { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    
    /* تصميم النافذة المنبثقة للتحميل */
    .download-modal-content {
      max-width: 400px;
      text-align: center;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    .countdown-circle {
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 20px auto;
    }
    .progress-ring-circle {
      transform: rotate(-90deg);
      transform-origin: 50% 50%;
      stroke-linecap: round;
      transition: stroke-dashoffset 8s linear;
    }
    .countdown-number {
      position: absolute;
      font-size: 28px;
      font-weight: bold;
      color: var(--brand-color);
    }
    .modal-body p {
      font-size: 16px;
      color: var(--text-secondary);
    }
    .modal-body p span {
      font-weight: bold;
      color: var(--brand-color);
    }

    @media (max-width: 992px) {
        .content-layout { flex-direction: column; }
        .subjects-sidebar { width: 100%; flex: 0 0 auto; max-height: 0; padding: 0 10px; overflow: hidden; border: none; box-shadow: none; background: none; transition: max-height 0.3s ease, padding 0.3s ease; }
        .subjects-sidebar.open { max-height: 100vh; padding: 10px 0; }
        .sidebar-toggle { display: inline-flex; align-items: center; gap: 5px; background: var(--brand-color); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background-color 0.3s ease, transform 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-weight: 600; }
        .sidebar-toggle:hover { background: var(--brand-darker); transform: translateY(-2px); }
        .sidebar-toggle.open { background: #dc3545; }
        .sidebar-toggle.open i { transform: rotate(180deg); }
        .page-header-title { width: 100%; justify-content: space-between; }
    }
  </style>
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="container">
  <header class="page-header">
    <div class="page-header-title">
        <h1><i class="fa-solid fa-school"></i> محاضرات <?php echo htmlspecialchars($class_info['class_name']); ?></h1>
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-list-ul" style="transition: transform 0.3s ease;"></i> عرض المواد</button>
    </div>
    <a href="profil.php">&larr; العودة إلى ملفي الشخصي</a>
    <a href="upload_lecture.php?id=<?php echo $offering_id; ?>" class="upload-btn"><i class="fa-solid fa-upload"></i> ارفع محاضرة</a>
  </header>
  <nav class="class-nav">
    <a href="class_lectures.php?id=<?php echo $offering_id; ?>"><i class="fa-solid fa-book"></i> المحاضرات</a>
    <a href="class_exams.php?id=<?php echo $offering_id; ?>"><i class="fa-solid fa-clipboard-list"></i> الامتحانات</a>
    <a href="/mo7adaraty/student/community/index.php"><i class="fa-solid fa-users"></i> ساحة المشاركة</a>
  </nav>
  <div class="content-layout">
      <aside class="subjects-sidebar" id="subjectsSidebar">
          <?php if (empty($subjects_with_lectures)): ?>
              <p style="text-align: center; padding: 10px;">لا توجد مواد متاحة لهذا المقرر.</p>
          <?php else: ?>
              <?php $is_first_tab = true; ?>
              <?php foreach (array_keys($subjects_with_lectures) as $subject_name): ?>
                  <a href="#" class="subject-link <?php if($is_first_tab) echo 'active'; ?>" data-tab="tab-<?php echo md5($subject_name); ?>">
                      <i class="fa-solid fa-book-open-reader"></i>
                      <span><?php echo htmlspecialchars($subject_name); ?></span>
                  </a>
                  <?php $is_first_tab = false; ?>
              <?php endforeach; ?>
          <?php endif; ?>
      </aside>

      <main class="lectures-main-content">
          <?php if (empty($subjects_with_lectures)): ?>
              <p style="text-align: center; padding: 20px; background: var(--card-bg); border-radius: 12px;">لا يوجد محتوى لعرضه حالياً.</p>
          <?php else: ?>
              <?php $is_first_content = true; ?>
              <?php foreach ($subjects_with_lectures as $subject_name => $lectures): ?>
                  <div id="tab-<?php echo md5($subject_name); ?>" class="tab-content <?php if($is_first_content) echo 'active'; ?>">
                      <div class="lectures-grid">
                          <?php if (empty($lectures)): ?>
                              <p style="text-align: center; padding: 20px; background: var(--card-bg); border-radius: 12px;">لا توجد محاضرات لهذه المادة بعد.</p>
                          <?php else: ?>
                              <?php foreach ($lectures as $lecture): ?>
                                  <?php
                                      $icon_class = 'fa-solid fa-file';
                                      if ($lecture['file_type'] == 'pdf') $icon_class = 'fa-solid fa-file-pdf';
                                      elseif (in_array($lecture['file_type'], ['jpg', 'jpeg', 'png'])) $icon_class = 'fa-solid fa-file-image';
                                  ?>
                                  <div class="lecture-card" id="lecture-<?php echo $lecture['lecture_id']; ?>">
                                      <div class="card-header">
                                          <i class="<?php echo $icon_class; ?> file-icon"></i>
                                          <div class="header-info">
                                              <h3><?php echo htmlspecialchars($lecture['title']); ?></h3>
                                              <p>بواسطة: <strong><?php echo htmlspecialchars($lecture['uploader_name']); ?></strong></p>
                                          </div>
                                      </div>
                                      <?php if(!empty($lecture['description'])): ?>
                                      <div class="card-body">
                                          <p><?php echo nl2br(htmlspecialchars($lecture['description'])); ?></p>
                                      </div>
                                      <?php endif; ?>
                                      <div class="card-footer">
                                          <div class="actions-left">
                                              <a href="../<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" class="view-btn"><i class="fa-solid fa-eye"></i> عرض</a>
                                              <a href="../download.php?type=lecture&id=<?php echo $lecture['lecture_id']; ?>" class="download-btn"><i class="fa-solid fa-download"></i> تحميل</a>
                                              <button class="report-btn" data-content-id="<?php echo $lecture['lecture_id']; ?>" data-content-type="lecture">
                                                  <i class="fa-solid fa-flag"></i> إبلاغ
                                              </button>
                                          </div>
                                          <div class="actions-right">
                                              <button class="reaction-btn like <?php if($lecture['user_reaction'] == 'like') echo 'active'; ?>" data-content-id="<?php echo $lecture['lecture_id']; ?>">
                                                  <i class="fa-solid fa-thumbs-up"></i> <span class="count">(<?php echo $lecture['likes_count']; ?>)</span>
                                              </button>
                                              <button class="reaction-btn dislike <?php if($lecture['user_reaction'] == 'dislike') echo 'active'; ?>" data-content-id="<?php echo $lecture['lecture_id']; ?>">
                                                  <i class="fa-solid fa-thumbs-down"></i> <span class="count">(<?php echo $lecture['dislikes_count']; ?>)</span>
                                              </button>
                                          </div>
                                      </div>
                                      <div class="comments-section">
                                          <div class="comments-container" data-content-id="<?php echo $lecture['lecture_id']; ?>" data-content-type="lecture"></div>
                                          <form class="comment-form" action="../comments/add_comment.php" method="POST">
                                              <input type="hidden" name="content_id" value="<?php echo $lecture['lecture_id']; ?>">
                                              <input type="hidden" name="content_type" value="lecture">
                                              <textarea name="comment_text" placeholder="اكتب تعليقك هنا..." required></textarea>
                                              <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                                          </form>
                                      </div>
                                  </div>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      </div>
                  </div>
                  <?php $is_first_content = false; ?>
              <?php endforeach; ?>
          <?php endif; ?>
      </main>
  </div>
  
  <div id="reportModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>الإبلاغ عن محتوى</h2>
        <span class="close-btn">&times;</span>
      </div>
      <div class="modal-body">
        <form id="reportForm" action="../submit_report.php" method="POST">
          <input type="hidden" name="content_id" id="report_content_id">
          <input type="hidden" name="content_type" id="report_content_type">
          <div class="form-group">
            <label for="reason">سبب البلاغ:</label>
            <textarea id="reason" name="reason" placeholder="يرجى توضيح سبب الإبلاغ..." required></textarea>
          </div>
          <button type="submit" class="upload-btn" style="width:100%"><i class="fa-solid fa-paper-plane"></i> إرسال البلاغ</button>
        </form>
      </div>
    </div>
  </div>
  
  <div id="downloadModal" class="modal">
    <div class="modal-content download-modal-content">
      <div class="modal-header">
        <h2>جاري تجهيز الملف...</h2>
        <span class="close-btn">&times;</span>
      </div>
      <div class="modal-body">
        <div class="countdown-circle">
          <svg class="progress-ring" width="120" height="120">
            <circle class="progress-ring-circle" stroke="#007bff" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
          </svg>
          <span class="countdown-number">8</span>
        </div>
        <p>سيبدأ التحميل بعد <span id="countdownText">8</span> ثوانٍ...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    var firstTab = $('.subject-link').first();
    if (firstTab.length) {
        firstTab.addClass('active');
        $('#' + firstTab.data('tab')).addClass('active');
    }

    $('.subject-link').on('click', function(e) {
        e.preventDefault();
        $('.subject-link').removeClass('active');
        $(this).addClass('active');
        var tabId = $(this).data('tab');
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    $('#sidebarToggle').on('click', function() {
        var sidebar = $('#subjectsSidebar');
        sidebar.toggleClass('open');
        $(this).toggleClass('open');
        if (sidebar.hasClass('open')) {
            $(this).html('<i class="fa-solid fa-list-ul" style="transition: transform 0.3s ease;"></i> إخفاء المواد');
        } else {
            $(this).html('<i class="fa-solid fa-list-ul" style="transition: transform 0.3s ease;"></i> عرض المواد');
        }
    });

    $('.reaction-btn').on('click', function() {
        var button = $(this);
        var reactionType = button.hasClass('like') ? 'like' : 'dislike';
        var contentId = button.data('content-id');
        var lectureCard = $('#lecture-' + contentId);
        $.post('../handle_reaction.php', {
            content_id: contentId, content_type: 'lecture', reaction_type: reactionType
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    lectureCard.find('.reaction-btn.like .count').text('(' + data.likes + ')');
                    lectureCard.find('.reaction-btn.dislike .count').text('(' + data.dislikes + ')');
                    lectureCard.find('.reaction-btn').removeClass('active');
                    if (data.user_reaction) {
                       lectureCard.find('.reaction-btn.' + data.user_reaction).addClass('active');
                    }
                }
            } catch (e) { console.error("Error parsing response: ", e, response); }
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

    $(".close-btn").on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
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
            } catch (ex) {
                console.error("Error parsing response: ", response);
                alert("حدث خطأ غير متوقع.");
            }
        }).fail(function() {
            alert("فشل إرسال البلاغ. يرجى المحاولة مرة أخرى.");
        });
    });

    var downloadModal = $("#downloadModal");
    var countdownText = $("#countdownText");
    var countdownNumber = $(".countdown-number");
    var progressCircle = $(".progress-ring-circle");
    var downloadUrl = '';

    // حساب محيط الدائرة للرسوم المتحركة
    var radius = progressCircle.attr('r');
    var circumference = 2 * Math.PI * radius;
    progressCircle.css('strokeDasharray', `${circumference} ${circumference}`);
    progressCircle.css('strokeDashoffset', '0');

    // التعامل مع النقر على زر التحميل
    $(document).on('click', '.download-btn', function(e) {
        e.preventDefault();
        downloadUrl = $(this).attr('href'); // حفظ رابط التحميل
        downloadModal.show();

        // إعادة تعيين العداد
        var timeLeft = 8;
        countdownText.text(timeLeft);
        countdownNumber.text(timeLeft);
        progressCircle.css('strokeDashoffset', '0');

        // إعداد الرسوم المتحركة للدائرة
        progressCircle.css('strokeDashoffset', circumference);
        setTimeout(function() {
            progressCircle.css('transition', 'stroke-dashoffset 8s linear');
            progressCircle.css('strokeDashoffset', '0');
        }, 50);

        // بدء العداد التنازلي
        var countdown = setInterval(function() {
            timeLeft--;
            countdownText.text(timeLeft);
            countdownNumber.text(timeLeft);

            if (timeLeft <= 0) {
                clearInterval(countdown);
                downloadModal.hide();
                window.location.href = downloadUrl; // بدء التحميل
            }
        }, 1000);
    });

    // إغلاق النافذة المنبثقة
    $(".close-btn", downloadModal).on('click', function() {
        downloadModal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == downloadModal[0]) {
            downloadModal.hide();
        }
    });
});
</script>
<script src="../comments/assets/js/comments.js"></script>
<?php include '../templates/footer.php'; ?>

</body>
</html>