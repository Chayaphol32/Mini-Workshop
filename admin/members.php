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
<section class="page-header page-heading">
    <div>
        <span class="eyebrow">MEMBER DIRECTORY</span>
        <h1>สมาชิกทั้งหมด</h1>
        <p class="muted">ค้นหาโปรไฟล์ ดูแต้ม และเข้าถึงประวัติของสมาชิกแต่ละคน</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-primary" href="<?= e(app_url('admin/member_create.php')) ?>"><span aria-hidden="true">+</span> เพิ่มสมาชิก</a>
    </div>
</section>

<section class="stats-grid metric-strip" aria-label="สรุปสมาชิก">
    <div class="stat-card">
        <p>ผลการค้นหา</p>
        <strong><?= number_format(count($members)) ?> <small>คน</small></strong>
    </div>
    <div class="stat-card">
        <p>แต้มรวมในผลลัพธ์</p>
        <strong><?= number_format($totalPoints) ?> <small>แต้ม</small></strong>
    </div>
    <div class="stat-card">
        <p>กติกาสะสมแต้ม</p>
        <strong>10 <small>฿</small> = 1 <small>แต้ม</small></strong>
    </div>
</section>

<section class="card filter-card">
    <form class="filter-grid" method="get" action="<?= e(app_url('admin/members.php')) ?>">
        <div class="field">
            <label for="q">ค้นหาสมาชิก</label>
            <input id="q" name="q" value="<?= e($term) ?>" placeholder="เลขสมาชิก, ชื่อ หรือเบอร์โทรศัพท์..." autocomplete="off">
        </div>
        <div class="actions filter-actions">
            <button class="button button-primary" type="submit">ค้นหา</button>
            <?php if ($term !== ''): ?>
                <a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">ล้างคำค้น</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">DIRECTORY</span>
            <h2>รายชื่อสมาชิก</h2>
            <p class="muted">เลือกสมาชิกเพื่อดูประวัติยอดซื้อและแต้มสะสม</p>
        </div>
        <span class="badge badge-user"><?= number_format(count($members)) ?> ผลลัพธ์</span>
    </div>
    <?php if ($members === []): ?>
        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true">♙</span>
            <h2>ไม่พบสมาชิก</h2>
            <p>ลองค้นหาด้วยเลขสมาชิก ชื่อ หรือเบอร์โทรศัพท์อื่น</p>
        </div>
    <?php else: ?>
        <div class="table-wrap data-table-desktop">
            <table>
                <caption class="sr-only">รายชื่อสมาชิกทั้งหมด</caption>
                <thead>
                    <tr>
                        <th scope="col">เลขสมาชิก</th>
                        <th scope="col">ชื่อสมาชิก</th>
                        <th scope="col">เบอร์โทรศัพท์</th>
                        <th scope="col">แต้มสะสม</th>
                        <th scope="col">ระดับสมาชิก</th>
                        <th scope="col" style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <?php $level = member_level((int) $member['total_points']); ?>
                    <tr>
                        <td><a href="<?= e(app_url('admin/member_detail.php?id=' . (int) $member['id'])) ?>"><strong><?= e($member['member_no']) ?></strong></a></td>
                        <td>
                            <div class="table-person">
                                <span class="nav-avatar" aria-hidden="true"><?= e(text_initial((string) $member['name'])) ?></span>
                                <strong><?= e($member['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($member['phone']) ?></td>
                        <td><strong><?= number_format((int) $member['total_points']) ?></strong> แต้ม</td>
                        <td><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></td>
                        <td style="text-align: right;">
                            <div class="actions table-actions">
                                <a class="button button-small button-muted" href="<?= e(app_url('admin/member_edit.php?id=' . (int) $member['id'])) ?>">แก้ไข</a>
                                <a class="button button-small button-muted" href="<?= e(app_url('admin/purchase.php?member_id=' . (int) $member['id'])) ?>">บันทึกซื้อ</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-list-mobile member-data-list" aria-label="รายชื่อสมาชิกบนมือถือ">
            <?php foreach ($members as $member): ?>
                <?php $level = member_level((int) $member['total_points']); ?>
                <article class="data-card member-data-card">
                    <div class="data-card-top">
                        <div class="table-person">
                            <span class="nav-avatar" aria-hidden="true"><?= e(text_initial((string) $member['name'])) ?></span>
                            <div>
                                <span class="data-card-label">MEMBER</span>
                                <strong><?= e($member['name']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span>
                    </div>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">เลขสมาชิก</span>
                            <strong><?= e($member['member_no']) ?></strong>
                            <small><?= e($member['phone']) ?></small>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">แต้มสะสม</span>
                            <strong><?= number_format((int) $member['total_points']) ?></strong>
                            <small>แต้ม</small>
                        </div>
                    </div>
                    <div class="actions data-card-actions">
                        <a class="button button-small button-muted" href="<?= e(app_url('admin/member_detail.php?id=' . (int) $member['id'])) ?>">ดูรายละเอียด</a>
                        <a class="button button-small button-primary" href="<?= e(app_url('admin/purchase.php?member_id=' . (int) $member['id'])) ?>">บันทึกซื้อ</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
