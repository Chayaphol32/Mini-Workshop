<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expect_text_helper(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAIL: {$message}");
    }
    echo "PASS: {$message}\n";
}

expect_text_helper(text_initial('ชญา') === 'ช', 'Thai initial works without mbstring');
expect_text_helper(text_initial('Coffee') === 'C', 'ASCII initial works');
expect_text_helper(text_lower('LATTE') === 'latte', 'ASCII text lowercasing works without mbstring');
expect_text_helper(text_lower('ชา') === 'ชา', 'Thai text remains readable without mbstring');

echo "Text helper checks passed.\n";
