
<?php
$role = $role ?? user()['role'];
$section = $section ?? 'overview';
$prefix = $role . '/dashboard';

$menus = $role === 'admin'
    ? [
        'overview' => 'Overview',
        'events' => 'Events',
        'buyers' => 'Attendees / Buyers',
        'orders' => 'Tickets & Orders',
        'payments' => 'Payments',
        'analytics' => 'Analytics',
        'users' => 'Users'
    ]
    : ($role === 'developer'
        ? [
            'overview' => 'System Overview',
            'api' => 'API Management',
            'logs' => 'Activity Logs',
            'errors' => 'Error & Security Logs',
            'users' => 'User & Role Management'
        ]
        : [
            'overview' => 'My Overview',
            'orders' => 'My Orders',
            'tickets' => 'My Tickets'
        ]);

function money_id($n)
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title ?? 'Dashboard') ?> · Batavias</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>

<body class="workspace">

<aside class="sidebar">
    <a class="brand" href="<?= e(url('')) ?>">
        <span class="brand-mark">B</span> batavias
    </a>

    <div class="workspace-label">
        <?= e(strtoupper($role)) ?> WORKSPACE
    </div>

    <div class="profile-mini">
        <span class="avatar">
            <?= e(strtoupper(substr(user()['name'] ?? 'U', 0, 1))) ?>
        </span>
        <span>
            <b><?= e(user()['name'] ?? 'User') ?></b>
            <small><?= e(user()['role'] ?? $role) ?> account</small>
        </span>
    </div>

    <nav class="side-nav">
        <?php foreach ($menus as $key => $label): ?>
            <a class="<?= $section === $key ? 'active' : '' ?>"
               href="<?= e(url($prefix . ($key === 'overview' ? '' : '/' . $key))) ?>">
                <span><?= e($label) ?></span>

                <?php if ($key === 'events' && $role === 'admin'): ?>
                    <small>
                        <?= e(db()->query("SELECT COUNT(*) n FROM events WHERE status='pending'")->fetch()['n'] ?? 0) ?>
                    </small>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="back-link" href="<?= e(url('')) ?>">← Back to website</a>

    <form method="post" action="<?= e(url('logout')) ?>" class="logout-form">
        <?= csrf_field() ?>
        <button class="side-logout">↪ Sign out</button>
    </form>
</aside>

<main class="workspace-main">

<header class="workspace-top">
    <div>
        <span class="eyebrow"><?= e(strtoupper($role)) ?> / BATAVIAS</span>

        <h1><?= e($menus[$section] ?? 'Overview') ?></h1>

        <p>
            <?= $role === 'admin'
                ? 'Manage events, attendees, orders and business performance.'
                : ($role === 'developer'
                    ? 'Monitor platform health, APIs, security and technical activity.'
                    : 'Your events, bookings and digital tickets in one place.') ?>
        </p>
    </div>

    <div class="top-user">
        <span class="avatar">
            <?= e(strtoupper(substr(user()['name'] ?? 'U', 0, 1))) ?>
        </span>
        <?= e(user()['name'] ?? 'User') ?>
    </div>
</header>

<?php if ($m = flash('success')): ?>
    <div class="alert success"><?= e($m) ?></div>
<?php endif; ?>

<?php if ($m = flash('error')): ?>
    <div class="alert error"><?= e($m) ?></div>
<?php endif; ?>

<!-- STATISTICS -->
<section class="stat-grid">
    <?php foreach (($stats ?? []) as $st): ?>
        <article class="stat-card">
            <div class="stat-icon">✦</div>
            <small><?= e($st[0] ?? '') ?></small>
            <strong><?= e($st[1] ?? 0) ?></strong>
            <span>Live platform data</span>
        </article>
    <?php endforeach; ?>
</section>

<!-- ADMIN CREATE EVENT -->
<?php if ($role === 'admin' && $section === 'events'): ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Create new event</h2>
            <p>Publish an event and set its initial ticket inventory.</p>
        </div>
    </div>

    <form method="post" class="event-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <label>
            Event name
            <input name="title" required placeholder="Jakarta Product Summit">
        </label>

        <label>
            City
            <input name="city" required placeholder="Jakarta">
        </label>

        <label>
            Venue
            <input name="venue" required placeholder="Venue name">
        </label>

        <label>
            Ticket price (IDR)
            <input name="price" type="number" min="0" value="100000" required>
        </label>

        <label>
            Ticket quota
            <input name="quota" type="number" min="1" value="100" required>
        </label>

        <label>
            Start date
            <input name="start_date" type="datetime-local" required>
        </label>

        <label>
            End date
            <input name="end_date" type="datetime-local" required>
        </label>

        <label class="wide">
            Description
            <textarea name="description" required rows="3"></textarea>
        </label>

        <button class="btn">Create & publish event</button>
    </form>
</section>
<?php endif; ?>

<!-- USER EVENT DISCOVERY -->
<?php if ($role === 'user' && $section === 'overview'): ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Discover upcoming events</h2>
            <p>Find your next experience.</p>
        </div>

        <a class="btn btn-outline" href="<?= e(url('events')) ?>">
            Explore all events
        </a>
    </div>

    <div class="event-mini-grid">
        <?php foreach (($events ?? []) as $ev): ?>
            <a class="event-mini" href="<?= e(url('events/' . ($ev['slug'] ?? ''))) ?>">
                <span class="event-date">
                    <?= !empty($ev['start_date']) ? e(date('d M', strtotime($ev['start_date']))) : '—' ?>
                </span>

                <b><?= e($ev['title'] ?? 'Untitled event') ?></b>

                <small>
                    <?= e($ev['city'] ?? '—') ?> · <?= e($ev['venue'] ?? '—') ?>
                </small>

                <span class="mini-link">View event →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- DATA TABLE -->
<section class="panel">

    <div class="panel-head">
        <div>
            <h2>
                <?= $role === 'admin'
                    ? ($section === 'events'
                        ? 'Event directory'
                        : ($section === 'payments'
                            ? 'Payment transactions'
                            : ($section === 'buyers'
                                ? 'Attendee directory'
                                : ($section === 'orders'
                                    ? 'Order directory'
                                    : 'Latest activity'))))
                    : ($role === 'developer'
                        ? 'Technical activity'
                        : 'Your ' . ($section === 'tickets' ? 'tickets' : 'orders')) ?>
            </h2>

            <p>Records are loaded directly from the Batavias database.</p>
        </div>

        <?php if ($role === 'admin' && in_array($section, ['buyers', 'orders', 'payments', 'analytics'], true)): ?>
            <button class="btn btn-outline" onclick="window.print()">
                Export / Print
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($rows)): ?>

        <div class="empty-state">
            <div>◌</div>
            <b>No records yet</b>
            <p>Data will appear here when platform activity is available.</p>
        </div>

    <?php else: ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>

                    <?php if ($section === 'events'): ?>
                        <th>Event</th>
                        <th>Organizer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>

                    <?php elseif ($section === 'buyers'): ?>
                        <th>Buyer</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total spent</th>
                        <th>Last order</th>

                    <?php elseif ($section === 'orders'): ?>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>

                    <?php elseif ($section === 'payments'): ?>
                        <th>Reference</th>
                        <th>Buyer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status / Action</th>

                    <?php elseif ($section === 'analytics'): ?>
                        <th>Month</th>
                        <th>Paid orders</th>
                        <th>Gross</th>

                    <?php elseif ($section === 'users'): ?>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>

                    <?php elseif ($role === 'developer'): ?>
                        <th>Time</th>
                        <th>Level / Method</th>
                        <th>Category / Path</th>
                        <th>Message / Status</th>

                    <?php elseif ($section === 'tickets'): ?>
                        <th>Ticket number</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>

                    <?php else: ?>
                        <th>Order</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    <?php endif; ?>

                </tr>
            </thead>

            <tbody>

            <?php foreach ($rows as $r): ?>
                <tr>

                <?php if ($section === 'events'): ?>

                    <td>
                        <b><?= e($r['title'] ?? '—') ?></b>
                        <small><?= e($r['city'] ?? '—') ?></small>
                    </td>

                    <td><?= e($r['organizer_name'] ?? '—') ?></td>

                    <td>
                        <?= !empty($r['start_date']) ? e(date('d M Y', strtotime($r['start_date']))) : '—' ?>
                    </td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>
                    </td>

                    <td>
                        <div class="action-row">
                            <?php foreach (['published' => 'Publish', 'rejected' => 'Reject', 'cancelled' => 'Cancel'] as $act => $label): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($r['id'] ?? 0) ?>">
                                    <button name="action" value="<?= e($act) ?>" class="tiny-btn">
                                        <?= e($label) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </td>

                <?php elseif ($section === 'buyers'): ?>

                    <td><b><?= e($r['name'] ?? '—') ?></b></td>
                    <td><?= e($r['email'] ?? '—') ?></td>
                    <td><?= e($r['orders'] ?? 0) ?></td>
                    <td><?= e(money_id($r['spent'] ?? 0)) ?></td>

                    <td>
                        <?= !empty($r['last_order']) ? e(date('d M Y', strtotime($r['last_order']))) : '—' ?>
                    </td>

                <?php elseif ($section === 'orders'): ?>

                    <td><b><?= e($r['order_number'] ?? '—') ?></b></td>

                    <td>
                        <?= e($r['buyer'] ?? '—') ?>
                        <small><?= e($r['email'] ?? '—') ?></small>
                    </td>

                    <td><?= e(money_id($r['total_amount'] ?? 0)) ?></td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>
                    </td>

                    <td>
                        <?= !empty($r['created_at']) ? e(date('d M Y H:i', strtotime($r['created_at']))) : '—' ?>
                    </td>

                <?php elseif ($section === 'payments'): ?>

                    <td>
                        <b><?= e($r['reference'] ?? '—') ?></b>
                        <small><?= e($r['order_number'] ?? '—') ?></small>
                    </td>

                    <td>
                        <?= e($r['buyer'] ?? '—') ?>
                        <small><?= e($r['email'] ?? '—') ?></small>
                    </td>

                    <td><?= e($r['method'] ?? '—') ?></td>
                    <td><?= e(money_id($r['amount'] ?? 0)) ?></td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>

                        <div class="action-row">
                            <?php foreach (['success' => 'Mark paid', 'failed' => 'Failed', 'refunded' => 'Refund'] as $act => $label): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($r['id'] ?? 0) ?>">
                                    <button class="tiny-btn" name="action" value="<?= e($act) ?>">
                                        <?= e($label) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </td>

                <?php elseif ($section === 'analytics'): ?>

                    <td><?= e($r['month'] ?? '—') ?></td>
                    <td><?= e($r['orders'] ?? 0) ?></td>
                    <td><?= e(money_id($r['gross'] ?? 0)) ?></td>

                <?php elseif ($section === 'users'): ?>

                    <td><b><?= e($r['name'] ?? '—') ?></b></td>
                    <td><?= e($r['email'] ?? '—') ?></td>
                    <td><?= e($r['role'] ?? '—') ?></td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>
                    </td>

                    <td>
                        <?php if (($r['role'] ?? '') === 'user'): ?>
                            <form method="post" class="action-row">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($r['id'] ?? 0) ?>">
                                <button class="tiny-btn" name="action" value="active">Active</button>
                                <button class="tiny-btn" name="action" value="suspended">Suspend</button>
                            </form>
                        <?php endif; ?>
                    </td>

                <?php elseif ($role === 'developer'): ?>

                    <td><?= e($r['created_at'] ?? '') ?></td>

                    <td>
                        <?= e($r['level'] ?? $r['method'] ?? '—') ?>
                    </td>

                    <td>
                        <?= e($r['category'] ?? $r['path'] ?? '—') ?>
                    </td>

                    <td>
                        <?= e(
                            $r['message']
                            ?? (
                                'HTTP ' . ($r['status_code'] ?? '—')
                                . ' · ' . ($r['duration_ms'] ?? '—') . ' ms'
                            )
                        ) ?>
                    </td>

                <?php elseif ($section === 'tickets'): ?>

                    <td><b><?= e($r['ticket_number'] ?? '—') ?></b></td>
                    <td><?= e($r['title'] ?? '—') ?></td>

                    <td>
                        <?= !empty($r['start_date']) ? e(date('d M Y H:i', strtotime($r['start_date']))) : '—' ?>
                    </td>

                    <td><?= e($r['venue'] ?? '—') ?></td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>
                    </td>

                <?php else: ?>

                    <td><b><?= e($r['order_number'] ?? '—') ?></b></td>
                    <td><?= e(money_id($r['total_amount'] ?? 0)) ?></td>
                    <td><?= e($r['payment_status'] ?? 'not created') ?></td>

                    <td>
                        <span class="status <?= e($r['status'] ?? '') ?>">
                            <?= e($r['status'] ?? '—') ?>
                        </span>
                    </td>

                    <td>
                        <?= !empty($r['created_at']) ? e(date('d M Y H:i', strtotime($r['created_at']))) : '—' ?>

                        <?php if (
                            $role === 'user' &&
                            ($r['payment_status'] ?? '') === 'pending'
                        ): ?>
                            <form method="post" class="action-row">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($r['id'] ?? 0) ?>">
                                <button class="tiny-btn" name="action" value="pay">
                                    Pay now (demo)
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>

                <?php endif; ?>

                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <?php endif; ?>

</section>

<footer class="workspace-footer">
    Batavias · <?= date('Y') ?>
    <span>Secure <?= e(ucfirst($role)) ?> Workspace</span>
</footer>

</main>
</body>
</html>
