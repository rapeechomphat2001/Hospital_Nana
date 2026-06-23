<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$adminUsername = 'admin';
$adminPasswordHash = '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9';
$redirectUrl = $_GET['redirect'] ?? 'admin.php';
$loginError = false;

if (!empty($_SESSION['is_admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordHash = hash('sha256', $password);

    if ($username === $adminUsername && hash_equals($adminPasswordHash, $passwordHash)) {
        $_SESSION['is_admin_logged_in'] = true;
        $_SESSION['admin_username'] = $adminUsername;

        $redirectUrl = trim($redirectUrl);
        if ($redirectUrl === '' || preg_match('/^(https?:)?\/\//i', $redirectUrl) || strpos($redirectUrl, 'login.php') !== false) {
            $redirectUrl = 'admin.php';
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    $loginError = true;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <h3 class="card-title text-center text-hospital mb-3">เข้าสู่ระบบผู้ดูแล</h3>
                <p class="text-center text-muted mb-4">กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนจัดการเว็บไซต์</p>

                <?php if ($loginError): ?>
                    <div class="alert alert-danger" role="alert">
                        ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-hospital-orange w-100">เข้าสู่ระบบ</button>
                </form>
                <div class="d-grid mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">กลับไปหน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
