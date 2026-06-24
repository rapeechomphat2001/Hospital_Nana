<?php
require_once 'connect.php';

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

$stmt = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
$all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กิจกรรมทั้งหมด - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
       :root {
    --hosp-orange: #fd873e;
    --hosp-orange-dark: #e06b2d;
    --hosp-text-dark: #495057;
}

        body {
            background-color: #f8f4f0;
            font-family: 'Sarabun', sans-serif, Arial;
        }

        /* ===== TOP BAR ===== */
        .top-bar {
            background-color: var(--hosp-orange-dark);
            color: #fff;
            font-size: 13px;
            padding: 7px 0;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            background-color: var(--hosp-orange);
            color: #fff;
            padding: 18px 0;
            border-bottom: 4px solid var(--hosp-orange-dark);
        }
        .page-header h1 {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .btn-back {
            background-color: #fff;
            color: var(--hosp-orange-dark);
            border: none;
            font-weight: 700;
            font-size: 14px;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            background-color: #ffe8d6;
            color: var(--hosp-orange-dark);
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--hosp-orange-dark);
            border-left: 5px solid var(--hosp-orange);
            padding-left: 12px;
            margin-bottom: 24px;
        }

        /* ===== EVENT CARD ===== */
        .event-card-wrap {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(243,112,33,0.10);
            transition: transform 0.22s, box-shadow 0.22s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .event-card-wrap:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(243,112,33,0.18);
        }
        .event-card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            border-bottom: 3px solid var(--hosp-orange);
        }
        .event-card-body {
            padding: 14px 16px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .event-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #2d2d2d;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .event-card-date {
            font-size: 12px;
            color: var(--hosp-orange);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 0;
            color: #aaa;
        }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }

        /* ===== FOOTER ===== */
        .main-footer {
            background-color: var(--hosp-orange-dark);
            color: #fff;
            padding: 28px 0 14px;
            font-size: 13px;
            margin-top: 48px;
        }
        .footer-copyright {
            background-color: #e06b2d;
            padding: 10px 0;
            font-size: 12px;
            text-align: center;
            color: #fff;
            margin-top: 16px;
        }

        @media (max-width: 575.98px) {
            .page-header h1 { font-size: 17px; }
            .event-card-img { height: 160px; }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
    </div>
</div>

<!-- Page Header -->
<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="bi bi-calendar3 me-2"></i>กิจกรรมทั้งหมด</h1>
        <a href="index.php" class="btn-back">
            <i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าแรก
        </a>
    </div>
</div>

<!-- Content -->
<div class="container my-5">
    <div class="section-title">
        <i class="bi bi-calendar-event me-1"></i> รายการกิจกรรม
    </div>

    <?php if(empty($all_events)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>ขณะนี้ยังไม่มีข้อมูลกิจกรรม</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php foreach($all_events as $event):
                $img = 'uploads/' . htmlspecialchars($event['image_name']);
            ?>
            <div class="col">
                <div class="event-card-wrap">
                    <img src="<?= $img ?>"
                         class="event-card-img"
                         alt="กิจกรรม"
                         onerror="this.src='https://placehold.co/400x200?text=No+Image'">
                    <div class="event-card-body">
                        <div class="event-card-title"><?= htmlspecialchars($event['title']) ?></div>
                        <div class="event-card-date">
                            <i class="bi bi-calendar-event"></i>
                            <?= dateToThaiFull($event['event_date']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
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
</body>
</html>