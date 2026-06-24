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

function parseSectionFiles($fileData) {
    if (empty($fileData)) return [];

    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) {
        return array_values(array_filter($decoded, fn($fileName) => is_string($fileName) && $fileName !== ''));
    }

    return is_string($fileData) ? [$fileData] : [];
}

function renderSectionPreview($fileName) {
    if (empty($fileName)) {
        return '<div class="section-file-box"><i class="bi bi-file-earmark-text"></i></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // PDF: preview card คลิกแล้วเปิด modal fullscreen
    if ($ext === 'pdf') {
        return '<div class="section-preview-action section-pdf-preview pdf-trigger" data-src="' . $path . '" style="cursor:pointer;">
            <div style="width:100%;height:100%;overflow:hidden;position:relative;">
                <iframe src="' . $path . '#page=1&toolbar=0&navpanes=0&scrollbar=0&view=FitH" style="position:absolute;top:0;left:0;width:calc(100% + 20px);height:calc(100% + 20px);border:0;pointer-events:none;" scrolling="no"></iframe>
            </div>
        </div>';
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<a href="' . $path . '" target="_blank" rel="noopener noreferrer" class="section-preview-action"><img src="' . $path . '" class="section-card-img" alt="' . $safeFile . '"></a>';
    }

    if (in_array($ext, ['doc', 'docx'])) {
        return '<a href="' . $path . '" class="section-preview-action" download>
            <div class="section-file-box">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="64" height="64">
                    <path fill="#2196F3" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z"/>
                    <path fill="#FFFFFF" d="M25 15.001H39V17H25zM25 19H39V21H25zM25 23.001H39V25.001H25zM25 27H39V29H25zM25 31H39V33.001H25z"/>
                    <path fill="#0D47A1" d="M27 42L6 38 6 10 27 6z"/>
                    <path fill="#FFFFFF" d="M21.167,31.012H18.45l-1.802-8.988c-0.098-0.477-0.155-0.996-0.174-1.560h-0.049c-0.016,0.448-0.094,0.976-0.232,1.560l-2.001,8.988h-2.827l-2.569-13h2.537l1.261,7.810c0.055,0.368,0.095,0.830,0.127,1.375h0.038c0.023-0.424,0.089-0.895,0.197-1.424l1.994-7.761h2.429l1.705,7.888c0.058,0.275,0.100,0.723,0.127,1.349h0.042c0.018-0.498,0.059-0.951,0.127-1.349l1.219-7.888h2.514L21.167,31.012z"/>
                </svg>
                <span>' . strtoupper($ext) . '</span>
            </div>
        </a>';
    }

    $display = strlen($safeFile) > 60 ? substr($safeFile, 0, 40) . '...' . substr($safeFile, -10) : $safeFile;
    return '<a href="' . $path . '" class="section-preview-action" download><div class="section-file-box"><i class="bi bi-file-earmark-text"></i><span>' . $display . '</span></div></a>';
}

function renderSectionPreviews($files) {
    if (empty($files)) {
        return renderSectionPreview('');
    }

    if (count($files) === 1) {
        return '<div class="section-file-gallery-item" style="width:100%;height:100%;">' . renderSectionPreview($files[0]) . '</div>';
    }

    $html = '<div class="section-file-gallery">';
    foreach ($files as $i => $fileName) {
        $html .= '<div class="section-file-gallery-item" data-index="' . $i . '">' . renderSectionPreview($fileName) . '</div>';
    }
    $html .= '</div>';

    return $html;
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
            --dept-orange: #e78551;
            --dept-orange-dark: #b86c2e;
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
        .section-preview {
            width: 100%;
            height: 220px;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* ซ่อน scrollbar ของ gallery */
        .section-file-gallery {
            display: flex;
            width: 100%;
            height: 220px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            background: #f3f4f6;
            scrollbar-width: none;       /* Firefox */
            -ms-overflow-style: none;    /* IE/Edge */
        }
        .section-file-gallery::-webkit-scrollbar {
            display: none;               /* Chrome/Safari */
        }

        .section-file-gallery-item {
            flex: 0 0 100%;
            min-width: 100%;
            height: 220px;
            scroll-snap-align: start;
            overflow: hidden;
        }
        .section-preview-action,
        .section-pdf-preview {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            color: inherit;
            text-decoration: none;
            background: #fdfdfd;
            overflow: hidden;
        }

        /* ซ่อน scrollbar ของ iframe PDF */
        .section-pdf-preview iframe {
            overflow: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .section-pdf-preview iframe::-webkit-scrollbar {
            display: none;
        }

        .section-card-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .section-file-box {
            width: 100%;
            height: 100%;
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
            font-size: 64px;
            color: var(--dept-orange);
        }
        .section-file-box span {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
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

        /* ปุ่มเลื่อน gallery */
        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.85);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
            color: var(--dept-orange);
            font-size: 16px;
            padding: 0;
        }
        .gallery-nav.prev { left: 6px; }
        .gallery-nav.next { right: 6px; }
        .gallery-nav:hover { background: #fff; }
        .gallery-dots {
            position: absolute;
            bottom: 6px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
            z-index: 10;
        }
        .gallery-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            border: 1px solid var(--dept-orange);
            cursor: pointer;
            transition: background 0.2s;
        }
        .gallery-dot.active {
            background: var(--dept-orange);
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
                $fileNames = parseSectionFiles($item['file_name'] ?? '');
                $hasMultiple = count($fileNames) > 1;
            ?>
                <div class="col">
                    <article class="card section-card h-100">
                        <div class="section-preview" <?= $hasMultiple ? 'data-gallery="true"' : '' ?>>
                            <?= renderSectionPreviews($fileNames) ?>
                            <?php if ($hasMultiple): ?>
                                <button class="gallery-nav prev" onclick="galleryMove(this, -1)"><i class="bi bi-chevron-left"></i></button>
                                <button class="gallery-nav next" onclick="galleryMove(this, 1)"><i class="bi bi-chevron-right"></i></button>
                                <div class="gallery-dots">
                                    <?php for ($d = 0; $d < count($fileNames); $d++): ?>
                                        <div class="gallery-dot <?= $d === 0 ? 'active' : '' ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body text-center">
                            <div class="section-card-title"><?= htmlspecialchars($item['title']) ?></div>
                            <?php if(!empty($item['department_name'])): ?>
                                <div class="section-card-meta">หน่วยงาน: <?= htmlspecialchars($item['department_name']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($item['content'])): ?>
                                <p class="text-muted mb-3" style="min-height: 60px;"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal fullscreen preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered m-0">
        <div class="modal-content border-0 rounded-0 position-relative" style="background:#000; width:100vw; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;">
            <div class="position-absolute" style="top:20px; right:25px; z-index:1060; display:flex; gap:24px; align-items:center;">
                <a href="" id="modal-pdf-download" download class="text-white d-none" style="font-size:24px; text-decoration:none;" title="ดาวน์โหลด PDF">
                    <i class="bi bi-download"></i>
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1) brightness(2); font-size:22px; opacity:0.85; margin:0; padding:0;"></button>
            </div>
            <div class="w-100 h-100 d-flex align-items-center justify-content-center p-2">
                <iframe id="fullscreen-pdf" src="" style="width:100%; height:90vh; border:none; border-radius:4px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// PDF modal
document.addEventListener("DOMContentLoaded", function() {
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const modalPdf = document.getElementById('fullscreen-pdf');
    const downloadBtn = document.getElementById('modal-pdf-download');

    document.querySelectorAll('.pdf-trigger').forEach(el => {
        el.addEventListener('click', function() {
            const src = this.getAttribute('data-src');
            modalPdf.setAttribute('src', src);
            downloadBtn.setAttribute('href', src);
            downloadBtn.classList.remove('d-none');
            previewModal.show();
        });
    });

    previewModalEl.addEventListener('hidden.bs.modal', function() {
        modalPdf.setAttribute('src', '');
    });
});

function galleryMove(btn, dir) {
    const preview = btn.closest('.section-preview');
    const gallery = preview.querySelector('.section-file-gallery');
    const dots = preview.querySelectorAll('.gallery-dot');
    if (!gallery) return;

    const itemWidth = gallery.offsetWidth;
    const total = gallery.querySelectorAll('.section-file-gallery-item').length;
    const current = Math.round(gallery.scrollLeft / itemWidth);
    const next = Math.max(0, Math.min(total - 1, current + dir));

    gallery.scrollTo({ left: next * itemWidth, behavior: 'smooth' });

    dots.forEach((d, i) => d.classList.toggle('active', i === next));
}

// sync dots on scroll
document.querySelectorAll('.section-file-gallery').forEach(gallery => {
    gallery.addEventListener('scroll', () => {
        const preview = gallery.closest('.section-preview');
        const dots = preview.querySelectorAll('.gallery-dot');
        const itemWidth = gallery.offsetWidth;
        const current = Math.round(gallery.scrollLeft / itemWidth);
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }, { passive: true });
});
</script>
</body>
</html>