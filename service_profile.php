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

function parseServiceProfileFiles($fileData) {
    if (empty($fileData)) return [];

    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) {
        return array_values(array_filter($decoded, fn($fileName) => is_string($fileName) && $fileName !== ''));
    }

    return is_string($fileData) ? [$fileData] : [];
}

function renderServiceProfilePreview($fileName) {
    if (empty($fileName)) {
        return '<div class="service-profile-file-box"><i class="bi bi-file-earmark-text"></i></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Images: ใช้ img-trigger เปิด modal เหมือน department.php
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<img src="' . $path . '" class="img-trigger" alt="preview" data-src="' . $path . '" style="cursor:pointer; width:100%; height:220px; object-fit:cover;">';
    }

    // Video
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
        return '<video class="w-100" style="height:220px; object-fit:contain; background:#000;" controls><source src="' . $path . '"></video>';
    }

    // PDF: ใช้ pdf-trigger เปิด modal เหมือน department.php
    if ($ext === 'pdf') {
        return '<div class="pdf-container pdf-trigger" data-src="' . $path . '" style="height:220px; background:#2d2d2d; overflow:hidden; cursor:pointer; display:flex; align-items:center; justify-content:center; border-radius:8px; padding:0 45px;">
                    <div style="width:100%; height:90%; background:#ffffff; box-shadow:0 4px 12px rgba(0,0,0,0.3); overflow:hidden; border-radius:4px;">
                        <iframe src="' . $path . '#page=1&toolbar=0&navpanes=0&scrollbar=0&view=FitH" style="width:calc(100% + 20px); height:calc(100% + 20px); border:none; pointer-events:none;" scrolling="no"></iframe>
                    </div>
                </div>';
    }

    // DOCX/DOC — ใช้ SVG icon Word จริง
    if (in_array($ext, ['doc', 'docx'])) {
        return '<a href="' . $path . '" class="service-profile-preview-action" download>
                    <div class="service-profile-file-box">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="64" height="64">
                            <path fill="#2196F3" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z"/>
                            <path fill="#FFFFFF" d="M25 15.001H39V17H25zM25 19H39V21H25zM25 23.001H39V25.001H25zM25 27H39V29H25zM25 31H39V33.001H25z"/>
                            <path fill="#0D47A1" d="M27 42L6 38 6 10 27 6z"/>
                            <path fill="#FFFFFF" d="M21.167,31.012H18.45l-1.802-8.988c-0.098-0.477-0.155-0.996-0.174-1.560h-0.049c-0.016,0.448-0.094,0.976-0.232,1.560l-2.001,8.988h-2.827l-2.569-13h2.537l1.261,7.810c0.055,0.368,0.095,0.830,0.127,1.375h0.038c0.023-0.424,0.089-0.895,0.197-1.424l1.994-7.761h2.429l1.705,7.888c0.058,0.275,0.100,0.723,0.127,1.349h0.042c0.018-0.498,0.059-0.951,0.127-1.349l1.219-7.888h2.514L21.167,31.012z"/>
                        </svg>
                        <div class="file-ext">DOCX</div>
                    </div>
                </a>';
    }

    // Other files
    $display = strlen($safeFile) > 60 ? substr($safeFile, 0, 40) . '...' . substr($safeFile, -10) : $safeFile;
    return '<a href="' . $path . '" class="service-profile-preview-action" download>
                <div class="service-profile-file-box"><i class="bi bi-file-earmark-text"></i><span>' . $display . '</span></div>
            </a>';
}

function renderServiceProfilePreviews($files) {
    if (empty($files)) {
        return renderServiceProfilePreview('');
    }

    if (count($files) === 1) {
        return renderServiceProfilePreview($files[0]);
    }

    $html = '<div class="service-profile-file-gallery" style="position:relative;">';
    foreach ($files as $i => $fileName) {
        $html .= '<div class="service-profile-file-gallery-item" data-index="' . $i . '">' . renderServiceProfilePreview($fileName) . '</div>';
    }
    $html .= '</div>';

    return $html;
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="service_profile.css">
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
                $fileNames = parseServiceProfileFiles($item['file_name'] ?? '');
                $hasMultiple = count($fileNames) > 1;
            ?>
                <div class="col-md-6 col-xl-4">
                    <article class="card service-profile-card h-100">
                        <div style="position:relative;">
                            <div class="service-profile-preview">
                                <?= renderServiceProfilePreviews($fileNames) ?>
                            </div>
                            <?php if ($hasMultiple): ?>
                                <button class="dept-gallery-nav prev" onclick="spGalleryMove(this,-1)"><i class="bi bi-chevron-left"></i></button>
                                <button class="dept-gallery-nav next" onclick="spGalleryMove(this,1)"><i class="bi bi-chevron-right"></i></button>
                                <div class="dept-gallery-dots">
                                    <?php for ($d = 0; $d < count($fileNames); $d++): ?>
                                        <div class="dept-gallery-dot <?= $d === 0 ? 'active' : '' ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body text-center">
                            <div class="service-profile-card-title"><?= htmlspecialchars($item['title']) ?></div>
                            <?php if(!empty($item['department_name'])): ?>
                                <div class="service-profile-meta">หน่วยงาน: <?= htmlspecialchars($item['department_name']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($item['content'])): ?>
                                <p class="mb-3 text-muted small"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal fullscreen preview -->
<div class="modal fade modal-fullscreen-preview" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered m-0">
        <div class="modal-content border-0 rounded-0 position-relative" style="background:#000; width:100vw; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;">

            <div class="position-absolute" style="top:20px; right:25px; z-index:1060; display:flex; gap:24px; align-items:center;">
                <a href="" id="modal-image-download" download class="text-white d-none" style="font-size:24px; text-decoration:none;" title="ดาวน์โหลดรูปภาพนี้">
                    <i class="bi bi-download"></i>
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1) brightness(2); font-size:22px; opacity:0.85; margin:0; padding:0;"></button>
            </div>

            <button type="button" id="modal-prev-btn" class="btn position-absolute d-none" style="left:30px; top:50%; transform:translateY(-50%); z-index:1055; border:none; background:rgba(45,45,45,0.7); color:white; width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="w-100 h-100 d-flex align-items-center justify-content-center p-4">
                <img id="fullscreen-image" src="" class="img-fluid d-none" style="max-height:85vh; max-width:90%; object-fit:contain; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
                <iframe id="fullscreen-pdf" src="" class="d-none" style="width:100%; height:85vh; border:none; border-radius:4px;"></iframe>
            </div>

            <button type="button" id="modal-next-btn" class="btn position-absolute d-none" style="right:30px; top:50%; transform:translateY(-50%); z-index:1055; border:none; background:rgba(45,45,45,0.7); color:white; width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Gallery navigation
function spGalleryMove(btn, dir) {
    const wrapper = btn.closest('[style*="position:relative"]');
    const gallery = wrapper.querySelector('.service-profile-file-gallery');
    const dots = wrapper.querySelectorAll('.dept-gallery-dot');
    if (!gallery) return;

    const itemWidth = gallery.offsetWidth;
    const total = gallery.querySelectorAll('.service-profile-file-gallery-item').length;
    const current = Math.round(gallery.scrollLeft / itemWidth);
    const next = Math.max(0, Math.min(total - 1, current + dir));

    gallery.scrollTo({ left: next * itemWidth, behavior: 'smooth' });
    dots.forEach((d, i) => d.classList.toggle('active', i === next));
}

document.querySelectorAll('.service-profile-file-gallery').forEach(gallery => {
    gallery.addEventListener('scroll', () => {
        const wrapper = gallery.closest('[style*="position:relative"]');
        if (!wrapper) return;
        const dots = wrapper.querySelectorAll('.dept-gallery-dot');
        const itemWidth = gallery.offsetWidth;
        const current = Math.round(gallery.scrollLeft / itemWidth);
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }, { passive: true });
});

// Fullscreen preview
document.addEventListener("DOMContentLoaded", function () {
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const modalImg = document.getElementById('fullscreen-image');
    const modalPdf = document.getElementById('fullscreen-pdf');
    const downloadBtn = document.getElementById('modal-image-download');
    const prevBtn = document.getElementById('modal-prev-btn');
    const nextBtn = document.getElementById('modal-next-btn');

    let currentIdx = 0;
    const imageElements = Array.from(document.querySelectorAll('.img-trigger'));

    function openPhotoViewer(index) {
        currentIdx = index;
        modalPdf.classList.add('d-none');
        modalPdf.setAttribute('src', '');
        modalImg.classList.remove('d-none');
        downloadBtn.classList.remove('d-none');

        if (imageElements.length > 1) {
            if (prevBtn) prevBtn.classList.remove('d-none');
            if (nextBtn) nextBtn.classList.remove('d-none');
        } else {
            if (prevBtn) prevBtn.classList.add('d-none');
            if (nextBtn) nextBtn.classList.add('d-none');
        }

        updateViewerImage();
        previewModal.show();
    }

    function updateViewerImage() {
        if (imageElements.length === 0) return;
        const src = imageElements[currentIdx].getAttribute('data-src');
        modalImg.setAttribute('src', src);
        downloadBtn.setAttribute('href', src);
    }

    function nextImage() {
        if (imageElements.length <= 1 || modalImg.classList.contains('d-none')) return;
        currentIdx = (currentIdx + 1) % imageElements.length;
        updateViewerImage();
    }

    function prevImage() {
        if (imageElements.length <= 1 || modalImg.classList.contains('d-none')) return;
        currentIdx = (currentIdx - 1 + imageElements.length) % imageElements.length;
        updateViewerImage();
    }

    imageElements.forEach((img, index) => {
        img.addEventListener('click', () => openPhotoViewer(index));
    });

    if (nextBtn) nextBtn.addEventListener('click', nextImage);
    if (prevBtn) prevBtn.addEventListener('click', prevImage);

    document.addEventListener('keydown', function (e) {
        if (previewModalEl.classList.contains('show')) {
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
        }
    });

    document.querySelectorAll('.pdf-trigger').forEach(div => {
        div.addEventListener('click', function () {
            const src = this.getAttribute('data-src');
            modalImg.classList.add('d-none');
            modalImg.setAttribute('src', '');
            downloadBtn.classList.add('d-none');
            if (prevBtn) prevBtn.classList.add('d-none');
            if (nextBtn) nextBtn.classList.add('d-none');
            modalPdf.setAttribute('src', src);
            modalPdf.classList.remove('d-none');
            previewModal.show();
        });
    });

    previewModalEl.addEventListener('hidden.bs.modal', function () {
        modalPdf.setAttribute('src', '');
        downloadBtn.classList.add('d-none');
        if (prevBtn) prevBtn.classList.add('d-none');
        if (nextBtn) nextBtn.classList.add('d-none');
    });
});
</script>
</body>
</html>