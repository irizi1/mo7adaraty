<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id  = $_SESSION['user_id'];

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
    
    // 4. جلب معلومات المقرر والفصل الدراسي
    $stmt_offering_info = $pdo->prepare("
        SELECT c.class_id, c.class_name 
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        WHERE co.offering_id = ?
    ");
    $stmt_offering_info->execute([$offering_id]);
    $class_info = $stmt_offering_info->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) {
        die("خطأ: لم يتم العثور على المقرر.");
    }
    $current_class_id = $class_info['class_id'];

    // 5. [تم التحديث] جلب كل الامتحانات الخاصة بالفصل الدراسي بأكمله
    $stmt_exams = $pdo->prepare("
        SELECT 
            e.exam_id, e.title, e.description, e.file_path, e.file_type, e.upload_date,
            s.subject_name,
            p.professor_name,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND reaction_type='like') as likes_count,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND reaction_type='dislike') as dislikes_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND user_id = :user_id) as user_reaction
        FROM exams e
        JOIN users u ON e.uploader_user_id = u.user_id
        JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE e.class_id = :class_id
        ORDER BY e.upload_date DESC
    ");
    $stmt_exams->execute(['user_id' => $user_id, 'class_id' => $current_class_id]);
    $exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

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
  <title>امتحانات <?php echo htmlspecialchars($class_info['class_name']); ?> - Mo7adaraty</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --brand-color: #007bff; --bg-color: #f4f8fc; --card-bg: #ffffff;
      --border-color: #e9ecef; --text-secondary: #6c757d; --green-color: #28a745;
      --red-color: #dc3545; --brand-darker: #0056b3;
    }
    body { font-family: 'Tajawal', sans-serif; background: var(--bg-color); margin:0; }
    .container { max-width: 1200px; margin:auto; padding:20px; }
    .page-header {
      background: var(--card-bg); border-radius:12px; padding:20px; margin-bottom:25px;
      box-shadow:0 4px 15px rgba(0,0,0,0.07);
      display: flex; justify-content: space-between; align-items: center;
    }
    .page-header h1 { margin:0; font-size:22px; color:var(--brand-color); }
    .page-header a { color: var(--brand-color); text-decoration:none; font-weight: 500;}
    .upload-btn {
        background-color: var(--green-color); color: white !important; padding: 10px 20px;
        border-radius: 8px; text-decoration: none; font-weight: 600;
    }
    .class-nav {
      display:flex; justify-content:center; gap:20px; background:var(--card-bg);
      border-radius:12px; padding:10px; margin-bottom:25px; box-shadow:0 2px 8px rgba(0,0,0,0.06);
    }
    .class-nav a { color:var(--text-secondary); text-decoration:none; padding:10px 15px; border-radius:8px; font-weight:600; }
    .class-nav a.active { background:var(--brand-color); color:#fff; }
    .lectures-main-content { flex: 1; }
    .lectures-grid { display:grid; gap:20px; }
    .lecture-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); display: flex; flex-direction: column; }
    .card-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
    .file-icon { font-size: 28px; color: var(--brand-color); }
    .header-info h3 { margin: 0; font-size: 18px; }
    .header-info p { margin: 4px 0 0; font-size: 14px; color: var(--text-secondary); }
    .card-body { padding: 20px; flex-grow: 1; }
    .card-body p { margin: 0; line-height:1.7; }
    .card-footer { padding: 15px 20px; background-color: #f8f9fa; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .actions-left a { text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 14px; font-weight: 600; color: white; display: inline-flex; align-items: center; gap: 6px; }
    .view-btn { background-color: #6c757d; }
    .download-btn { background-color: var(--brand-color); margin-right: 5px; }
    .actions-right { display: flex; gap: 10px; }
    .reaction-btn { background: #e9ecef; color: var(--text-secondary); padding: 8px 12px; border: 1px solid transparent; border-radius: 6px; cursor: pointer; transition: all 0.2s ease;}
    .reaction-btn.active.like { background-color: var(--brand-color); color: white; border-color: var(--brand-darker); }
    .reaction-btn.active.dislike { background-color: var(--red-color); color: white; border-color: #b02a37; }
    .comments-section { padding: 20px; border-top: 1px solid var(--border-color); }
    .comment-author-info img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
    .comment-form { display: flex; gap: 10px; }
    .comment-form textarea { flex-grow: 1; border: 1px solid #ccc; border-radius: 5px; padding: 10px; }
    .comment-form button { border: none; background-color: var(--green-color); color: white; padding: 0 15px; border-radius: 5px; cursor: pointer; }
  </style>
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="container">
  <header class="page-header">
    <div>
        <h1><i class="fa-solid fa-school"></i> امتحانات <?php echo htmlspecialchars($class_info['class_name']); ?></h1>
        <a href="profil.php">&larr; العودة إلى ملفي الشخصي</a>
    </div>
    <div>
        <a href="upload_exam.php?id=<?php echo $offering_id; ?>" class="upload-btn">
            <i class="fa-solid fa-upload"></i> ارفع امتحان
        </a>
    </div>
  </header>

<nav class="class-nav">
   <a href="class_lectures.php?id=<?php echo $offering_id; ?>"><i class="fa-solid fa-book"></i> المحاضرات</a>
   <a href="class_exams.php?id=<?php echo $offering_id; ?>"><i class="fa-solid fa-clipboard-list"></i> الامتحانات</a>
   <a href="/mo7adaraty/student/community/index.php"><i class="fa-solid fa-users"></i> ساحة المشاركة</a>
 </nav>
  <main class="lectures-main-content">
      <?php if (empty($exams)): ?>
          <p style="text-align: center; padding: 20px; background: var(--card-bg); border-radius: 12px;">لا توجد امتحانات منشورة لهذا الفصل حالياً.</p>
      <?php else: ?>
          <div class="lectures-grid">
              <?php foreach ($exams as $exam): ?>
                  <?php
                      $icon_class = 'fa-solid fa-file';
                      if ($exam['file_type'] == 'pdf') $icon_class = 'fa-solid fa-file-pdf';
                      elseif (in_array($exam['file_type'], ['jpg', 'jpeg', 'png'])) $icon_class = 'fa-solid fa-file-image';
                  ?>
                  <div class="lecture-card" id="exam-<?php echo $exam['exam_id']; ?>">
                      <div class="card-header">
                          <i class="<?php echo $icon_class; ?> file-icon"></i>
                          <div class="header-info">
                              <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                              <p>
                                المادة: <strong><?php echo htmlspecialchars($exam['subject_name']); ?></strong> - 
                                الأستاذ: <strong><?php echo htmlspecialchars($exam['professor_name'] ?? 'غير محدد'); ?></strong>
                              </p>
                          </div>
                      </div>
                      <?php if(!empty($exam['description'])): ?>
                      <div class="card-body">
                          <p><?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                      </div>
                      <?php endif; ?>
                      <div class="card-footer">
                          <div class="actions-left">
                              <a href="../<?php echo htmlspecialchars($exam['file_path']); ?>" target="_blank" class="view-btn"><i class="fa-solid fa-eye"></i> عرض</a>
                              <a href="../download.php?type=exam&id=<?php echo $exam['exam_id']; ?>" class="download-btn"><i class="fa-solid fa-download"></i> تحميل</a>
                          </div>
                          <div class="actions-right">
                              <button class="reaction-btn like <?php if($exam['user_reaction'] == 'like') echo 'active'; ?>" data-content-id="<?php echo $exam['exam_id']; ?>">
                                  <i class="fa-solid fa-thumbs-up"></i> <span class="count">(<?php echo $exam['likes_count']; ?>)</span>
                              </button>
                              <button class="reaction-btn dislike <?php if($exam['user_reaction'] == 'dislike') echo 'active'; ?>" data-content-id="<?php echo $exam['exam_id']; ?>">
                                  <i class="fa-solid fa-thumbs-down"></i> <span class="count">(<?php echo $exam['dislikes_count']; ?>)</span>
                              </button>
                          </div>
                      </div>
                      <div class="comments-section">
                          <div class="comments-container" data-content-id="<?php echo $exam['exam_id']; ?>" data-content-type="exam"></div>
                          <form class="comment-form" action="../comments/add_comment.php" method="POST">
                              <input type="hidden" name="content_id" value="<?php echo $exam['exam_id']; ?>">
                              <input type="hidden" name="content_type" value="exam">
                              <textarea name="comment_text" placeholder="اكتب تعليقك هنا..." required></textarea>
                              <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                          </form>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    // Reaction buttons functionality
    $('.reaction-btn').on('click', function() {
        var button = $(this);
        var reactionType = button.hasClass('like') ? 'like' : 'dislike';
        var contentId = button.data('content-id');
        var examCard = $('#exam-' + contentId);
        $.post('../handle_reaction.php', {
            content_id: contentId, content_type: 'exam', reaction_type: reactionType
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    examCard.find('.reaction-btn.like .count').text('(' + data.likes + ')');
                    examCard.find('.reaction-btn.dislike .count').text('(' + data.dislikes + ')');
                    var likeBtn = examCard.find('.reaction-btn.like');
                    var dislikeBtn = examCard.find('.reaction-btn.dislike');
                    likeBtn.removeClass('active');
                    dislikeBtn.removeClass('active');
                    if (data.user_reaction === 'like') {
                        likeBtn.addClass('active');
                    } else if (data.user_reaction === 'dislike') {
                        dislikeBtn.addClass('active');
                    }
                }
            } catch (e) { console.error("Error parsing response: ", e); }
        });
    });
});
</script>
<script src="../comments/assets/js/comments.js"></script>
<?php include '../templates/footer.php'; ?>

</body>
</html>