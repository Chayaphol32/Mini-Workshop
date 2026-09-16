<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'บันทึกยอดซื้อ';
$pathPrefix = '../';
$errors = [];
$term = trim((string) ($_GET['q'] ?? ''));
$selectedMemberId = parse_id($_GET['member_id'] ?? null);
$amountInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term = trim((string) ($_POST['q'] ?? ''));
    $selectedMemberId = parse_id($_POST['member_id'] ?? null);
    $amountInput = trim((string) ($_POST['amount'] ?? ''));
}

$selectedMember = $selectedMemberId === null ? null : find_member($pdo, $selectedMemberId);
if ($selectedMemberId !== null && $selectedMember === null) {
    $errors[] = 'ไม่พบสมาชิกที่เลือก';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if ($selectedMemberId === null) {
        $errors[] = 'กรุณาเลือกสมาชิก';
    }
    $amount = parse_positive_amount($amountInput);
    if ($amount === null) {
        $errors[] = 'ยอดซื้อต้องเป็นตัวเลขมากกว่า 0 และมีทศนิยมไม่เกิน 2 ตำแหน่ง';
    }
    if ($errors === []) {
        $points = calculate_points($amount);
        create_purchase($pdo, $selectedMemberId, $amount, $points);
        flash('success', 'บันทึกยอดซื้อ ' . money($amount) . ' บาท ได้รับ ' . $points . ' แต้ม');
        redirect(app_url('admin/member_detail.php?id=' . $selectedMemberId));
    }
}

$members = search_members($pdo, $term);
require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow">MANUAL REWARDS</span>
        <h1>บันทึกยอดซื้อ</h1>
        <p class="muted">ค้นหาสมาชิก ตรวจสอบตัวตน แล้วเพิ่มแต้มจากยอดซื้อหน้าร้าน</p>
    </div>
    <div class="page-header-actions"><a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">กลับสมาชิก</a></div>
</section>

<div class="workflow-layout">
    <section class="card workflow-step">
        <div class="section-heading">
            <div><span class="step-kicker">STEP 01</span><h2>เลือกสมาชิก</h2><p class="muted">ค้นหาด้วยเลขสมาชิก ชื่อ หรือเบอร์โทร</p></div>
            <?php if ($selectedMember !== null): ?><span class="badge badge-active">เลือกแล้ว</span><?php endif; ?>
        </div>
        <form class="filter-grid filter-card" method="get" action="<?= e(app_url('admin/purchase.php')) ?>">
            <div class="field"><label for="q">ค้นหาสมาชิก</label><input id="q" name="q" value="<?= e($term) ?>" placeholder="เลขสมาชิกหรือชื่อ..." autocomplete="off"></div>
            <div class="actions filter-actions"><button class="button button-primary" type="submit">ค้นหา</button><?php if ($term !== ''): ?><a class="button button-muted" href="<?= e(app_url('admin/purchase.php')) ?>">ล้าง</a><?php endif; ?></div>
        </form>
        <?php if ($members === []): ?>
            <div class="empty-state compact-empty"><span class="empty-state-icon" aria-hidden="true">♙</span><h2>ไม่พบสมาชิก</h2><p>ลองปรับคำค้นหาแล้วค้นหาใหม่</p></div>
        <?php else: ?>
            <div class="table-wrap data-table-desktop member-picker">
                <table>
                    <caption class="sr-only">สมาชิกสำหรับเลือกบันทึกยอดซื้อ</caption>
                    <thead><tr><th scope="col">เลขสมาชิก</th><th scope="col">ชื่อ</th><th scope="col">เบอร์โทร</th><th scope="col">แต้มรวม</th><th scope="col"></th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $member): ?>
                        <?php $selectUrl = app_url('admin/purchase.php?' . http_build_query(['q' => $term, 'member_id' => (int) $member['id']])); ?>
                        <tr class="<?= $selectedMemberId === (int) $member['id'] ? 'selected-row' : '' ?>"><td><strong><?= e($member['member_no']) ?></strong></td><td><?= e($member['name']) ?></td><td><?= e($member['phone']) ?></td><td><?= (int) $member['total_points'] ?> แต้ม</td><td><a class="button button-small <?= $selectedMemberId === (int) $member['id'] ? 'button-primary' : 'button-muted' ?>" href="<?= e($selectUrl) ?>"><?= $selectedMemberId === (int) $member['id'] ? 'เลือกแล้ว' : 'เลือกสมาชิก' ?></a></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="data-list-mobile member-picker-list" aria-label="สมาชิกสำหรับเลือกบนมือถือ">
                <?php foreach ($members as $member): ?>
                    <?php $selectUrl = app_url('admin/purchase.php?' . http_build_query(['q' => $term, 'member_id' => (int) $member['id']])); ?>
                    <article class="data-card member-picker-card <?= $selectedMemberId === (int) $member['id'] ? 'is-selected' : '' ?>">
                        <div class="data-card-top"><div><span class="data-card-label">MEMBER</span><strong><?= e($member['name']) ?></strong></div><span class="badge badge-user"><?= (int) $member['total_points'] ?> แต้ม</span></div>
                        <div class="data-card-grid"><div><span class="data-card-label">เลขสมาชิก</span><strong><?= e($member['member_no']) ?></strong><small><?= e($member['phone']) ?></small></div></div>
                        <a class="button button-small <?= $selectedMemberId === (int) $member['id'] ? 'button-primary' : 'button-muted' ?> data-card-action" href="<?= e($selectUrl) ?>"><?= $selectedMemberId === (int) $member['id'] ? 'เลือกสมาชิกคนนี้แล้ว' : 'เลือกสมาชิกคนนี้' ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card workflow-step">
        <div class="section-heading">
            <div><span class="step-kicker">STEP 02</span><h2>บันทึกยอดซื้อ</h2><p class="muted">แต้มจะถูกคำนวณจากยอดเงินและเพิ่มเมื่อบันทึกสำเร็จ</p></div>
        </div>
        <?php if ($errors !== []): ?><ul class="error-list error-summary" role="alert" aria-label="เกิดข้อผิดพลาด"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($selectedMember !== null): ?>
            <div class="selected-member selected-member-card"><span class="data-card-label">SELECTED MEMBER</span><h3><?= e($selectedMember['name']) ?></h3><p class="member-meta"><span>เลขสมาชิก <?= e($selectedMember['member_no']) ?></span><span>โทร <?= e($selectedMember['phone']) ?></span><span>แต้มเดิม <?= (int) $selectedMember['total_points'] ?> แต้ม</span></p></div>
            <form method="post" action="<?= e(app_url('admin/purchase.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="member_id" value="<?= (int) $selectedMember['id'] ?>"><input type="hidden" name="q" value="<?= e($term) ?>">
                <div class="field"><label for="amount">ยอดซื้อ (บาท)</label><input id="amount" name="amount" value="<?= e($amountInput) ?>" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="เช่น 125.00" required><p class="field-hint">ระบบจะปัดเศษลง: ทุก 10 บาท = 1 แต้ม</p></div>
                <div class="preview-box points-preview-card"><span>แต้มที่จะได้รับจากรายการนี้</span><strong><span id="points-preview">0</span> แต้ม</strong></div>
                <div class="actions form-actions"><button class="button button-primary" type="submit">ยืนยันบันทึกยอดซื้อ</button><a class="button button-muted" href="<?= e(app_url('admin/purchase.php')) ?>">เลือกสมาชิกใหม่</a></div>
            </form>
        <?php else: ?>
            <div class="empty-state compact-empty"><span class="empty-state-icon" aria-hidden="true">✦</span><h2>เลือกสมาชิกก่อน</h2><p>เลือกสมาชิกจากขั้นตอนที่ 1 แล้วจึงกรอกยอดซื้อ</p></div>
        <?php endif; ?>
    </section>
</div>

<script>
const amountInput = document.getElementById('amount');
const pointsPreview = document.getElementById('points-preview');
if (amountInput && pointsPreview) {
    const updatePointsPreview = () => {
        const amount = Number(amountInput.value);
        pointsPreview.textContent = Number.isFinite(amount) && amount > 0 ? String(Math.floor(amount / 10)) : '0';
    };
    amountInput.addEventListener('input', updatePointsPreview);
    updatePointsPreview();
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
