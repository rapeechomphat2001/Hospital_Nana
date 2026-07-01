<?php
// 🔗 1. เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล MySQL
require_once 'connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_admin_logged_in'])) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect={$redirectUrl}");
    exit;
}

$active_tab = $_GET['tab'] ?? 'news';

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ.
function dateToThaiText($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุวันที่';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    $d  = date('j', $time);
    $m  = date('n', $time);
    $y  = date('Y', $time) + 543;
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return "$d {$months[$m]} $y";
}

// ฟังก์ชันแปลงชื่อไฟล์เป็น array
function parseFileNames($fileData) {
    if (empty($fileData)) return [];
    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) return $decoded;
    if (is_string($fileData) && !empty($fileData)) return [$fileData];
    return [];
}

$department_content_sections = [
    'knowledge'       => 'ข่าวประชาสัมพันธ์ / เกร็ดความรู้',
    'structure'       => 'โครงสร้างการบริหารงาน',
    'personnel'       => 'ทำเนียบบุคลากร',
    'service'         => 'การให้บริการต่างๆ',
    'service_profile' => 'Service Profile',
    'indicator'       => 'ตัวชี้วัด',
    'academic'        => 'ผลงานวิจัย / วิชาการ',
    'wi'              => 'WI / SP'
];

// สร้างตาราง department_contents ถ้ายังไม่มี
$conn->exec("CREATE TABLE IF NOT EXISTS department_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    file_name TEXT NULL,
    link_url VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department_section (department_id, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $conn->exec("ALTER TABLE department_contents MODIFY file_name TEXT NULL");
} catch (Exception $e) { }

// สร้างตาราง banners ถ้ายังไม่มี
$conn->exec("CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT NULL,
    image_name VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ---------- helpers ----------
function uploadAdminFile($fieldName, $prefix, $oldFile = '') {
    if (empty($_FILES[$fieldName]['name'])) return $oldFile;
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES[$fieldName]['name']));
    $fileName = time() . '_' . $prefix . '_' . $safeName;
    move_uploaded_file($_FILES[$fieldName]['tmp_name'], 'uploads/' . $fileName);
    return $fileName;
}

function uploadMultipleAdminFiles($fieldName, $prefix, $oldFiles = '') {
    if (empty($_FILES[$fieldName]['name'][0])) return $oldFiles;
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    $existingFiles = [];
    if (!empty($oldFiles)) {
        $decoded = json_decode($oldFiles, true);
        if (is_array($decoded)) $existingFiles = $decoded;
        elseif (is_string($oldFiles) && $oldFiles !== 'default.jpg') $existingFiles = [$oldFiles];
    }

    $uploadedFiles = !empty($_POST['keep_old_files']) ? $existingFiles : [];

    if (is_array($_FILES[$fieldName]['name'])) {
        foreach ($_FILES[$fieldName]['name'] as $index => $fileName) {
            if (!empty($fileName)) {
                $safeName    = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fileName));
                $newFileName = time() . '_' . $index . '_' . $prefix . '_' . $safeName;
                if (move_uploaded_file($_FILES[$fieldName]['tmp_name'][$index], 'uploads/' . $newFileName)) {
                    $uploadedFiles[] = $newFileName;
                }
            }
        }
    }

    if (count($uploadedFiles) === 0)  return $oldFiles;
    if (count($uploadedFiles) === 1)  return $uploadedFiles[0];
    return json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE);
}

// ==================== PROCESS ====================

// [1] ข่าวประชาสัมพันธ์ (news)
if (isset($_POST['action_news'])) {
    $file_name    = uploadMultipleAdminFiles('image', 'news', $_POST['old_image'] ?? 'default.jpg');
    $is_new_status = isset($_POST['is_new']) ? 1 : 0;
    $created_at   = !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d');
    $link_url     = !empty($_POST['link_url']) ? $_POST['link_url'] : null;

    if ($_POST['action_news'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO news (title, content, created_at, image_name, is_new, link_url) VALUES (:title, :content, :created_at, :image_name, :is_new, :link_url)");
        $stmt->execute([':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':created_at' => $created_at, ':image_name' => $file_name, ':is_new' => $is_new_status, ':link_url' => $link_url]);
    } elseif ($_POST['action_news'] == 'update') {
        $stmt = $conn->prepare("UPDATE news SET title=:title, content=:content, created_at=:created_at, image_name=:image_name, is_new=:is_new, link_url=:link_url WHERE id=:id");
        $stmt->execute([':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':created_at' => $created_at, ':image_name' => $file_name, ':is_new' => $is_new_status, ':link_url' => $link_url, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=news"); exit;
}
if (isset($_GET['del_news'])) {
    $conn->prepare("DELETE FROM news WHERE id=:id")->execute([':id' => $_GET['del_news']]);
    header("Location: admin.php?tab=news"); exit;
}

// [2] หน่วยงาน (departments)
if (isset($_POST['action_dept'])) {
    $link_url = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    if ($_POST['action_dept'] == 'create') {
        $conn->prepare("INSERT INTO departments (name, link_url) VALUES (:name, :link_url)")->execute([':name' => $_POST['name'], ':link_url' => $link_url]);
    } elseif ($_POST['action_dept'] == 'update') {
        $conn->prepare("UPDATE departments SET name=:name, link_url=:link_url WHERE id=:id")->execute([':name' => $_POST['name'], ':link_url' => $link_url, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=departments"); exit;
}
if (isset($_GET['del_dept'])) {
    $conn->prepare("DELETE FROM department_contents WHERE department_id=:id")->execute([':id' => $_GET['del_dept']]);
    $conn->prepare("DELETE FROM departments WHERE id=:id")->execute([':id' => $_GET['del_dept']]);
    header("Location: admin.php?tab=departments"); exit;
}

// [3] ข้อมูลรายกลุ่มงาน (dept_contents)
if (isset($_POST['action_dept_content'])) {
    $department_id = (int)($_POST['department_id'] ?? 0);
    $section       = $_POST['section'] ?? 'knowledge';
    $sort_order    = max(1, (int)($_POST['sort_order'] ?? 1));
    $link_url      = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    $file_name     = uploadMultipleAdminFiles('content_file', 'dept_content', $_POST['old_file'] ?? '');

    if ($_POST['action_dept_content'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO department_contents (department_id, section, title, content, file_name, link_url, sort_order) VALUES (:department_id, :section, :title, :content, :file_name, :link_url, :sort_order)");
        $stmt->execute([':department_id' => $department_id, ':section' => $section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order]);
    } elseif ($_POST['action_dept_content'] == 'update') {
        $stmt = $conn->prepare("UPDATE department_contents SET department_id=:department_id, section=:section, title=:title, content=:content, file_name=:file_name, link_url=:link_url, sort_order=:sort_order WHERE id=:id");
        $stmt->execute([':department_id' => $department_id, ':section' => $section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=dept_contents&dept_id=" . $department_id); exit;
}
if (isset($_GET['del_dept_content'])) {
    $department_id = (int)($_GET['dept_id'] ?? 0);
    $conn->prepare("DELETE FROM department_contents WHERE id=:id")->execute([':id' => $_GET['del_dept_content']]);
    header("Location: admin.php?tab=dept_contents&dept_id=" . $department_id); exit;
}

// [4] Banner / Slider
if (isset($_POST['action_banner'])) {
    $file_name  = uploadAdminFile('banner_image', 'banner', $_POST['old_image'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = max(1, (int)($_POST['sort_order'] ?? 1));
    $link_url   = !empty($_POST['link_url']) ? $_POST['link_url'] : null;

    if ($_POST['action_banner'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO banners (title, subtitle, image_name, link_url, sort_order, is_active) VALUES (:title, :subtitle, :image_name, :link_url, :sort_order, :is_active)");
        $stmt->execute([':title' => $_POST['title'], ':subtitle' => $_POST['subtitle'] ?? '', ':image_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':is_active' => $is_active]);
    } elseif ($_POST['action_banner'] == 'update') {
        $stmt = $conn->prepare("UPDATE banners SET title=:title, subtitle=:subtitle, image_name=:image_name, link_url=:link_url, sort_order=:sort_order, is_active=:is_active WHERE id=:id");
        $stmt->execute([':title' => $_POST['title'], ':subtitle' => $_POST['subtitle'] ?? '', ':image_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':is_active' => $is_active, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=banners"); exit;
}
if (isset($_GET['del_banner'])) {
    $conn->prepare("DELETE FROM banners WHERE id=:id")->execute([':id' => $_GET['del_banner']]);
    header("Location: admin.php?tab=banners"); exit;
}

// ==================== FETCH DATA ====================
$news_items         = [];
$dept_items         = [];
$dept_content_items = [];
$banner_items       = [];

$all_depts        = $conn->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$selected_dept_id = (int)($_GET['dept_id'] ?? ($all_depts[0]['id'] ?? 0));

if ($active_tab == 'news') {
    $news_items = $conn->query("SELECT * FROM news ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'departments') {
    $dept_items = $conn->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'dept_contents' && $selected_dept_id > 0) {
    $stmt = $conn->prepare("SELECT dc.*, d.name AS department_name FROM department_contents dc INNER JOIN departments d ON d.id = dc.department_id WHERE dc.department_id = :department_id ORDER BY dc.section ASC, dc.sort_order ASC, dc.id DESC");
    $stmt->execute([':department_id' => $selected_dept_id]);
    $dept_content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'banners') {
    $banner_items = $conn->query("SELECT * FROM banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <h2 class="mb-0 text-hospital fw-bold">ระบบจัดการข้อมูลเว็บไซต์ (Admin Dashboard)</h2>
        <a href="logout.php" class="btn btn-outline-secondary">ออกจากระบบ</a>
    </div>

    <ul class="nav nav-tabs" id="hospitalTabs">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'news' ? 'active' : '' ?>" href="?tab=news">
                <i class="bi bi-megaphone-fill fs-5 icon-news"></i> ข่าวประชาสัมพันธ์
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'departments' ? 'active' : '' ?>" href="?tab=departments">
                <i class="bi bi-building-fill fs-5 icon-dept"></i> หอผู้ป่วย / หน่วยงาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'dept_contents' ? 'active' : '' ?>" href="?tab=dept_contents">
                <i class="bi bi-folder2-open fs-5 icon-dept"></i> ข้อมูลรายกลุ่มงาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'banners' ? 'active' : '' ?>" href="?tab=banners">
                <i class="bi bi-image fs-5"></i> Banner / Slider
            </a>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 border rounded-bottom shadow-sm">

        <?php if($active_tab == 'news'): ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold">จัดการข่าวประชาสัมพันธ์</h5>
            <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="admin-form-container">
                <input type="hidden" name="action_news" value="create">
                <div class="row g-2 mb-2">
                    <div class="col-md-5"><input type="text" name="title" class="form-control" placeholder="หัวข้อข่าว" required></div>
                    <div class="col-md-3"><input type="text" name="created_at" id="news_date_picker" class="form-control bg-white" placeholder="เลือกวันที่ พ.ศ." required></div>
                    <div class="col-md-4"><input type="file" name="image[]" class="form-control" accept="image/*,application/pdf" multiple></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12"><input type="url" name="link_url" class="form-control" placeholder="ลิงก์หน้าข่าวสารเพิ่มเติมภายนอก"></div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-10">
                        <textarea name="content" class="form-control" rows="2" placeholder="กรอกเนื้อหาข่าวสารแบบละเอียด..."></textarea>
                    </div>
                    <div class="col-md-2 text-end d-flex flex-column gap-2 justify-content-end align-items-end">
                        <div class="form-check form-switch p-2 border rounded bg-white w-100 text-center">
                            <input class="form-check-input ms-1" type="checkbox" name="is_new" id="news_is_new" value="1" checked>
                            <label class="form-check-label me-3" for="news_is_new">ติดป้าย "ใหม่"</label>
                        </div>
                        <button type="submit" class="btn btn-hospital-orange w-100">+ เพิ่มข่าวสาร</button>
                    </div>
                </div>
            </form>

            <table class="table table-striped align-middle mt-4">
                <thead><tr><th>ไฟล์/รูป</th><th>วันที่</th><th>หัวข้อข่าว/เนื้อหา/ลิงก์</th><th>ป้ายสถานะ</th><th>จัดการ</th></tr></thead>
                <tbody>
                    <?php foreach($news_items as $row):
                        $news_content = $row['content'] ?? '';
                        $is_new_badge = isset($row['is_new']) ? (int)$row['is_new'] : 0;
                        $news_link    = $row['link_url'] ?? '';
                        $news_files   = parseFileNames($row['image_name']);
                    ?>
                    <tr>
                        <td width="12%">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <?php foreach ($news_files as $file):
                                    $is_pdf = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
                                ?>
                                    <?php if($is_pdf): ?>
                                        <a href="uploads/<?= htmlspecialchars($file) ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($file) ?>" width="50" class="img-thumbnail" onerror="this.src='https://placehold.co/50x50?text=No+Image'" style="height:auto;">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if(empty($news_files)): ?><span class="text-muted small">ไม่มีไฟล์</span><?php endif; ?>
                            </div>
                        </td>
                        <td width="15%"><?= dateToThaiText($row['created_at']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <div class="text-muted small text-truncate text-preview-short"><?= htmlspecialchars($news_content) ?></div>
                            <?php if(!empty($news_link)): ?>
                                <div class="small mt-1"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($news_link) ?>" target="_blank" class="text-decoration-none text-truncate d-inline-block text-preview-link"><?= htmlspecialchars($news_link) ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td width="12%"><?php if($is_new_badge === 1): ?><span class="badge-orange-style">ใหม่</span><?php endif; ?></td>
                        <td width="15%">
                            <button class="btn btn-outline-edit-style btn-sm me-1" onclick="editNews(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['title'])) ?>', '<?= addslashes(htmlspecialchars($news_content)) ?>', '<?= $row['created_at'] ?>', '<?= addslashes(htmlspecialchars($row['image_name'])) ?>', <?= $is_new_badge ?>, '<?= addslashes(htmlspecialchars($news_link)) ?>')">แก้ไข</button>
                            <a href="admin.php?tab=news&del_news=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'departments'): ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold">จัดการหอผู้ป่วย / หน่วยงาน</h5>
            <form action="admin.php?tab=departments" method="POST" class="admin-form-container">
                <input type="hidden" name="action_dept" value="create">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5"><input type="text" name="name" class="form-control" placeholder="ชื่อหอผู้ป่วย/หน่วยงาน" required></div>
                    <div class="col-md-5"><input type="url" name="link_url" class="form-control" placeholder="ลิงก์หน้าเว็บประจำแผนก"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-hospital-orange w-100">+ เพิ่มหน่วยงาน</button></div>
                </div>
            </form>

            <div style="overflow-x: auto;">
            <table class="table table-striped align-middle mt-4" style="min-width: 600px;">
                <thead><tr><th>ชื่อหน่วยงาน / หอผู้ป่วย</th><th>ลิงก์ประจำแผนก</th><th style="white-space: nowrap; width: 140px;">จัดการ</th></tr></thead>
                <tbody>
                    <?php foreach($dept_items as $row):
                        $dept_link = $row['link_url'] ?? '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php if(!empty($dept_link)): ?>
                                <i class="bi bi-link-45deg text-primary"></i>
                                <a href="<?= htmlspecialchars($dept_link) ?>" target="_blank" class="text-decoration-none" title="<?= htmlspecialchars($dept_link) ?>"><?= htmlspecialchars($dept_link) ?></a>
                            <?php else: ?>
                                <span class="text-muted small">ไม่ได้ระบุลิงก์</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap; width: 140px;">
                            <button class="btn btn-outline-edit-style btn-sm me-1" onclick="editDept(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['name'])) ?>', '<?= addslashes(htmlspecialchars($dept_link)) ?>')">แก้ไข</button>
                            <a href="admin.php?tab=departments&del_dept=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'dept_contents'): ?>
        <div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="text-hospital mb-0 fw-bold">จัดการข้อมูลรายกลุ่มงาน</h5>
                <?php if($selected_dept_id > 0): ?>
                    <a href="department.php?id=<?= $selected_dept_id ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-arrow-up-right"></i> เปิดหน้ากลุ่มงาน
                    </a>
                <?php endif; ?>
            </div>

            <?php if(empty($all_depts)): ?>
                <div class="alert alert-warning">กรุณาเพิ่มข้อมูลหอผู้ป่วย / หน่วยงานก่อน</div>
            <?php else: ?>
                <form method="GET" class="row g-2 mb-3">
                    <input type="hidden" name="tab" value="dept_contents">
                    <div class="col-md-8">
                        <select name="dept_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach($all_depts as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (int)$dept['id'] === $selected_dept_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-hospital-orange w-100" type="submit">เลือกกลุ่มงาน</button>
                    </div>
                </form>

                <form id="deptContentForm" action="admin.php?tab=dept_contents&dept_id=<?= $selected_dept_id ?>" method="POST" enctype="multipart/form-data" class="admin-form-container">
                    <input type="hidden" name="action_dept_content" id="dept_content_action" value="create">
                    <input type="hidden" name="id" id="dept_content_id">
                    <input type="hidden" name="old_file" id="dept_content_old_file">
                    
                    <input type="hidden" name="department_id" id="dept_content_department_id" value="<?= $selected_dept_id ?>">

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">หมวดข้อมูล</label>
                            <select name="section" id="dept_content_section" class="form-select" required>
                                <?php foreach($department_content_sections as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ลำดับแสดงผล</label>
                            <input type="number" name="sort_order" id="dept_content_sort_order" class="form-control" value="1" min="1" step="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">หัวข้อ</label>
                            <input type="text" name="title" id="dept_content_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ลิงก์ภายนอก (ถ้ามี)</label>
                            <input type="url" name="link_url" id="dept_content_link_url" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">รายละเอียด</label>
                        <textarea name="content" id="dept_content_content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ไฟล์แนบ / รูปภาพ / วิดีโอ</label>
                            <input type="file" name="content_file[]" class="form-control" accept="image/*,application/pdf,video/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx" multiple>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" id="dept_content_submit" class="btn btn-hospital-orange flex-fill">+ เพิ่มข้อมูล</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetDeptContentForm()">ล้างฟอร์ม</button>
                        </div>
                    </div>
                </form>

                <div class="admin-table-scroll dept-content-scroll mt-4">
                <table class="table table-striped align-middle dept-content-table mb-0">
                    <colgroup>
                        <col class="col-section">
                        <col class="col-title">
                        <col class="col-file">
                        <col class="col-order">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr><th>หมวด</th><th>หัวข้อ / รายละเอียด</th><th>ไฟล์ / ลิงก์</th><th>ลำดับ</th><th>จัดการ</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($dept_content_items)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลรายกลุ่มงานนี้</td></tr>
                        <?php endif; ?>
                        <?php foreach($dept_content_items as $row):
                            $content_file  = $row['file_name'] ?? '';
                            $content_files = parseFileNames($content_file);
                            $content_link  = $row['link_url'] ?? '';
                            $section_label = $department_content_sections[$row['section']] ?? $row['section'];
                            $edit_payload  = [
                                'id'            => (int)$row['id'],
                                'department_id' => (int)$row['department_id'],
                                'section'       => $row['section'],
                                'title'         => $row['title'],
                                'content'       => $row['content'] ?? '',
                                'file_name'     => $content_file,
                                'link_url'      => $content_link,
                                'sort_order'    => (int)$row['sort_order']
                            ];
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary dept-content-section-badge"><?= htmlspecialchars($section_label) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($row['title']) ?></strong>
                                <div class="small text-muted text-preview-medium"><?= htmlspecialchars($row['content'] ?? '') ?></div>
                            </td>
                            <td class="dept-content-file-cell">
                                <?php foreach($content_files as $file): ?>
                                   <div class="mb-1"><a href="uploads/<?= htmlspecialchars($file) ?>" target="_blank"><i class="bi bi-paperclip text-hospital"></i> <?= htmlspecialchars($file) ?></a></div>
                                <?php endforeach; ?>
                                <?php if(!empty($content_link)): ?>
                                    <div class="admin-file-link"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($content_link) ?>" target="_blank"><?= htmlspecialchars($content_link) ?></a></div>
                                <?php endif; ?>
                                <?php if(empty($content_files) && empty($content_link)): ?>
                                    <span class="text-muted small">ไม่มีไฟล์/ลิงก์</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$row['sort_order'] ?></td>
                            <td width="16%">
                                <button type="button" class="btn btn-outline-edit-style btn-sm me-1" onclick='editDeptContent(<?= json_encode($edit_payload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>แก้ไข</button>
                                <a href="admin.php?tab=dept_contents&dept_id=<?= $selected_dept_id ?>&del_dept_content=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ต้องการลบข้อมูลนี้หรือไม่?')">ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'banners'): ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold">จัดการ Banner / Slider หน้าแรก</h5>
            <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Banner จะแสดงเป็น Slider บนหน้าแรกของเว็บไซต์ เรียงตามลำดับที่กำหนด</p>

            <form action="admin.php?tab=banners" method="POST" enctype="multipart/form-data" class="admin-form-container">
                <input type="hidden" name="action_banner" value="create">
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">หัวข้อ Banner <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="หัวข้อที่แสดงบน Slider" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ลิงก์ปุ่ม "อ่านเพิ่มเติม"</label>
                        <input type="url" name="link_url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">ลำดับแสดง</label>
                        <input type="number" name="sort_order" class="form-control" value="1" min="1">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-7">
                        <label class="form-label fw-bold">คำบรรยาย (Subtitle)</label>
                        <input type="text" name="subtitle" class="form-control" placeholder="คำบรรยายใต้หัวข้อ Banner">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">รูปภาพ Banner</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                        <div class="form-text">แนะนำขนาด 1920×600 px หรืออัตราส่วน 16:5</div>
                    </div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="form-check form-switch p-2 border rounded bg-white">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" id="banner_is_active" value="1" checked>
                            <label class="form-check-label ms-2" for="banner_is_active">เปิดแสดง Banner นี้บนหน้าแรก</label>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn btn-hospital-orange w-100">+ เพิ่ม Banner</button>
                    </div>
                </div>
            </form>

            <table class="table table-striped align-middle mt-4">
                <thead>
                    <tr>
                        <th width="12%">รูปภาพ</th>
                        <th>หัวข้อ / คำบรรยาย / ลิงก์</th>
                        <th width="8%">ลำดับ</th>
                        <th width="9%">สถานะ</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($banner_items)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มี Banner — กรุณาเพิ่มด้านบน</td></tr>
                    <?php endif; ?>
                    <?php foreach($banner_items as $row):
                        $img = $row['image_name'] ?? '';
                    ?>
                    <tr>
                        <td>
                            <?php if(!empty($img)): ?>
                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="banner-thumb" onerror="this.src='https://placehold.co/100x60?text=No+Img'">
                            <?php else: ?>
                                <span class="badge bg-secondary">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <?php if(!empty($row['subtitle'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars($row['subtitle']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($row['link_url'])): ?>
                                <div class="small mt-1"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($row['link_url']) ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($row['link_url']) ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= (int)$row['sort_order'] ?></td>
                        <td>
                            <?php if((int)$row['is_active'] === 1): ?>
                                <span class="badge bg-success">แสดง</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">ซ่อน</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-outline-edit-style btn-sm me-1"
                                onclick="editBanner(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['title'])) ?>', '<?= addslashes(htmlspecialchars($row['subtitle'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($row['image_name'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($row['link_url'] ?? '')) ?>', <?= (int)$row['sort_order'] ?>, <?= (int)$row['is_active'] ?>)">
                                แก้ไข
                            </button>
                            <a href="admin.php?tab=banners&del_banner=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ต้องการลบ Banner นี้หรือไม่?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div></div><div class="modal fade" id="modalNews" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไขข่าวประชาสัมพันธ์</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_news" value="update">
        <input type="hidden" name="id" id="edit_news_id">
        <input type="hidden" name="old_image" id="edit_news_old_image">
        <div class="mb-3"><label class="form-label fw-bold">หัวข้อข่าว</label><input type="text" name="title" id="edit_news_title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-bold">วันที่ของข่าว</label><input type="text" name="created_at" id="edit_news_date" class="form-control bg-white" required></div>
        <div class="mb-3"><label class="form-label fw-bold">ลิงก์หน้าข่าวสารเพิ่มเติมภายนอก</label><input type="url" name="link_url" id="edit_news_link" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-bold">เนื้อหาข่าวแบบละเอียด</label><textarea name="content" id="edit_news_content" class="form-control" rows="4"></textarea></div>
        <div class="mb-3">
            <div class="form-check form-switch p-2 border rounded bg-light form-switch-indented">
                <input class="form-check-input" type="checkbox" name="is_new" id="edit_news_is_new" value="1">
                <label class="form-check-label ms-2" for="edit_news_is_new"><strong>เปิดแสดงป้าย "ใหม่"</strong></label>
            </div>
        </div>
        <div class="mb-3"><label class="form-label fw-bold">เปลี่ยนไฟล์ (เว้นว่างเพื่อใช้ไฟล์เดิม)</label><input type="file" name="image[]" class="form-control" accept="image/*,application/pdf" multiple></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalDept" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="admin.php?tab=departments" method="POST" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไขหน่วยงาน</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_dept" value="update">
        <input type="hidden" name="id" id="edit_dept_id">
        <div class="mb-3"><label class="form-label fw-bold">ชื่อหน่วยงาน / หอผู้ป่วย</label><input type="text" name="name" id="edit_dept_name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-bold">ลิงก์หน้าเว็บประจำแผนก</label><input type="url" name="link_url" id="edit_dept_link" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalBanner" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="admin.php?tab=banners" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไข Banner / Slider</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_banner" value="update">
        <input type="hidden" name="id" id="edit_banner_id">
        <input type="hidden" name="old_image" id="edit_banner_old_image">
        <div class="mb-3">
            <label class="form-label fw-bold">หัวข้อ Banner</label>
            <input type="text" name="title" id="edit_banner_title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">คำบรรยาย (Subtitle)</label>
            <input type="text" name="subtitle" id="edit_banner_subtitle" class="form-control" placeholder="คำบรรยายใต้หัวข้อ">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ลิงก์ปุ่ม "อ่านเพิ่มเติม"</label>
            <input type="url" name="link_url" id="edit_banner_link" class="form-control">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">ลำดับแสดง</label>
                <input type="number" name="sort_order" id="edit_banner_sort" class="form-control" min="1">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold">รูปภาพใหม่ (เว้นว่างเพื่อใช้รูปเดิม)</label>
                <input type="file" name="banner_image" class="form-control" accept="image/*">
            </div>
        </div>
        <div id="edit_banner_preview_wrap" class="mb-3" style="display:none;">
            <label class="form-label fw-bold">รูปปัจจุบัน</label><br>
            <img id="edit_banner_preview" src="" class="banner-thumb" style="width:180px; height:auto;">
        </div>
        <div class="form-check form-switch p-2 border rounded bg-light form-switch-indented">
            <input class="form-check-input ms-1" type="checkbox" name="is_active" id="edit_banner_active" value="1">
            <label class="form-check-label ms-2" for="edit_banner_active"><strong>เปิดแสดง Banner นี้บนหน้าแรก</strong></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

<script>
const flatpickrConfig = {
    locale: "th",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d/m/Y",
    defaultDate: "<?= date('Y-m-d') ?>",
    onUpdate:     function(s, d, i) { formatYearToThai(i); },
    onReady:      function(s, d, i) {
        formatYearToThai(i);
        i.calendarContainer.addEventListener('click', () => { setTimeout(() => formatYearToThai(i), 1); });
    },
    onMonthChange: function(s, d, i) { setTimeout(() => formatYearToThai(i), 1); },
    onYearChange:  function(s, d, i) { setTimeout(() => formatYearToThai(i), 1); }
};

function formatYearToThai(instance) {
    const thYear   = parseInt(instance.currentYear) + 543;
    const yearInput = instance.calendarContainer.querySelector('.numInput.flatpickr-year');
    if (yearInput) yearInput.value = thYear;
    if (instance.altInput) {
        const val = instance.input.value;
        if (val) {
            const parts    = val.split('-');
            const thYearInput = parseInt(parts[0]) + 543;
            instance.altInput.value = parts[2] + '/' + parts[1] + '/' + thYearInput;
        }
    }
}

<?php if($active_tab == 'news'): ?>
    const mainPicker      = flatpickr("#news_date_picker", flatpickrConfig);
    const modalNewsPicker = flatpickr("#edit_news_date",   flatpickrConfig);
<?php endif; ?>

// ----- Edit functions -----
function editNews(id, title, content, date, img, isNew, link) {
    document.getElementById('edit_news_id').value        = id;
    document.getElementById('edit_news_title').value     = title;
    document.getElementById('edit_news_content').value   = content;
    document.getElementById('edit_news_old_image').value = img;
    document.getElementById('edit_news_is_new').checked  = (parseInt(isNew) === 1);
    document.getElementById('edit_news_link').value      = link;
    if (typeof modalNewsPicker !== 'undefined') { modalNewsPicker.setDate(date); formatYearToThai(modalNewsPicker); }
    new bootstrap.Modal(document.getElementById('modalNews')).show();
}

// ล็อกระบบให้ department_id ซิงค์และดึงข้อมูลมาแก้ไขได้ตามปกติ
function editDept(id, name, link) {
    document.getElementById('edit_dept_id').value   = id;
    document.getElementById('edit_dept_name').value = name;
    document.getElementById('edit_dept_link').value = link;
    new bootstrap.Modal(document.getElementById('modalDept')).show();
}

function editDeptContent(item) {
    document.getElementById('dept_content_action').value        = 'update';
    document.getElementById('dept_content_id').value            = item.id || '';
    document.getElementById('dept_content_old_file').value      = item.file_name || '';
    document.getElementById('dept_content_department_id').value = item.department_id || '';
    document.getElementById('dept_content_section').value       = item.section || 'knowledge';
    document.getElementById('dept_content_sort_order').value    = Math.max(1, parseInt(item.sort_order || 1, 10));
    document.getElementById('dept_content_title').value         = item.title || '';
    document.getElementById('dept_content_link_url').value      = item.link_url || '';
    document.getElementById('dept_content_content').value       = item.content || '';
    document.getElementById('dept_content_submit').textContent  = 'บันทึกการแก้ไข';
    document.getElementById('deptContentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetDeptContentForm() {
    const form = document.getElementById('deptContentForm');
    if (!form) return;
    
    // เก็บค่า department_id ปัจจุบันไว้ก่อนล้างฟอร์ม
    const currentDeptId = document.getElementById('dept_content_department_id').value;
    
    form.reset();
    
    document.getElementById('dept_content_action').value   = 'create';
    document.getElementById('dept_content_id').value       = '';
    document.getElementById('dept_content_old_file').value = '';
    document.getElementById('dept_content_sort_order').value = '1';
    
    // คืนค่า department_id ประจำกลุ่มงานกลับเข้าไปตามเดิม
    document.getElementById('dept_content_department_id').value = currentDeptId;
    
    document.getElementById('dept_content_submit').textContent = '+ เพิ่มข้อมูล';
}

const deptContentForm = document.getElementById('deptContentForm');
if (deptContentForm) {
    deptContentForm.addEventListener('submit', function () {
        const sortInput = document.getElementById('dept_content_sort_order');
        sortInput.value = Math.max(1, parseInt(sortInput.value || 1, 10));
    });
}

// ----- Banner edit -----
function editBanner(id, title, subtitle, img, link, sort, isActive) {
    document.getElementById('edit_banner_id').value       = id;
    document.getElementById('edit_banner_title').value    = title;
    document.getElementById('edit_banner_subtitle').value = subtitle;
    document.getElementById('edit_banner_old_image').value = img;
    document.getElementById('edit_banner_link').value     = link;
    document.getElementById('edit_banner_sort').value     = sort;
    document.getElementById('edit_banner_active').checked = (parseInt(isActive) === 1);

    // แสดง preview รูปเดิม
    const previewWrap = document.getElementById('edit_banner_preview_wrap');
    const previewImg  = document.getElementById('edit_banner_preview');
    if (img) {
        previewImg.src        = 'uploads/' + img;
        previewWrap.style.display = 'block';
    } else {
        previewWrap.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('modalBanner')).show();
}
</script>
</body>
</html>