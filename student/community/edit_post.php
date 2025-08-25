<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// جلب ID المنشور والتحقق منه
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    header("Location: my_pending_posts.php");
    exit();
}

try {
    // جلب بيانات المنشور والتأكد من أن الطالب هو المالك وأن المنشور لا يزال قيد المراجعة
    $stmt = $pdo->prepare("
        SELECT * FROM posts 
        WHERE post_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch();

    if (!$post) {
        // إذا لم يتم العثور عليه (إما لأنه ليس ملكه أو تمت مراجعته بالفعل)
        $_SESSION['post_action_status'] = ['type' => 'error', 'message' => 'لا يمكنك تعديل هذا المنشور.'];
        header("Location: my_pending_posts.php");
        exit();
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
   
   
    <title>تعديل المنشور - Mo7adaraty</title>
      <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
     <style>
        :root {
            --brand-color: #007bff; --bg-color: #f4f8fc; --card-bg: #ffffff;
            --border-color: #e9ecef; --text-primary: #343a40;
        }
        body { background-color: var(--bg-color); font-family: 'Tajawal', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .form-container { max-width: 600px; width: 100%; background: var(--card-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin: 20px; }
        h2 { text-align: center; color: var(--brand-color); margin-bottom: 25px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; }
        .form-group textarea { min-height: 120px; }
        .current-image { max-width: 150px; border-radius: 8px; margin-top: 10px; display: block; }
        .hint { font-size: 14px; color: #6c757d; }
    </style>
</head>
<body class="center-content">

<div class="form-container">
    <h2><i class="fa-solid fa-pen-to-square"></i> تعديل المنشور</h2>

    <form action="update_post_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">

        <div class="form-group">
            <label for="post_text">النص:</label>
            <textarea id="post_text" name="post_text" class="form-control" rows="5"><?php echo htmlspecialchars($post['post_text']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="post_image">استبدال الصورة (اختياري):</label>
            <input type="file" id="post_image" name="post_image" class="form-control" accept="image/*">
            <?php if (!empty($post['image_path'])): ?>
                <p class="hint">الصورة الحالية:</p>
                <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="الصورة الحالية" class="current-image">
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width:100%;">حفظ التعديلات</button>
    </form>
     <div class="switch-form" style="text-align:center; margin-top: 15px;">
        <p><a href="my_pending_posts.php">العودة إلى منشوراتي</a></p>
    </div>
</div>

</body>
</html>