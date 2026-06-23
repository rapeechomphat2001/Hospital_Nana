<?php
require_once 'connect.php';

$dept_id = (int)($_GET['dept_id'] ?? 0);
$backUrl = $dept_id > 0 ? 'department.php?id=' . $dept_id : 'index.php';
$backLabel = $dept_id > 0 ? 'กลับสู่หน้ากลุ่มงาน' : 'กลับสู่หน้าแรก';
$department_name = '';

if ($dept_id > 0) {
    $stmtDept = $conn->prepare("SELECT name FROM departments WHERE id = :id");
    $stmtDept->execute([':id' => $dept_id]);
    $dept = $stmtDept->fetch(PDO::FETCH_ASSOC);
    if ($dept && !empty($dept['name'])) {
        $department_name = $dept['name'];
    }
}

$stmt = $conn->prepare("SELECT dc.*, d.name AS department_name FROM department_contents dc LEFT JOIN departments d ON d.id = dc.department_id WHERE dc.section IN ('service_profile', 'Service Profile')" . ($dept_id > 0 ? ' AND dc.department_id = :department_id' : '') . " ORDER BY d.name ASC, dc.sort_order ASC, dc.id DESC");
$params = [];
if ($dept_id > 0) {
    $params[':department_id'] = $dept_id;
}
$stmt->execute($params);
$service_profile_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderServiceProfilePreview($fileName) {
    if (empty($fileName)) {
        return '<div class="service-profile-file-box"><i class="bi bi-file-earmark-text"></i></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // PDF: embed first page preview in an iframe for quick view
    if ($ext === 'pdf') {
        return '<div class="service-profile-pdf-preview" style="width:100%;height:220px;overflow:hidden;background:#f8f5f0;"><iframe src="' . $path . '#page=1&toolbar=0&navpanes=0" style="width:100%;height:100%;border:0;"></iframe></div>';
    }

    // Images: show image preview
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<img src="' . $path . '" class="service-profile-image" alt="Service Profile">';
    }

    // Videos: show video player icon (handled elsewhere) - fallback to generic box
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
        return '<div class="service-profile-file-box"><i class="bi bi-file-earmark-play-fill"></i><span>' . strtoupper($ext) . '</span></div>';
    }

    // Word documents: show only extension (DOC / DOCX)
    if (in_array($ext, ['doc', 'docx'])) {
        return '<div class="service-profile-file-box"><i class="bi bi-file-earmark-word-fill"></i><span>' . strtoupper($ext) . '</span></div>';
    }

    // Other files: show filename (shortened if too long)
    $display = strlen($safeFile) > 60 ? substr($safeFile, 0, 40) . '...' . substr($safeFile, -10) : $safeFile;
    return '<div class="service-profile-file-box"><i class="bi bi-file-earmark-text"></i><span>' . $display . '</span></div>';
}

function serviceProfileActionLabel($fileName, $linkUrl) {
    if (!empty($linkUrl)) {
        return 'เปิดลิงก์';
    }
    if (empty($fileName)) {
        return 'ไม่มีไฟล์';
    }
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) return 'เล่นวิดีโอ';
    if ($ext === 'pdf') return 'เปิดไฟล์';
    return 'ดาวน์โหลดไฟล์';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Profile - โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --hosp-orange: #d96f18;
            --hosp-orange-dark: #b95b0f;
            --bg-page: #fbf8f2;
            --text-primary: #2d3748;
        }
        body {
            background: var(--bg-page);
            color: var(--text-primary);
            font-family: 'Sarabun', 'Kanit', Arial, sans-serif;
        }
        .navbar-custom {
            background: var(--hosp-orange);
            box-shadow: 0 2px 10px rgba(0,0,0,.12);
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #fff;
            font-weight: 600;
        }
        .hero-section {
            background: var(--hosp-orange);
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
        .hero-section p {
            color: rgba(255,255,255,.9);
            margin-top: 10px;
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
        .service-profile-preview {
            width: 100%;
            min-height: 220px;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .service-profile-preview img,
        .service-profile-file-box {
            width: 100%;
            height: 100%;
        }
        .service-profile-preview img {
            object-fit: contain;
            max-height: 100%;
        }
        .service-profile-file-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px;
            color: #6b7280;
        }
        .service-profile-file-box i {
            font-size: 42px;
            color: var(--hosp-orange);
        }
        .personnel-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1.25rem;
        }
        .service-profile-card {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
            background: #fff;
            transition: transform 0.2s ease;
        }
        .service-profile-card:hover {
            transform: translateY(-5px);
        }
        .service-profile-card .card-body {
            border-top: none !important;
            box-shadow: none !important;
        }
        .service-profile-card hr { display: none !important; }
        /* Extra guard: remove pseudo-elements and any thin lines between preview and body */
        .service-profile-card .service-profile-preview + .card-body,
        .service-profile-card .service-profile-preview,
        .service-profile-card .service-profile-preview::before,
        .service-profile-card .service-profile-preview::after,
        .service-profile-card .card-body::before,
        .service-profile-card .card-body::after {
            border-top: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
            background-clip: padding-box !important;
            content: none !important;
            display: block !important;
            height: auto !important;
        }
        .service-profile-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .service-profile-meta {
            color: #6b7280;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .btn-service {
            color: var(--hosp-orange);
            border-color: var(--hosp-orange);
            font-weight: 600;
        }
        .btn-service:hover {
            background: var(--hosp-orange);
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
        <a class="navbar-brand" href="<?= htmlspecialchars($backUrl) ?>"><i class="bi bi-chevron-left"></i> <?= htmlspecialchars($backLabel) ?></a>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <h1><i class="bi bi-megaphone-fill"></i> Service Profile</h1>
        <?php if (!empty($department_name)): ?>
            <p class="lead mb-0"><?= htmlspecialchars($department_name) ?> - โรงพยาบาลปากช่องนานา</p>
        <?php else: ?>
            <p class="lead mb-0">รวมเอกสารและข้อมูล Service Profile ของกลุ่มงาน</p>
        <?php endif; ?>
    </div>
</section>

<main class="container mb-5">
    <?php if(empty($service_profile_items)): ?>
        <div class="empty-section">ยังไม่มีข้อมูล Service Profile</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach($service_profile_items as $item): 
                $fileName = $item['file_name'] ?? '';
                $linkUrl = $item['link_url'] ?? '';
                $targetUrl = !empty($linkUrl) ? $linkUrl : (!empty($fileName) ? 'uploads/' . $fileName : '#');
                $actionLabel = serviceProfileActionLabel($fileName, $linkUrl);
            ?>
                <div class="col-md-6 col-xl-4">
                    <article class="card personnel-card service-profile-card h-100">
                        <div class="service-profile-preview">
                            <?= renderServiceProfilePreview($fileName) ?>
                        </div>
                        <div class="card-body">
                            <div class="service-profile-card-title"><?= htmlspecialchars($item['title']) ?></div>
                            <?php if(!empty($item['department_name'])): ?>
                                <div class="service-profile-meta"><span>หน่วยงาน: <?= htmlspecialchars($item['department_name']) ?></span></div>
                            <?php endif; ?>
                            <?php if(!empty($item['content'])): ?>
                                <p class="mb-3 text-muted" style="min-height: 60px;"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                            <?php endif; ?>
                            <?php if($targetUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-service w-100"><?= htmlspecialchars($actionLabel) ?></a>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
