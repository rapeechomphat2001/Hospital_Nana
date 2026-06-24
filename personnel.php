<?php
require_once 'connect.php';

$dept_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
$stmt->execute([':id' => $dept_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    http_response_code(404);
    die('ไม่พบข้อมูลกลุ่มงาน');
}

$stmt = $conn->prepare("SELECT * FROM department_contents 
                        WHERE department_id = :department_id 
                        AND (section = 'personnel' OR section = 'ทำเนียบบุคลากร') 
                        ORDER BY sort_order ASC, id DESC");
$stmt->execute([':department_id' => $dept_id]);
$personnel_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderPersonnelImage($fileName) {
    if (empty($fileName)) {
        return '<div class="personnel-image-box"><div class="no-avatar-box"><i class="bi bi-person-bounding-box"></i></div></div>';
    }

    $safeFile = htmlspecialchars($fileName);
    $path = 'uploads/' . $safeFile;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '<div class="personnel-image-box">
                    <img src="' . $path . '" class="personnel-card-img img-trigger" alt="บุคลากร" data-src="' . $path . '" style="cursor:pointer;">
                </div>';
    }

    return '<div class="personnel-image-box"><div class="no-avatar-box"><i class="bi bi-person-bounding-box"></i></div></div>';
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="personnel.css">
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
                        <?= renderPersonnelImage($fileName) ?>
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                            <?php if(!empty($item['content'])): ?>
                                <p class="card-text text-muted small mb-0"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
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
                <a href="" id="modal-image-download" download class="text-white" style="font-size:24px; text-decoration:none;" title="ดาวน์โหลดรูปภาพ">
                    <i class="bi bi-download"></i>
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1) brightness(2); font-size:22px; opacity:0.85; margin:0; padding:0;"></button>
            </div>
            <button type="button" id="modal-prev-btn" class="btn position-absolute d-none" style="left:30px; top:50%; transform:translateY(-50%); z-index:1055; border:none; background:rgba(45,45,45,0.7); color:white; width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="w-100 h-100 d-flex align-items-center justify-content-center p-4">
                <img id="fullscreen-image" src="" class="img-fluid" style="max-height:85vh; max-width:90%; object-fit:contain; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
            </div>
            <button type="button" id="modal-next-btn" class="btn position-absolute d-none" style="right:30px; top:50%; transform:translateY(-50%); z-index:1055; border:none; background:rgba(45,45,45,0.7); color:white; width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const modalImg = document.getElementById('fullscreen-image');
    const downloadBtn = document.getElementById('modal-image-download');
    const prevBtn = document.getElementById('modal-prev-btn');
    const nextBtn = document.getElementById('modal-next-btn');

    let currentIdx = 0;
    const imageElements = Array.from(document.querySelectorAll('.img-trigger'));

    function openViewer(index) {
        currentIdx = index;
        if (imageElements.length > 1) {
            prevBtn.classList.remove('d-none');
            nextBtn.classList.remove('d-none');
        }
        updateImage();
        previewModal.show();
    }

    function updateImage() {
        const src = imageElements[currentIdx].getAttribute('data-src');
        modalImg.setAttribute('src', src);
        downloadBtn.setAttribute('href', src);
    }

    imageElements.forEach((img, i) => img.addEventListener('click', () => openViewer(i)));

    nextBtn.addEventListener('click', () => {
        currentIdx = (currentIdx + 1) % imageElements.length;
        updateImage();
    });
    prevBtn.addEventListener('click', () => {
        currentIdx = (currentIdx - 1 + imageElements.length) % imageElements.length;
        updateImage();
    });

    document.addEventListener('keydown', e => {
        if (!previewModalEl.classList.contains('show')) return;
        if (e.key === 'ArrowRight') nextBtn.click();
        if (e.key === 'ArrowLeft') prevBtn.click();
    });

    previewModalEl.addEventListener('hidden.bs.modal', () => {
        modalImg.setAttribute('src', '');
    });
});
</script>
</body>
</html>