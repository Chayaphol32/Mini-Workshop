<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'จัดการสมาชิก';
$pathPrefix = '../';
$term = trim((string) ($_GET['q'] ?? ''));
$members = search_members($pdo, $term);
$totalPoints = array_sum(array_map(static fn (array $member): int => (int) $member['total_points'], $members));

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Admin Members</p>
        <h1>สมาชิกทั้งหมด</h1>
        <p class="muted">ค้นหาและจัดการข้อมูลสมาชิกทุกคนของร้าน</p>
    </div>
    <div class="actions">
        <a class="button" href="<?= e(app_url('admin/member_create.php')) ?>">+ เพิ่มสมาชิก</a>
        <a class="button button-muted" href="<?= e(app_url('admin/index.php')) ?>">กลับ Dashboard</a>
    </div>
</section>

<section class="stats-grid" aria-label="สรุปสมาชิก">
    <div class="stat-card"><p>สมาชิกที่แสดง</p><strong><?= count($members) ?></strong></div>
    <div class="stat-card"><p>แต้มรวมที่ใช้งาน</p><strong><?= $totalPoints ?> แต้ม</strong></div>
    <div class="stat-card"><p>กติกาสะสมแต้ม</p><strong>10 บาท = 1 แต้ม</strong></div>
</section>

<section class="card filter-card">
    <form class="actions" method="get" action="<?= e(app_url('admin/members.php')) ?>">
        <label class="sr-only" for="q">ค้นหาเลขสมาชิกหรือชื่อ</label>
        <input id="q" name="q" value="<?= e($term) ?>" placeholder="ค้นหาเลขสมาชิกหรือชื่อ...">
        <button class="button" type="submit">ค้นหา</button>
        <?php if ($term !== ''): ?><a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">ล้าง</a><?php endif; ?>
    </form>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายชื่อสมาชิก</h2>
            <p class="muted">ชื่อซ้ำจะแสดงเลขสมาชิกและเบอร์โทรเพื่อแยกบุคคล</p>
        </div>
    </div>
    <div class="table-wrap">
        <?php if ($members === []): ?>
            <div class="empty">ไม่พบสมาชิกจากคำค้นนี้</div>
        <?php else: ?>
            <table>
                <thead><tr><th>เลขสมาชิก</th><th>ชื่อ</th><th>เบอร์โทร</th><th>แต้มรวม</th><th>ระดับ</th><th>จัดการ</th></tr></thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <?php $level = member_level((int) $member['total_points']); ?>
                    <tr>
                        <td><a href="<?= e(app_url('admin/member_detail.php?id=' . (int) $member['id'])) ?>"><strong><?= e($member['member_no']) ?></strong></a></td>
                        <td><?= e($member['name']) ?></td>
                        <td><?= e($member['phone']) ?></td>
                        <td><strong><?= (int) $member['total_points'] ?></strong> แต้ม</td>
                        <td><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></td>
                        <td><div class="table-actions"><a href="<?= e(app_url('admin/member_edit.php?id=' . (int) $member['id'])) ?>">แก้ไข</a><a href="<?= e(app_url('admin/purchase.php?member_id=' . (int) $member['id'])) ?>">บันทึกซื้อ</a></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
