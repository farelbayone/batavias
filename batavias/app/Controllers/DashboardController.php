
<?php

final class DashboardController
{
    public static function dispatch(string $role, string $section = 'overview'): void
    {
        require_role($role);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::handle($role, $section);
        }

        $data = [
            'section' => $section,
            'role'    => $role,
            'stats'   => [],
            'rows'    => [],
            'events'  => []
        ];

        if ($role === 'admin') {
            self::adminData($data);
        } elseif ($role === 'developer') {
            self::developerData($data);
        } else {
            self::userData($data);
        }

        $title = ucfirst($role) . ' · ' . ucfirst($section);

        // Kirim data ke workspace.php
        extract($data, EXTR_SKIP);

        require dirname(__DIR__, 2) . '/views/workspace.php';
    }

    private static function handle(string $role, string $section): void
    {
        // USER: SIMULASI PEMBAYARAN
        if (
            $role === 'user' &&
            $section === 'orders' &&
            ($_POST['action'] ?? '') === 'pay'
        ) {
            $pdo = db();
            $pdo->beginTransaction();

            try {
                $id = (int)($_POST['id'] ?? 0);

                $q = $pdo->prepare(
                    'SELECT p.*, o.user_id
                     FROM payments p
                     JOIN orders o ON o.id = p.order_id
                     WHERE p.id = ?
                     FOR UPDATE'
                );

                $q->execute([$id]);
                $pay = $q->fetch();

                if (!$pay || (int)$pay['user_id'] !== (int)user()['id']) {
                    throw new RuntimeException('Pesanan tidak ditemukan.');
                }

                if ($pay['status'] !== 'pending') {
                    throw new RuntimeException('Pembayaran tidak berstatus pending.');
                }

                $pdo->prepare(
                    "UPDATE payments
                     SET status = 'success', paid_at = NOW()
                     WHERE id = ?"
                )->execute([$id]);

                $pdo->prepare(
                    "UPDATE orders SET status = 'paid' WHERE id = ?"
                )->execute([$pay['order_id']]);

                $q = $pdo->prepare(
                    'SELECT id, quantity
                     FROM order_items
                     WHERE order_id = ?'
                );

                $q->execute([$pay['order_id']]);

                foreach ($q->fetchAll() as $item) {
                    for ($i = 0; $i < (int)$item['quantity']; $i++) {
                        $token = bin2hex(random_bytes(24));

                        $pdo->prepare(
                            "INSERT INTO tickets
                            (order_item_id, ticket_number, qr_token_hash, status, created_at)
                            VALUES (?, ?, ?, 'valid', NOW())"
                        )->execute([
                            $item['id'],
                            'TKT-' . strtoupper(bin2hex(random_bytes(5))),
                            hash('sha256', $token)
                        ]);
                    }
                }

                $pdo->commit();

                log_event(
                    'payment_simulated',
                    'User completed demo payment ' . $pay['reference'],
                    user()['id']
                );

                flash('success', 'Pembayaran berhasil. Tiket digital sudah diterbitkan.');

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                flash('error', $e->getMessage());
            }

            redirect('user/dashboard/orders');
        }

        // ADMIN ACTIONS
        if ($role === 'admin') {

            // CREATE EVENT
            if (
                $section === 'events' &&
                ($_POST['action'] ?? '') === 'create'
            ) {
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $venue = trim($_POST['venue'] ?? '');

                $start = str_replace('T', ' ', $_POST['start_date'] ?? '');
                $end = str_replace('T', ' ', $_POST['end_date'] ?? '');

                $price = max(0, (float)($_POST['price'] ?? 0));
                $quota = max(1, (int)($_POST['quota'] ?? 1));

                if ($title && $desc && $city && $venue && $start && $end) {

                    $slug = strtolower(
                        trim(
                            preg_replace('/[^a-z0-9]+/i', '-', $title),
                            '-'
                        )
                    ) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

                    $stmt = db()->prepare(
                        "INSERT INTO events
                        (
                            organizer_id,
                            title,
                            slug,
                            description,
                            city,
                            venue,
                            start_date,
                            end_date,
                            registration_deadline,
                            capacity,
                            status,
                            created_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW())"
                    );

                    $stmt->execute([
                        user()['id'],
                        $title,
                        $slug,
                        $desc,
                        $city,
                        $venue,
                        $start,
                        $end,
                        date('Y-m-d H:i:s', strtotime($start) - 86400),
                        $quota
                    ]);

                    $eventId = (int)db()->lastInsertId();

                    db()->prepare(
                        'INSERT INTO ticket_types
                        (event_id, name, price, quota, is_active)
                        VALUES (?, ?, ?, ?, 1)'
                    )->execute([
                        $eventId,
                        'Regular',
                        $price,
                        $quota
                    ]);

                    log_event(
                        'event_created',
                        'Admin created event ' . $title,
                        user()['id']
                    );

                    flash('success', 'Event berhasil dibuat dan dipublikasikan.');

                } else {
                    flash('error', 'Lengkapi semua data event.');
                }
            }

            // EVENT MODERATION
            elseif (
                $section === 'events' &&
                in_array(
                    $_POST['action'] ?? '',
                    ['published', 'rejected', 'cancelled'],
                    true
                )
            ) {
                db()->prepare(
                    'UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?'
                )->execute([
                    $_POST['action'],
                    (int)($_POST['id'] ?? 0)
                ]);

                log_event(
                    'event_moderation',
                    'Event status updated by admin',
                    user()['id']
                );

                flash('success', 'Status event berhasil diperbarui.');
            }

            // PAYMENT MANAGEMENT
            elseif (
                $section === 'payments' &&
                in_array(
                    $_POST['action'] ?? '',
                    ['success', 'failed', 'refunded'],
                    true
                )
            ) {
                $id = (int)($_POST['id'] ?? 0);
                $action = $_POST['action'];

                $pdo = db();
                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare(
                        'SELECT * FROM payments WHERE id = ? FOR UPDATE'
                    );

                    $stmt->execute([$id]);
                    $pay = $stmt->fetch();

                    if (!$pay) {
                        throw new RuntimeException('Pembayaran tidak ditemukan.');
                    }

                    $pdo->prepare(
                        "UPDATE payments
                         SET status = ?,
                         paid_at = IF(? = 'success', NOW(), paid_at)
                         WHERE id = ?"
                    )->execute([
                        $action,
                        $action,
                        $id
                    ]);

                    $orderStatus = $action === 'success'
                        ? 'paid'
                        : ($action === 'refunded' ? 'refunded' : 'pending');

                    $pdo->prepare(
                        'UPDATE orders SET status = ? WHERE id = ?'
                    )->execute([
                        $orderStatus,
                        $pay['order_id']
                    ]);

                    $pdo->commit();

                    log_event(
                        'payment_update',
                        'Payment ' . $id . ' updated to ' . $action,
                        user()['id']
                    );

                    flash('success', 'Status pembayaran berhasil diperbarui.');

                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    flash('error', $e->getMessage());
                }
            }

            // USER MANAGEMENT
            elseif (
                $section === 'users' &&
                in_array(
                    $_POST['action'] ?? '',
                    ['active', 'inactive', 'suspended'],
                    true
                )
            ) {
                db()->prepare(
                    'UPDATE users
                     SET status = ?, updated_at = NOW()
                     WHERE id = ? AND role = "user"'
                )->execute([
                    $_POST['action'],
                    (int)($_POST['id'] ?? 0)
                ]);

                flash('success', 'Status akun berhasil diperbarui.');
            }
        }

        redirect(
            $role . '/dashboard' .
            ($section === 'overview' ? '' : '/' . $section)
        );
    }

    // ==========================================
    // ADMIN DASHBOARD
    // ==========================================

    private static function adminData(array &$d): void
    {
        $d['stats'] = [
            [
                'Total events',
                db()->query('SELECT COUNT(*) n FROM events')->fetch()['n']
            ],
            [
                'Ticket buyers',
                db()->query('SELECT COUNT(DISTINCT user_id) n FROM orders')->fetch()['n']
            ],
            [
                'Gross revenue',
                'Rp ' . number_format(
                    (float)db()->query(
                        "SELECT COALESCE(SUM(amount), 0) n
                         FROM payments
                         WHERE status = 'success'"
                    )->fetch()['n'],
                    0,
                    ',',
                    '.'
                )
            ],
            [
                'Pending payments',
                db()->query(
                    "SELECT COUNT(*) n FROM payments WHERE status = 'pending'"
                )->fetch()['n']
            ]
        ];

        $section = $d['section'];

        if ($section === 'events') {

            $d['rows'] = db()->query(
                'SELECT e.*, u.name organizer_name
                 FROM events e
                 LEFT JOIN users u ON u.id = e.organizer_id
                 ORDER BY e.created_at DESC'
            )->fetchAll();

        } elseif ($section === 'buyers') {

            $d['rows'] = db()->query(
                "SELECT
                    u.name,
                    u.email,
                    COUNT(o.id) orders,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN o.status = 'paid'
                                THEN o.total_amount
                                ELSE 0
                            END
                        ), 0
                    ) spent,
                    MAX(o.created_at) last_order
                 FROM users u
                 JOIN orders o ON o.user_id = u.id
                 GROUP BY u.id
                 ORDER BY last_order DESC"
            )->fetchAll();

        } elseif ($section === 'orders') {

            $d['rows'] = db()->query(
                'SELECT
                    o.*,
                    u.name buyer,
                    u.email,
                    COALESCE(
                        (SELECT p.status
                         FROM payments p
                         WHERE p.order_id = o.id
                         ORDER BY p.id DESC
                         LIMIT 1),
                        "not created"
                    ) AS payment_status
                 FROM orders o
                 JOIN users u ON u.id = o.user_id
                 ORDER BY o.created_at DESC'
            )->fetchAll();

        } elseif ($section === 'payments') {

            $d['rows'] = db()->query(
                'SELECT
                    p.*,
                    o.order_number,
                    u.name buyer,
                    u.email
                 FROM payments p
                 JOIN orders o ON o.id = p.order_id
                 JOIN users u ON u.id = o.user_id
                 ORDER BY p.created_at DESC'
            )->fetchAll();

        } elseif ($section === 'analytics') {

            $d['rows'] = db()->query(
                "SELECT
                    DATE_FORMAT(created_at, '%Y-%m') month,
                    COUNT(*) orders,
                    SUM(total_amount) gross
                 FROM orders
                 WHERE status = 'paid'
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 12"
            )->fetchAll();

        } elseif ($section === 'users') {

            $d['rows'] = db()->query(
                'SELECT id, name, email, role, status, created_at
                 FROM users
                 ORDER BY created_at DESC'
            )->fetchAll();

        } else {

            // ADMIN OVERVIEW: DATA PESANAN + PEMBELI
            $d['rows'] = db()->query(
                "SELECT
                    o.order_number,
                    o.total_amount,
                    o.status,
                    o.created_at,
                    u.name AS buyer,
                    u.email AS email,
                    COALESCE(
                        (SELECT p.status
                         FROM payments p
                         WHERE p.order_id = o.id
                         ORDER BY p.id DESC
                         LIMIT 1),
                        'not created'
                    ) AS payment_status
                 FROM orders o
                 LEFT JOIN users u ON u.id = o.user_id
                 ORDER BY o.created_at DESC
                 LIMIT 8"
            )->fetchAll();
        }
    }

    // ==========================================
    // DEVELOPER DASHBOARD
    // ==========================================

    private static function developerData(array &$d): void
    {
        $d['stats'] = [
            ['PHP runtime', PHP_VERSION],
            ['Database', 'Connected'],
            [
                'API requests',
                db()->query('SELECT COUNT(*) n FROM api_logs')->fetch()['n']
            ],
            [
                'System errors',
                db()->query(
                    "SELECT COUNT(*) n
                     FROM system_logs
                     WHERE level = 'error'"
                )->fetch()['n']
            ]
        ];

        $section = $d['section'];

        if ($section === 'api') {

            $d['rows'] = db()->query(
                'SELECT * FROM api_logs ORDER BY id DESC LIMIT 100'
            )->fetchAll();

        } elseif ($section === 'errors') {

            $d['rows'] = db()->query(
                'SELECT * FROM system_logs
                 WHERE level IN ("error", "warning", "security")
                 ORDER BY id DESC
                 LIMIT 100'
            )->fetchAll();

        } elseif ($section === 'users') {

            $d['rows'] = db()->query(
                'SELECT id, name, email, role, status, created_at
                 FROM users
                 ORDER BY id DESC'
            )->fetchAll();

        } else {

            $d['rows'] = db()->query(
                'SELECT * FROM system_logs
                 ORDER BY id DESC
                 LIMIT 100'
            )->fetchAll();
        }
    }

    // ==========================================
    // USER DASHBOARD
    // ==========================================

    private static function userData(array &$d): void
    {
        $uid = (int)user()['id'];

        $stmt = db()->prepare(
            'SELECT COUNT(*) n FROM orders WHERE user_id = ?'
        );

        $stmt->execute([$uid]);
        $orders = $stmt->fetch()['n'];

        $stmt = db()->prepare(
            "SELECT COUNT(*) n
             FROM tickets t
             JOIN order_items oi ON oi.id = t.order_item_id
             JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = ?
             AND t.status = 'valid'"
        );

        $stmt->execute([$uid]);
        $tickets = $stmt->fetch()['n'];

        $d['stats'] = [
            ['My orders', $orders],
            ['Active tickets', $tickets],
            [
                'Upcoming events',
                db()->query(
                    "SELECT COUNT(*) n
                     FROM events
                     WHERE status = 'published'
                     AND start_date > NOW()"
                )->fetch()['n']
            ]
        ];

        if ($d['section'] === 'tickets') {

            $stmt = db()->prepare(
                "SELECT
                    t.ticket_number,
                    t.status,
                    e.title,
                    e.start_date,
                    e.venue
                 FROM tickets t
                 JOIN order_items oi ON oi.id = t.order_item_id
                 JOIN orders o ON o.id = oi.order_id
                 JOIN ticket_types tt ON tt.id = oi.ticket_type_id
                 JOIN events e ON e.id = tt.event_id
                 WHERE o.user_id = ?
                 ORDER BY t.id DESC"
            );

            $stmt->execute([$uid]);
            $d['rows'] = $stmt->fetchAll();

        } else {

            $stmt = db()->prepare(
                'SELECT
                    o.*,
                    p.status payment_status,
                    p.method
                 FROM orders o
                 LEFT JOIN payments p ON p.order_id = o.id
                 WHERE o.user_id = ?
                 ORDER BY o.id DESC'
            );

            $stmt->execute([$uid]);
            $d['rows'] = $stmt->fetchAll();
        }

        $d['events'] = db()->query(
            "SELECT
                id,
                title,
                slug,
                city,
                venue,
                start_date
             FROM events
             WHERE status = 'published'
             AND start_date > NOW()
             ORDER BY start_date
             LIMIT 6"
        )->fetchAll();
    }
}
