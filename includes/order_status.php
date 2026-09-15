<?php
declare(strict_types=1);

function can_transition_order(string $from, string $to): bool
{
    $transitions = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    return in_array($to, $transitions[$from] ?? [], true);
}

function status_can_earn_points(string $status): bool
{
    return $status === 'completed';
}
