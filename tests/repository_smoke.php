<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$members = search_members($pdo, '');
if (count($members) < 1 || !array_key_exists('total_points', $members[0])) {
    throw new RuntimeException('Member query did not return aggregate points.');
}

$member = find_member($pdo, (int) $members[0]['id']);
if ($member === null || !array_key_exists('total_points', $member)) {
    throw new RuntimeException('Member detail query failed.');
}

echo "Repository smoke checks passed.\n";
