<?php
require_once 'connect.php';

// 🛑 เอา 'personnel' ออกจากโครงสร้างแสดงผลหลักของหน้านี้อย่างถาวร
$department_content_sections = [
    'knowledge' => 'ข่าวประชาสัมพันธ์และเกร็ดความรู้'
];

$conn->exec("CREATE TABLE IF NOT EXISTS department_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    file_name VARCHAR(255) NULL,
    link_url VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department_section (department_id, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$dept_id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
$stmt->execute([':id' => $dept_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    http_response_code(404);
    die('ไม่พบข้อมูลกลุ่มงาน');
}

$stmt = $conn->prepare("SELECT * FROM department_contents WHERE department_id = :department_id ORDER BY section ASC, sort_order ASC, id DESC");
$stmt->execute([':department_id' => $dept_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียมอาร์เรย์จัดกลุ่มข้อมูลแยกตามคีย์ที่เหลืออยู่
$items_by_section = [];
foreach ($department_content_sections as $key => $label) {
    $items_by_section[$key] = [];
}

// 🔄 ระบบจับคู่ Mapping รองรับการทำงานของหมวดหมู่อื่นๆ ตามปกติ
foreach ($items as $item) {
    $db_section = trim($item['section']);
    $target_key = null;

    foreach ($department_content_sections as $eng_key => $th_label) {
        if ($db_section === $eng_key || $db_section === $th_label) {
            $target_key = $eng_key;
            break;
        }
    }

    if ($target_key !== null) {
        $items_by_section[$target_key][] = $item;
    } else {
        $items_by_section[$db_section][] = $item;
    }
}

function renderDepartmentFile($fileName) {
    if (empty($fileName)) return '';

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<div class="image-preview-box">
                    <img src="' . $path . '" class="card-img-top dept-card-img img-trigger" alt="preview" data-src="' . $path . '" style="cursor: pointer;">
                </div>';
    }

    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
        return '<video class="dept-card-img w-100" controls><source src="' . $path . '"></video>';
    }

    if ($ext === 'pdf') {
        return '<div class="pdf-container pdf-trigger" data-src="' . $path . '" style="height:220px; background:transparent; overflow:hidden; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <div style="width:64%; height:90%; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; border-radius:4px;">
                        <iframe src="' . $path . '#page=1&toolbar=0&navpanes=0" style="width:100%; height:100%; border:none; pointer-events:none;"></iframe>
                    </div>
                </div>';
    }

    if (in_array($ext, ['doc', 'docx'])) {
        $label = strtoupper($ext);
        return '<div class="dept-file-box"><i class="bi bi-file-earmark-text" style="font-size:48px;color:var(--dept-orange)"></i><div class="file-ext" style="font-weight:700;margin-top:6px;">' . $label . '</div></div>';
    }

    return '<div class="dept-file-box"><i class="bi bi-file-earmark-text"></i><span>' . $safeFile . '</span></div>';
}

function contentActionLabel($fileName, $linkUrl) {
    if (!empty($linkUrl)) return 'เปิดลิงก์';
    $ext = strtolower(pathinfo($fileName ?? '', PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) return 'เล่นวิดีโอ';
    if (in_array($ext, ['doc', 'docx'])) return 'ดาวน์โหลดไฟล์';
    if ($ext === 'pdf') return 'เปิดไฟล์';
    return 'เปิดไฟล์';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($department['name']) ?> - โรงพยาบาลปากช่องนานา</title>
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
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #fff;
            font-weight: 600;
        }
        .navbar-custom .nav-link:hover {
            color: #ffe1c4;
        }
        .navbar-toggler {
            border-color: rgba(255,255,255,.65);
        }
        .navbar-toggler-icon {
            filter: invert(1) grayscale(1) brightness(2);
        }
        .hero-section {
            background: var(--dept-orange);
            color: #fff;
            padding: 80px 16px 90px;
            border-radius: 0 0 56px 56px;
            text-align: center;
            margin-bottom: 48px;
        }
        .hero-section h1 {
            font-size: clamp(34px, 5vw, 64px);
            line-height: 1.15;
            font-weight: 700;
        }
        .section-title {
            color: var(--dept-orange);
            font-weight: 700;
            text-align: center;
            margin: 34px 0 24px;
        }
        .dept-card {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
            min-height: 100%;
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
            background: #fff;
        }
        
        .image-preview-box {
            width: 100%;
            height: 220px;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            border-bottom: none;
        }
        .image-preview-box img.dept-card-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dept-card-img {
            height: 220px;
            object-fit: cover;
            background: #f1f1f1;
        }
        video.dept-card-img {
            height: auto;
            aspect-ratio: 16 / 9;
            object-fit: contain;
            background: #000;
        }
        .dept-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.25rem;
        }
        .dept-file-box {
            height: 140px;
            min-height: 120px;
            background: #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #6b7280;
            padding: 12px;
            text-align: center;
            overflow: hidden;
        }
        .dept-file-box i {
            font-size: 42px;
            color: var(--dept-orange);
        }
        /* Show department unit below title */
        .section-card-meta { color: #6b7280; font-size: 0.95rem; margin-bottom: 8px; }
        /* If needed show short label for DOCX inside preview box */
        .dept-file-box .file-ext { font-size: 18px; color: #374151; }
        .dept-file-box .file-ext::before { content: ''; }
        
        .btn-outline-custom {
            color: var(--dept-orange);
            border-color: var(--dept-orange);
            font-weight: 600;
        }
        .btn-outline-custom:hover {
            background: var(--dept-orange);
            color: #fff;
        }
        .empty-section {
            background: #fff;
            border: 1px dashed #e1b083;
            border-radius: 8px;
            color: #8a6a50;
            padding: 20px;
            text-align: center;
        }
        @media (max-width: 767.98px) {
            .hero-section {
                padding: 58px 16px 68px;
                border-radius: 0 0 34px 34px;
            }
            .image-preview-box {
                height: 220px;
            }
            .dept-card-img,
            .dept-file-box {
                height: 180px;
            }
        }
        .modal-fullscreen-preview .modal-content {
            background: rgba(30, 30, 30, 0.96);
            border: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .modal-fullscreen-preview .btn-close {
            filter: invert(1) brightness(2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i> หน้าแรก</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#departmentNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="departmentNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#section-knowledge"><?= htmlspecialchars($department_content_sections['knowledge']) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="personnel.php?id=<?= $dept_id ?>">ทำเนียบบุคลากร</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="service_profile.php?dept_id=<?= $dept_id ?>">Service Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="indicator.php?dept_id=<?= $dept_id ?>">ตัวชี้วัด</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="structure.php?dept_id=<?= $dept_id ?>">โครงสร้างการบริหารงาน</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="service.php?dept_id=<?= $dept_id ?>">การให้บริการต่างๆ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="academic.php?dept_id=<?= $dept_id ?>">ผลงานวิจัย</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="wi.php?dept_id=<?= $dept_id ?>">WI</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-section">
    <h1><?= htmlspecialchars($department['name']) ?></h1>
    <p class="lead mb-0">โรงพยาบาลปากช่องนานา</p>
</section>

<main class="container mb-5">
    <?php foreach($department_content_sections as $sectionKey => $sectionLabel): ?>
        <section id="section-<?= htmlspecialchars($sectionKey) ?>" class="mb-5">
            <h2 class="section-title"><i class="bi bi-megaphone-fill"></i> <?= htmlspecialchars($sectionLabel) ?></h2>
            
            <?php if(empty($items_by_section[$sectionKey])): ?>
                <div class="empty-section">ยังไม่มีข้อมูลในหมวดนี้</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 justify-content-center">
                    <?php foreach($items_by_section[$sectionKey] as $item): 
                        $fileName = $item['file_name'] ?? '';
                        $linkUrl = $item['link_url'] ?? '';
                        $targetUrl = !empty($linkUrl) ? $linkUrl : (!empty($fileName) ? 'uploads/' . $fileName : '#');
                        
                        $file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        $is_video = in_array($file_ext, ['mp4', 'webm', 'ogg']);
                        $is_pdf = ($file_ext === 'pdf');
                    ?>
                        <div class="col">
                            <article class="card dept-card">
                                <?= renderDepartmentFile($fileName) ?>
                                <div class="card-body text-center">
                                    <h5 class="card-title fw-bold"><?= htmlspecialchars($item['title']) ?></h5>
                                    <div class="section-card-meta">หน่วยงาน: <?= htmlspecialchars($department['name']) ?></div>
                                    <?php if(!empty($item['content'])): ?>
                                        <p class="card-text small text-muted"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                                    <?php endif; ?>

                                    <?php if($targetUrl !== '#'): ?>
                                        <?php if($is_pdf): ?>
                                            <button type="button" class="btn btn-sm btn-outline-custom btn-pdf-open" data-src="<?= htmlspecialchars($targetUrl) ?>"><?= htmlspecialchars(contentActionLabel($fileName, $linkUrl)) ?></button>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($targetUrl) ?>" class="btn btn-sm btn-outline-custom" <?php if(in_array($file_ext, ['doc','docx'])) echo 'download'; ?> target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars(contentActionLabel($fileName, $linkUrl)) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</main>

<div class="modal fade modal-fullscreen-preview" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content text-white">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-1 text-center">
                <img id="fullscreen-image" src="" class="img-fluid d-none" style="max-height: 82vh; object-fit: contain;">
                <iframe id="fullscreen-pdf" src="" class="d-none" style="width: 100%; height: 82vh; border: none; border-radius: 4px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        const modalImg = document.getElementById('fullscreen-image');
        const modalPdf = document.getElementById('fullscreen-pdf');

        const imgTriggers = document.querySelectorAll('.img-trigger');
        imgTriggers.forEach(img => {
            img.addEventListener('click', function() {
                const src = this.getAttribute('data-src');
                modalPdf.classList.add('d-none');
                modalPdf.setAttribute('src', '');
                
                modalImg.setAttribute('src', src);
                modalImg.classList.remove('d-none');
                previewModal.show();
            });
        });

        const pdfTriggers = document.querySelectorAll('.pdf-trigger');
        pdfTriggers.forEach(div => {
            div.addEventListener('click', function() {
                const src = this.getAttribute('data-src');
                modalImg.classList.add('d-none');
                modalImg.setAttribute('src', '');
                
                modalPdf.setAttribute('src', src);
                modalPdf.classList.remove('d-none');
                previewModal.show();
            });
        });

        const pdfOpenButtons = document.querySelectorAll('.btn-pdf-open');
        pdfOpenButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const src = this.getAttribute('data-src');
                modalImg.classList.add('d-none');
                modalImg.setAttribute('src', '');
                modalPdf.setAttribute('src', src);
                modalPdf.classList.remove('d-none');
                previewModal.show();
            });
        });
    });
</script>
</body>
</html>
