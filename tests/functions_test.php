<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expect_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAIL: {$message}");
    }
    echo "PASS: {$message}\n";
}

expect_true(calculate_points(85.0) === 8, '85 baht gives 8 points');
expect_true(calculate_points(125.0) === 12, '125 baht gives 12 points');
expect_true(member_level(999)['key'] === 'member', '999 points is Member');
expect_true(member_level(1000)['key'] === 'silver', '1000 points is Silver');
expect_true(member_level(4999)['key'] === 'silver', '4999 points is Silver');
expect_true(member_level(5000)['key'] === 'gold', '5000 points is Gold');
expect_true(member_level(9999)['key'] === 'gold', '9999 points is Gold');
expect_true(member_level(10000)['key'] === 'platinum', '10000 points is Platinum');
expect_true(parse_positive_amount('125.00') === 125.0, 'positive amount is accepted');
expect_true(parse_positive_amount('0') === null, 'zero amount is rejected');
expect_true(parse_positive_amount('-1') === null, 'negative amount is rejected');
expect_true(parse_positive_amount('abc') === null, 'non-numeric amount is rejected');
expect_true(parse_id('5') === 5, 'numeric id is accepted');
expect_true(parse_id('abc') === null, 'text id is rejected');

echo "All function checks passed.\n";
