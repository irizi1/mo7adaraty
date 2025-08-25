<header class="admin-header">
    <div class="header-content">
        <h1>لوحة تحكم Mo7adaraty</h1>
        <div class="admin-info">
            <span>مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            
            <a href="/mo7adaraty/student/profil.php" style="color: #17a2b8; font-weight: bold;">
                <i class="fa-solid fa-user-graduate"></i> الواجهة الطلابية
            </a>
            <a href="/mo7adaraty/logout.php">تسجيل الخروج</a>
        </div>
        <button id="sidebar-toggle" class="sidebar-toggle-btn">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>