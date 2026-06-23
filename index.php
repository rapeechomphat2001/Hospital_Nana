<?php
// 🔗 เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล MySQL
require_once 'connect.php';

// ==================== [VISITOR COUNTER] ระบบสถิติผู้เข้าชมจริง ====================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$counter_file = 'visitor_counter.json';
$today_date = date('Y-m-d');

// ค่าเริ่มต้นกรณีไม่มีไฟล์บันทึก
$counter_data = [
    'last_date' => $today_date,
    'today' => 0,
    'week' => 0,
    'total' => 0,
    'week_start_date' => date('Y-m-d', strtotime('monday this week'))
];

// ถ้ามีไฟล์เดิมอยู่แล้ว ให้โหลดไฟล์มาอ่านค่า
if (file_exists($counter_file)) {
    $json_content = file_get_contents($counter_file);
    $loaded_data = json_decode($json_content, true);
    if ($loaded_data) {
        $counter_data = array_merge($counter_data, $loaded_data);
    }
}

// เช็กและรีเซ็ตค่าหากเปลี่ยนวันใหม่
if ($counter_data['last_date'] !== $today_date) {
    $counter_data['last_date'] = $today_date;
    $counter_data['today'] = 0;
}

// เช็กและรีเซ็ตค่ารายสัปดาห์หากข้ามไปสัปดาห์ใหม่
$current_week_start = date('Y-m-d', strtotime('monday this week'));
if ($counter_data['week_start_date'] !== $current_week_start) {
    $counter_data['week_start_date'] = $current_week_start;
    $counter_data['week'] = 0;
}

// นับเพิ่มยอดสถิติเฉพาะการเข้ามาดูครั้งแรกในเซสชันนั้นๆ (ป้องกันการกด Refresh รัวๆ)
if (!isset($_SESSION['has_visited_hospital'])) {
    $_SESSION['has_visited_hospital'] = true;
    $counter_data['today']++;
    $counter_data['week']++;
    $counter_data['total']++;
    
    // บันทึกตัวเลขใหม่ลงในไฟล์ json เก็บไว้
    file_put_contents($counter_file, json_encode($counter_data, JSON_PRETTY_PRINT));
}
// =================================================================================

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ. สไตล์ย่อ (เช่น 28 พ.ค. 69)
function dateToThaiShort($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุ';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    
    $d = date('j', $time);
    $m = date('n', $time);
    $y = (date('Y', $time) + 543) % 100;
    
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return "$d {$months[$m]} $y";
}

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ. แบบเต็มสำหรับกิจกรรม
function dateToThaiFull($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุวันที่';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    
    $d = date('j', $time);
    $m = date('n', $time);
    $y = date('Y', $time) + 543;
    
    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    return "$d {$months[$m]} $y";
}

// 🔄 QUERY ดึงข้อมูลจาก MySQL
$stmt_news = $conn->query("SELECT * FROM news ORDER BY id DESC");
$news_list = $stmt_news->fetchAll(PDO::FETCH_ASSOC);

$stmt_events = $conn->query("SELECT * FROM events ORDER BY id DESC LIMIT 4");
$event_list = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

$stmt_depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
$dept_list = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ควบคุมสไตล์ Dropdown Menu สีส้มให้ตรงธีมโรงพยาบาล */
        .dropdown-menu {
            background-color: var(--hosp-orange) !important;
            border: none !important;
            border-radius: 0 0 6px 6px !important;
            padding: 0 !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .dropdown-item {
            color: #ffffff !important;
            font-size: 14px;
            padding: 10px 20px !important;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 500;
        }
        .dropdown-item:last-child {
            border-bottom: none;
        }
        .dropdown-item:hover {
            background-color: var(--hosp-orange-dark) !important;
        }
        .dept-dropdown-menu {
            width: 460px;
            max-width: calc(100vw - 24px);
            padding: 18px 22px !important;
            column-count: 2;
            column-gap: 42px;
        }
        .dept-dropdown-menu li {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .dept-dropdown-menu .dropdown-item {
            border-bottom: none;
            padding: 9px 14px !important;
            white-space: nowrap;
        }
        @media (max-width: 575.98px) {
            .dept-dropdown-menu {
                width: calc(100vw - 16px);
                column-gap: 18px;
                padding: 12px 14px !important;
            }
            .dept-dropdown-menu .dropdown-item {
                font-size: 13px;
                padding: 8px 8px !important;
            }
        }
        @media (min-width: 992px) {
            .nav-item.dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }
        }

        /* ใส่สี Hover ให้กับกิจกรรม */
        .event-card:hover {
            background-color: #fdf5ee;
            transition: background-color 0.2s;
        }

        /* ใส่สี Hover ให้กับหอผู้ป่วย/หน่วยงาน */
        .dept-link-node:hover {
            background-color: #fdf5ee;
            transition: background-color 0.2s;
        }

        /* ใส่สี Hover ให้กับลิงก์ที่เกี่ยวข้อง */
        .link-grid-item:hover {
            background-color: #fdf5ee;
            transition: background-color 0.2s;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 | <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
        <div id="liveClock">กำลังโหลดเวลา...</div>
    </div>
</div>

<div class="header-banner">
    <div class="container d-flex align-items-center">
        <div class="bg-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 65px;">
            <i class="bi bi-hospital text-warning fs-3"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล</h2>
            <div class="small opacity-90">โรงพยาบาลปากช่องนานา | Nursing Department, Pakchong Nana Hospital</div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg main-nav p-0 sticky-top shadow-sm">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="เปิดเมนู">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="navbar-nav">
                <a class="nav-link active" href="#"><i class="bi bi-house-door-fill"></i> หน้าแรก</a>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        เกี่ยวกับกลุ่มงาน
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-eye-fill me-2"></i> วิสัยทัศน์ / พันธกิจ</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-diagram-3-fill me-2"></i> โครงสร้างองค์กร</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-people-fill me-2"></i> ทำเนียบผู้บริหาร</a></li>
                       <li><a class="dropdown-item" href="service_profile.php"><i class="bi bi-file-earmark-person-fill me-2"></i> Service Profile</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="deptDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        หอผู้ป่วย/หน่วยงาน
                    </a>
                    <ul class="dropdown-menu dept-dropdown-menu" aria-labelledby="deptDropdown">
                    <?php if(empty($dept_list)): ?>
                    <li><a class="dropdown-item" href="#">ไม่พบข้อมูล</a></li>
                    <?php else: ?>
                    <?php foreach($dept_list as $dept): 
                // 💡 ปรับปรุง: ตัดคำว่า "งาน" ออกเพื่อให้สั้นกระชับตามเรฟ
                $shortName = str_replace(['งาน', 'หน่วยงาน'], '', $dept['name']);
                     ?>
                    <li>
                    <?php $deptUrl = !empty($dept['link_url']) ? $dept['link_url'] : 'department.php?id=' . (int)$dept['id']; ?>
                    <a class="dropdown-item" href="<?= htmlspecialchars($deptUrl) ?>">
                        <?= htmlspecialchars(trim($shortName)) ?>
                    </a>
                    </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        งานบริหาร 
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="#">แผนงาน / โครงงาน</a></li>
                        <li><a class="dropdown-item" href="#">ระเบียบ / ข้อบังคับ</a></li>
                        <li><a class="dropdown-item" href="#">ข้อมูลงานบริหาร</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        งานวิชาการ 
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                        <li><a class="dropdown-item" href="#">คลังความรู้ / KM</a></li>
                        <li><a class="dropdown-item" href="#">แนวปฎิบัติการพยาบาล (CPG)</a></li>
                        <li><a class="dropdown-item" href="#">งานวิจัย / R2R</a></li>
                        <li><a class="dropdown-item" href="#">อบรม / สัมมนา</a></li>
                    </ul>
                    
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="qualityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        คุณภาพการพยาบาล 
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="qualityDropdown">
                        <li><a class="dropdown-item" href="#">ตัวชี้วัดคุณภาพ (KPI)</a></li>
                        <li><a class="dropdown-item" href="#">Patient Safety</a></li>
                        <li><a class="dropdown-item" href="#">IC / การป้องกันการติดเชื้อ</a></li>
                    </ul>
                </div>
            
                <a class="nav-link" href="#">ติดต่อเรา</a>
            </div>
        </div>
    </div>
</nav>

<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3"></button>
    </div>

    <div class="carousel-inner">
        <?php 
        for($i = 0; $i < 4; $i++): 
            $isActive = ($i == 0) ? 'active' : '';
            
            if(isset($event_list[$i])) {
                $slide_title = $event_list[$i]['title'];
                $slide_desc = $event_list[$i]['content'];
                $slide_img = 'uploads/' . $event_list[$i]['image_name'];
                $slide_link = 'uploads/' . $event_list[$i]['image_name'];
            } else {
                $slide_titles = [
                    "พัฒนาคุณภาพอย่างต่อเนื่อง",
                    "บริการด้วยใจ ปลอดภัยได้มาตรฐาน",
                    "ยกระดับการบริบาลผู้ป่วยอย่างอบอุ่น",
                    "ก้าวสู่ความเป็นเลิศด้านการพยาบาลชุมชน"
                ];
                $slide_title = $slide_titles[$i];
                $slide_desc = "กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา เพื่อสุขภาวะที่ดีของประชาชน";
                $nature_imgs = [
                    "https://images.unsplash.com/photo-1501854140801-50d01698950b?q=80&w=1920",
                    "https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?q=80&w=1920",
                    "https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=1920",
                    "https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=1920"
                ];
                $slide_img = $nature_imgs[$i];
                $slide_link = "#";
            }
        ?>
        <div class="carousel-item <?= $isActive ?>" style="background-image: url('<?= $slide_img ?>');">
            <div class="carousel-overlay"></div>
            <div class="carousel-caption-custom">
                <h1 class="fw-bold mb-2 text-truncate" style="font-size: 38px; letter-spacing: 0.5px;"><?= htmlspecialchars($slide_title) ?></h1>
                <p class="mb-4 text-truncate-2" style="font-size: 16px; opacity: 0.95;"><?= htmlspecialchars($slide_desc) ?></p>
                <a href="<?= htmlspecialchars($slide_link) ?>" target="_blank" class="btn-readmore">อ่านเพิ่มเติม</a>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<div class="vision-bar text-center">
    <div class="container">
        " วิสัยทัศน์: กลุ่มงานการพยาบาลที่มีคุณภาพ มาตรฐาน เป็นที่ไว้วางใจของผู้รับบริการ ภายใต้หลักธรรมาภิบาล เพื่อสุขภาวะที่ดีของประชาชน "
    </div>
</div>

<div class="stat-box-container text-center">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6 border-end">
                <div class="stat-number">422</div>
                <div class="stat-label"><i class="bi bi-person-fill"></i> บุคลากรพยาบาล (คน)</div>
            </div>
            <div class="col-md-3 col-6 border-end">
                <div class="stat-number">380</div>
                <div class="stat-label"><i class="bi bi-door-open-fill"></i> เตียงผู้ป่วย (เตียง)</div>
            </div>
            <div class="col-md-3 col-6 border-end">
                <div class="stat-number">18</div>
                <div class="stat-label"><i class="bi bi-building-fill"></i> หน่วยงาน</div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-number">24</div>
                <div class="stat-label"><i class="bi bi-clock-fill"></i> ชั่วโมง พร้อมให้บริการ</div>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <div class="row g-4">
        
        <div class="col-lg-4 col-md-6">
            <div class="block-header">
                <span><i class="bi bi-megaphone-fill"></i> ข่าวประชาสัมพันธ์</span>
                <a href="#">ดูทั้งหมด ›</a>
            </div>
            <div class="border p-2 bg-white" style="border-top: none; min-height: 380px;">
                <?php if(empty($news_list)): ?>
                    <div class="text-muted text-center py-4">ไม่มีข้อมูลข่าวประชาสัมพันธ์</div>
                <?php else: ?>
                    <?php foreach($news_list as $news): 
                        $news_file = !empty($news['image_name']) ? $news['image_name'] : 'default.jpg';
                    ?>
                    <div class="news-item-row d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <i class="bi bi-chevron-right small me-1" style="color: var(--hosp-orange) !important;"></i>
                            <a href="uploads/<?= htmlspecialchars($news_file) ?>" target="_blank" class="text-decoration-none align-middle">
                                <?= htmlspecialchars($news['title']) ?>
                            </a>
                            <?php if(isset($news['is_new']) && (int)$news['is_new'] === 1): ?>
                                <span class="badge-new ms-1 align-middle">ใหม่</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted small text-end flex-shrink-0 pt-1" style="width: 75px; font-weight: 500;"><?= dateToThaiShort($news['created_at']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5 col-md-6">
            <div class="block-header">
                <span><i class="bi bi-calendar3"></i> กิจกรรม</span>
                <a href="#">ดูทั้งหมด ›</a>
            </div>
            <div class="border px-3 bg-white" style="border-top: none; min-height: 380px;">
                <?php if(empty($event_list)): ?>
                    <div class="text-muted text-center py-4">ไม่มีข้อมูลกิจกรรม</div>
                <?php else: ?>
                    <?php foreach($event_list as $event): 
                        $event_file = !empty($event['image_name']) ? $event['image_name'] : 'default.jpg';
                    ?>
                    <div class="event-card">
                        <a href="uploads/<?= htmlspecialchars($event_file) ?>" target="_blank">
                            <img src="uploads/<?= htmlspecialchars($event_file) ?>" onerror="this.src='https://placehold.co/100x75?text=No+Image'">
                        </a>
                        <div class="flex-grow-1">
                            <div class="event-card-title">
                                <a href="uploads/<?= htmlspecialchars($event_file) ?>" target="_blank">
                                    <?= htmlspecialchars($event['title']) ?>
                                </a>
                            </div>
                            <span class="text-muted small d-block" style="font-size: 11px; margin-top: 4px;"><i class="bi bi-calendar-event"></i> <?= dateToThaiFull($event['event_date']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-3 col-md-12">
            <div class="block-header">
                <span><i class="bi bi-building-fill"></i> หอผู้ป่วย / หน่วยงาน</span>
            </div>
            <div class="dept-list-box" style="min-height: 380px;">
                <div class="row g-0">
                    <?php if(empty($dept_list)): ?>
                        <div class="text-muted text-center py-4 w-100">ไม่มีข้อมูลหน่วยงาน</div>
                    <?php else: ?>
                        <?php 
                        $half = ceil(count($dept_list) / 2);
                        $chunks = array_chunk($dept_list, $half);
                        
                        foreach($chunks as $chunk): ?>
                            <div class="col-6 px-2">
                                <?php foreach($chunk as $dept): ?>
                                    <?php $deptUrl = !empty($dept['link_url']) ? $dept['link_url'] : 'department.php?id=' . (int)$dept['id']; ?>
                                    <div class="dept-link-node text-truncate">
                                        <i class="bi bi-chevron-right small" style="color: var(--hosp-orange) !important;"></i>
                                        <a href="<?= htmlspecialchars($deptUrl) ?>"><?= htmlspecialchars($dept['name']) ?></a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8 col-md-12">
            <div class="block-header">
                <span><i class="bi bi-grid-fill"></i> ลิงก์ที่เกี่ยวข้อง</span>
            </div>
            <div class="link-grid-box mb-4">
                <div class="row g-0">
                    <div class="col-6 border-end">
                        <div class="link-grid-item"><i class="bi bi-shield-check text-warning me-2"></i><a href="#" target="_blank">สภาการพยาบาล</a></div>
                        <div class="link-grid-item"><i class="bi bi-mortarboard-fill text-warning me-2"></i><a href="#" target="_blank">ระบบสืบค้น CPG</a></div>
                        <div class="link-grid-item"><i class="bi bi-tablet-landscape text-warning me-2"></i><a href="#" target="_blank">ระบบ HIS</a></div>
                    </div>
                    <div class="col-6">
                        <div class="link-grid-item"><i class="bi bi-heart-pulse-fill text-danger me-2"></i><a href="#" target="_blank">กรมการแพทย์</a></div>
                        <div class="link-grid-item"><i class="bi bi-bar-chart-line-fill text-warning me-2"></i><a href="#" target="_blank">รายงาน KPI</a></div>
                        <div class="link-grid-item"><i class="bi bi-file-earmark-medical-fill text-warning me-2"></i><a href="#" target="_blank">คลังความรู้ KM</a></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-11">
            <div class="block-header"><span><i class="bi bi-share-fill"></i> ติดตามเรา</span></div>
            <div class="border p-3 bg-white text-center d-flex flex-wrap justify-content-center gap-3 mb-4" style="border-top:none;">
                <a href="#" class="btn btn-sm btn-outline-primary rounded"><i class="bi bi-facebook"></i> Facebook</a>
                <a href="#" class="btn btn-sm btn-outline-danger rounded"><i class="bi bi-youtube"></i> YouTube</a>
                <a href="#" class="btn btn-sm btn-outline-success rounded"><i class="bi bi-line"></i> Line OA</a>
            </div>

           <div class="block-header"><span><i class="bi bi-bar-chart-fill"></i> สถิติผู้เข้าชม</span></div>
<div class="border p-3 bg-white mb-4" style="border-top:none; font-size: 14px;">
    <div class="d-flex justify-content-between mb-1"><span>วันนี้</span><span class="fw-bold text-danger"><?= number_format($counter_data['today']) ?></span></div>
    
    <div class="d-flex justify-content-between mb-1"><span>สัปดาห์นี้</span><span class="fw-bold text-danger"><?= number_format($counter_data['week']) ?></span></div>
    <div class="d-flex justify-content-between"><span>รวมทั้งหมด</span><span class="fw-bold text-danger"><?= number_format($counter_data['total']) ?></span></div>
</div>
        </div>
    </div>
</div>

<footer class="main-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="bi bi-building"></i> กลุ่มงานการพยาบาล</h5>
                <p class="small opacity-80 mt-2">โรงพยาบาลปากช่องนานา<br>มุ่งมั่นในการพัฒนาคุณภาพการพยาบาล เพื่อผู้ป่วยและผู้รับบริการทุกคน</p>
            </div>
            <div class="col-md-4">
                <h5><i class="bi bi-geo-alt-fill"></i> ติดต่อเรา</h5>
                <ul class="small opacity-80">
                    <li><i class="bi bi-map"></i> 123 ถ.มิตรภาพ อ.ปากช่อง จ.นครราชสีมา 30130</li>
                    <li><i class="bi bi-telephone"></i> 044-316-999 ต่อ 4400</li>
                    <li><i class="bi bi-envelope"></i> nursing@pkc.go.th</li>
                    <li><i class="bi bi-clock"></i> เปิดให้บริการ 24 ชั่วโมง</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5><i class="bi bi-link-45deg"></i> ลิงก์ที่เกี่ยวข้อง</h5>
                <ul class="small opacity-80">
                    <li><i class="bi bi-chevron-right"></i> <a href="#">กระทรวงสาธารณสุข</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">สภาการพยาบาล</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">กรมการแพทย์</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">สรพ. (HA)</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright text-center mt-4">
        <div class="container">
            © 2569 กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา — สงวนลิขสิทธิ์ทั้งหมด
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function updateThaiLiveClock() {
    const now = new Date();
    const days = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    
    const dayName = days[now.getDay()];
    const dateNum = now.getDate();
    const monthName = months[now.getMonth()];
    const thaiYear = now.getFullYear() + 543;
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('liveClock').innerHTML = `วัน${dayName}ที่ ${dateNum} ${monthName} ${thaiYear} เวลา ${hours}:${minutes}:${seconds}`;
}
updateThaiLiveClock();
setInterval(updateThaiLiveClock, 1000);
</script>
</body>
</html>
