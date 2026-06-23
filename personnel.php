<?php
require_once 'connect.php';

$dept_id = (int)($_GET['id'] ?? 0);

// 1. ดึงข้อมูลชื่อกลุ่มงาน
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
$stmt->execute([':id' => $dept_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    http_response_code(404);
    die('ไม่พบข้อมูลกลุ่มงาน');
}

// 2. ดึงเฉพาะข้อมูลในหมวด "ทำเนียบบุคลากร" หรือ "personnel" ของกลุ่มงานนี้เท่านั้น
$stmt = $conn->prepare("SELECT * FROM department_contents 
                        WHERE department_id = :department_id 
                        AND (section = 'personnel' OR section = 'ทำเนียบบุคลากร') 
                        ORDER BY sort_order ASC, id DESC");
$stmt->execute([':department_id' => $dept_id]);
$personnel_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderPersonnelImage($fileName) {
    if (empty($fileName)) {
        return '<div class="no-avatar-box"><i class="bi bi-person-bounding-box"></i></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    return '<div class="personnel-image-box">
                <img src="' . $path . '" class="card-img-top personnel-card-img" alt="บุคลากร">
            </div>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทำเนียบบุคลากร <?= htmlspecialchars($department['name']) ?> - โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dept-orange: #d96f18;
            --dept-orange-dark: #b95b0f;
            --dept-bg: #fcf8f5;
            --dept-text: #2d3748;
        }
        body {
            background: var(--dept-bg);
            color: var(--dept-text);
            font-family: 'Sarabun', 'Kanit', Arial, sans-serif;
        }
        .navbar-custom {
            background: var(--dept-orange);
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .navbar-custom .navbar-brand {
            color: #fff;
            font-weight: 600;
        }
        .hero-section {
            background: var(--dept-orange);
            color: #fff;
            padding: 60px 16px 70px;
            border-radius: 0 0 56px 56px;
            text-align: center;
            margin-bottom: 48px;
        }
        .hero-section h1 {
            font-size: clamp(28px, 4vw, 48px);
            font-weight: 700;
        }
        .personnel-card {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
            background: #fff;
            transition: transform 0.2s ease;
        }
        .personnel-card:hover {
            transform: translateY(-5px);
        }
        
        /* สไตล์กล่องรูปภาพเจ้าหน้าที่ ให้แสดงรูปเต็มใบ ไม่ตัดขอบหัวขาด */
        .personnel-image-box {
            width: 100%;
            height: 320px;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            border-bottom: 1px solid #f1f1f1;
        }
        .personnel-card-img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* รูปภาพเต็ม ไม่โดนบีบสัดส่วนบุคคล */
        }
        .no-avatar-box {
            width: 100%;
            height: 320px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }
        .no-avatar-box i {
            font-size: 64px;
        }
        .btn-back {
            color: var(--dept-orange);
            border-color: var(--dept-orange);
            font-weight: 600;
        }
        .btn-back:hover {
            background: var(--dept-orange);
            color: #fff;
        }
        .empty-section {
            background: #fff;
            border: 1px dashed #e1b083;
            border-radius: 8px;
            color: #8a6a50;
            padding: 40px;
            text-align: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="department.php?id=<?= $dept_id ?>"><i class="bi bi-chevron-left"></i> กลับสู่หน้ากลุ่มงาน</a>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <h1><i class="bi bi-people-fill"></i> ทำเนียบบุคลากร</h1>
        <p class="lead mb-0"><?= htmlspecialchars($department['name']) ?> - โรงพยาบาลปากช่องนานา</p>
    </div>
</section>

<main class="container mb-5">
    <?php if(empty($personnel_items)): ?>
        <div class="empty-section fs-5">ยังไม่มีข้อมูลบุคลากรในกลุ่มงานนี้</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4 justify-content-center">
            <?php foreach($personnel_items as $item): 
                $fileName = $item['file_name'] ?? '';
            ?>
                <div class="col">
                    <article class="card personnel-card text-center h-100">
                        <!-- แสดงรูปภาพบุคลากร -->
                        <?= renderPersonnelImage($fileName) ?>
                        
                        <div class="card-body d-flex flex-column justify-content-center">
                            <!-- แสดงชื่อ-นามสกุล -->
                            <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                            
                            <!-- แสดงตำแหน่ง (ถ้ามีระบุในรายละเอียด) -->
                            <?php if(!empty($item['content'])): ?>
                                <p class="card-text text-muted small mb-0"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-5">
        <a href="department.php?id=<?= $dept_id ?>" class="btn btn-back px-4 py-2"><i class="bi bi-house-door"></i> กลับหน้าหลักกลุ่มงาน</a>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>