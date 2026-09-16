<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$checks = 0;

function check_ux(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

check_ux(role_home('user') === 'user/menu.php', 'User login lands on the order-first menu');

$header = (string) file_get_contents(__DIR__ . '/../includes/header.php');
check_ux(str_contains($header, 'app-sidebar'), 'authenticated shell has a desktop sidebar');
check_ux(str_contains($header, 'mobile-nav'), 'authenticated shell has mobile navigation');
check_ux(str_contains($header, 'topbar'), 'authenticated shell has a utility topbar');

$menu = (string) file_get_contents(__DIR__ . '/../user/menu.php');
check_ux(str_contains($menu, 'menu-search'), 'menu screen has a dedicated search control');
check_ux(str_contains($menu, 'aria-pressed="true"'), 'menu category filters expose pressed state');
check_ux(!str_contains($menu, 'role="tab"'), 'menu category filters use button semantics');
check_ux(str_contains($menu, 'aria-label="จำนวน <?= e($menuName) ?>"'), 'menu quantity inputs have an accessible name');
check_ux(str_contains($menu, 'cart-bar'), 'menu screen has a sticky cart summary');
check_ux(str_contains($menu, 'cart-sheet'), 'menu screen has an accessible cart sheet');

$orders = (string) file_get_contents(__DIR__ . '/../admin/orders.php');
check_ux(str_contains($orders, 'data-list-mobile'), 'admin order data has a mobile representation');

$userOrders = (string) file_get_contents(__DIR__ . '/../user/orders.php');
check_ux(str_contains($userOrders, 'data-list-mobile'), 'user order data has a mobile representation');

$members = (string) file_get_contents(__DIR__ . '/../admin/members.php');
check_ux(str_contains($members, 'data-list-mobile'), 'member directory has a mobile representation');

$catalog = (string) file_get_contents(__DIR__ . '/../admin/menu.php');
check_ux(str_contains($catalog, 'data-list-mobile'), 'menu catalog has a mobile representation');

$dashboard = (string) file_get_contents(__DIR__ . '/../admin/index.php');
check_ux(str_contains($dashboard, 'data-list-mobile'), 'admin dashboard queue has a mobile representation');

$memberHub = (string) file_get_contents(__DIR__ . '/../user/index.php');
check_ux(str_contains($memberHub, 'member-hub'), 'member points screen has a focused hub layout');
check_ux(str_contains($memberHub, 'progress-card'), 'member points screen has a progress card');

$userDetail = (string) file_get_contents(__DIR__ . '/../user/order_detail.php');
check_ux(str_contains($userDetail, 'receipt-list'), 'user order detail has a responsive receipt representation');

$adminDetail = (string) file_get_contents(__DIR__ . '/../admin/order_detail.php');
check_ux(str_contains($adminDetail, 'receipt-list'), 'admin order detail has a responsive receipt representation');

$memberDetail = (string) file_get_contents(__DIR__ . '/../admin/member_detail.php');
check_ux(str_contains($memberDetail, 'data-list-mobile'), 'member detail has a mobile purchase history representation');

$accounts = (string) file_get_contents(__DIR__ . '/../admin/users.php');
check_ux(str_contains($accounts, 'data-list-mobile'), 'account directory has a mobile representation');

$purchase = (string) file_get_contents(__DIR__ . '/../admin/purchase.php');
check_ux(str_contains($purchase, 'data-list-mobile'), 'manual purchase member picker has a mobile representation');

$styles = (string) file_get_contents(__DIR__ . '/../assets/style.css');
check_ux(!str_contains($styles, '@import url('), 'design system does not block on a remote font import');
check_ux(str_contains($styles, '.app-shell-authenticated'), 'design system defines the authenticated app shell');
check_ux(str_contains($styles, '.mobile-nav'), 'design system defines the mobile navigation surface');
check_ux(str_contains($styles, '.quick-actions-grid'), 'design system defines admin quick actions');
check_ux(str_contains($styles, '.metric-caption'), 'design system defines compact metric captions');
check_ux(str_contains($styles, '.member-hub'), 'design system defines the member points hub');
check_ux(str_contains($styles, '.progress-card'), 'design system defines the rewards progress card');
check_ux(str_contains($styles, '.receipt-list'), 'design system defines responsive receipt items');
check_ux(str_contains($styles, '.workflow-layout'), 'design system defines the manual purchase workflow');
check_ux(str_contains($styles, '.stepper-input:focus-visible'), 'design system keeps quantity focus visible');

fwrite(STDOUT, "UX contract checks passed: {$checks}\n");
