<?php
require_once 'connect.php';

$section = $_GET['section'] ?? '';
$dept_id = (int)($_GET['dept_id'] ?? 0);

$section_labels = [
    'indicator' => 'ตัวชี้วัด',
    'structure' => 'โครงสร้างการบริหารงาน',
    'service' => 'การให้บริการต่างๆ',
    'academic' => 'ผลงานวิจัย',
    'wi' => 'WI'
];

$section_icons = [
    'indicator' => 'graph-up',
    'structure' => 'people-fill',
    'service' => 'gear-wide-connected',
    'academic' => 'journal-bookmark',
    'wi' => 'clipboard-data'
];

if (!isset($section_labels[$section])) {
    http_response_code(404);
    die('ไม่พบหน้าที่ร้องขอ');
}

$section_label = $section_labels[$section];
$section_icon = $section_icons[$section] ?? 'file-earmark-text';
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

$stmt = $conn->prepare("SELECT dc.*, d.name AS department_name FROM department_contents dc LEFT JOIN departments d ON d.id = dc.department_id WHERE (dc.section = :section OR dc.section = :label)" . ($dept_id > 0 ? ' AND dc.department_id = :department_id' : '') . " ORDER BY dc.sort_order ASC, dc.id DESC");
$params = [':section' => $section, ':label' => $section_label];
if ($dept_id > 0) {
    $params[':department_id'] = $dept_id;
}
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderSectionPreview($fileName) {
    if (empty($fileName)) {
        return '<div class="section-file-box"><i class="bi bi-file-earmark-text"></i></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // PDF: embed preview
    if ($ext === 'pdf') {
        return '<div class="section-pdf-preview" style="width:100%;height:220px;overflow:hidden;background:#f8f5f0;"><iframe src="' . $path . '#page=1&toolbar=0&navpanes=0" style="width:100%;height:100%;border:0;"></iframe></div>';
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<img src="' . $path . '" class="section-card-img" alt="' . $safeFile . '">';
    }

    if (in_array($ext, ['doc', 'docx'])) {
        return '<div class="section-file-box"><i class="bi bi-file-earmark-word-fill"></i><span>' . strtoupper($ext) . '</span></div>';
    }

    $display = strlen($safeFile) > 60 ? substr($safeFile, 0, 40) . '...' . substr($safeFile, -10) : $safeFile;
    return '<div class="section-file-box"><i class="bi bi-file-earmark-text"></i><span>' . $display . '</span></div>';
}

function sectionActionLabel($fileName, $linkUrl) {
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
    <title><?= htmlspecialchars($section_label) ?> - โรงพยาบาลปากช่องนานา</title>
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
        .hero-section p {
            color: rgba(255,255,255,.9);
            margin-top: 10px;
        }
        .section-card {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
            background: #fff;
            transition: transform 0.2s ease;
        }
        .section-card:hover {
            transform: translateY(-5px);
        }
            .section-card .card-body { border-top: none !important; box-shadow: none !important; }
            .section-card hr { display: none !important; }
            .section-card .section-preview + .card-body,
            .section-card .section-preview,
            .section-card .section-preview::before,
            .section-card .section-preview::after,
            .section-card .card-body::before,
            .section-card .card-body::after {
                border-top: none !important;
                border-bottom: none !important;
                box-shadow: none !important;
                background-clip: padding-box !important;
                content: none !important;
                display: block !important;
                height: auto !important;
            }
        .section-preview {
            width: 100%;
                    min-height: 220px;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .section-card-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .section-file-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px;
            color: #6b7280;
            text-align: center;
        }
        .section-file-box i {
            font-size: 42px;
            color: var(--dept-orange);
        }
        .section-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1.25rem;
        }
        .section-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .section-card-meta {
            color: #6b7280;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .btn-section {
            color: var(--dept-orange);
            border-color: var(--dept-orange);
            font-weight: 600;
        }
        .btn-section:hover {
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
        <a class="navbar-brand" href="<?= htmlspecialchars($backUrl) ?>"><i class="bi bi-chevron-left"></i> <?= htmlspecialchars($backLabel) ?></a>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <h1><i class="bi bi-<?= htmlspecialchars($section_icon) ?>"></i> <?= htmlspecialchars($section_label) ?></h1>
        <?php if (!empty($department_name)): ?>
            <p class="lead mb-0"><?= htmlspecialchars($department_name) ?> - โรงพยาบาลปากช่องนานา</p>
        <?php else: ?>
            <p class="lead mb-0">รวมข้อมูล<?= htmlspecialchars($section_label) ?>ของกลุ่มงาน</p>
        <?php endif; ?>
    </div>
</section>

<main class="container mb-5">
    <?php if(empty($items)): ?>
        <div class="empty-section">ยังไม่มีข้อมูล<?= htmlspecialchars($section_label) ?>ในขณะนี้</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4 justify-content-center">
            <?php foreach($items as $item):
                $fileName = $item['file_name'] ?? '';
                $linkUrl = $item['link_url'] ?? '';
                $targetUrl = !empty($linkUrl) ? $linkUrl : (!empty($fileName) ? 'uploads/' . $fileName : '#');
                $actionLabel = sectionActionLabel($fileName, $linkUrl);
            ?>
                <div class="col">
                    <article class="card section-card h-100">
                        <div class="section-preview">
                            <?= renderSectionPreview($fileName) ?>
                        </div>
                        <div class="card-body text-center">
                            <div class="section-card-title"><?= htmlspecialchars($item['title']) ?></div>
                            <?php if(!empty($item['department_name'])): ?>
                                <div class="section-card-meta">หน่วยงาน: <?= htmlspecialchars($item['department_name']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($item['content'])): ?>
                                <p class="text-muted mb-3" style="min-height: 60px;"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                            <?php endif; ?>
                            <?php if($targetUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-section w-100"><?= htmlspecialchars($actionLabel) ?></a>
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
