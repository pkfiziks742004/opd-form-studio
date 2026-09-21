<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

function time_elapsed_string(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d === 1 ? 'Yesterday' : $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}

function purge_expired_notifications(PDO $pdo): void {
    try {
        $pdo->exec("DELETE FROM notifications WHERE created_at < (NOW() - INTERVAL 24 HOUR)");
    } catch (Throwable $e) {
        // Silently catch if table or privilege issues
    }
}

try {
    $pdo = db();

    // Auto-clean: Purge any notifications older than 24 hours on every API touch
    purge_expired_notifications($pdo);

    if ($action === 'fetch') {
        $userId = (int)$user['id'];
        $role = $user['role'];
        $isAdmin = ($role === 'admin');

        // Role condition: admin sees all; reception sees 'all' or 'reception'
        $roleFilter = $isAdmin ? "1=1" : "(n.target_role = 'all' OR n.target_role = 'reception')";

        // Fetch unread count (only within active 24-hour window)
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications n
            LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.user_id = ?
            WHERE $roleFilter AND r.id IS NULL AND n.created_at >= (NOW() - INTERVAL 24 HOUR)
        ");
        $stmtCount->execute([$userId]);
        $unreadCount = (int)$stmtCount->fetchColumn();

        // Fetch notifications list (only within active 24-hour window)
        $stmtList = $pdo->prepare("
            SELECT n.id, n.title, n.message, n.type, n.target_role, n.created_at,
                   u.name AS sender_name,
                   (CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) AS is_read
            FROM notifications n
            LEFT JOIN users u ON u.id = n.created_by
            LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.user_id = ?
            WHERE $roleFilter AND n.created_at >= (NOW() - INTERVAL 24 HOUR)
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmtList->execute([$userId]);
        $rows = $stmtList->fetchAll();

        $notifications = array_map(function($r) use ($isAdmin) {
            return [
                'id' => (int)$r['id'],
                'title' => (string)$r['title'],
                'message' => (string)$r['message'],
                'type' => (string)$r['type'],
                'target_role' => (string)$r['target_role'],
                'created_at' => (string)$r['created_at'],
                'time_ago' => time_elapsed_string($r['created_at']),
                'sender_name' => (string)($r['sender_name'] ?: 'Administrator'),
                'is_read' => (bool)$r['is_read'],
                'can_delete' => $isAdmin
            ];
        }, $rows);

        echo json_encode([
            'success' => true,
            'is_admin' => $isAdmin,
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
        exit;
    }

    if ($action === 'send') {
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only administrators can broadcast notifications.']);
            exit;
        }

        verify_csrf();

        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'info');
        $targetRole = trim($_POST['target_role'] ?? 'reception');

        if ($title === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Notification title cannot be blank.']);
            exit;
        }

        if ($message === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Notification message cannot be blank.']);
            exit;
        }

        if (!in_array($type, ['info', 'alert', 'urgent'], true)) {
            $type = 'info';
        }

        if (!in_array($targetRole, ['all', 'reception'], true)) {
            $targetRole = 'reception';
        }

        $stmt = $pdo->prepare("
            INSERT INTO notifications (title, message, type, target_role, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $message, $type, $targetRole, $user['id']]);
        $notifId = (int)$pdo->lastInsertId();

        // Audit log
        try {
            $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                VALUES (?, 'NOTIF_BROADCAST', 'notification', ?, ?)
            ")->execute([
                $user['id'],
                $notifId,
                json_encode(['title' => $title, 'type' => $type, 'target' => $targetRole])
            ]);
        } catch (Throwable $ignore) {}

        echo json_encode([
            'success' => true,
            'message' => 'Notification successfully broadcasted to reception.',
            'id' => $notifId
        ]);
        exit;
    }

    if ($action === 'mark_read') {
        verify_csrf();

        $userId = (int)$user['id'];
        $role = $user['role'];
        $id = $_POST['id'] ?? 'all';

        if ($id === 'all') {
            $roleFilter = ($role === 'admin') ? "1=1" : "(target_role = 'all' OR target_role = 'reception')";
            $pdo->exec("
                INSERT IGNORE INTO notification_reads (notification_id, user_id)
                SELECT id, {$userId} FROM notifications
                WHERE $roleFilter
            ");
        } else {
            $notifId = (int)$id;
            if ($notifId > 0) {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO notification_reads (notification_id, user_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$notifId, $userId]);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied. Only administrators can delete notifications.']);
            exit;
        }

        verify_csrf();
        $targetId = $_POST['id'] ?? '';

        if ($targetId === 'all') {
            $pdo->exec("DELETE FROM notifications");
            try {
                $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                    VALUES (?, 'NOTIF_CLEAR_ALL', 'notification', 0, ?)
                ")->execute([
                    $user['id'],
                    json_encode(['cleared_by' => $user['name'] ?? 'Admin'])
                ]);
            } catch (Throwable $ignore) {}

            echo json_encode(['success' => true, 'message' => 'All announcements have been cleared.']);
            exit;
        }

        $notifId = (int)$targetId;
        if ($notifId > 0) {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notifId]);

            try {
                $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                    VALUES (?, 'NOTIF_DELETE', 'notification', ?, ?)
                ")->execute([
                    $user['id'],
                    $notifId,
                    json_encode(['deleted_by' => $user['name'] ?? 'Admin'])
                ]);
            } catch (Throwable $ignore) {}

            echo json_encode(['success' => true, 'message' => 'Notification deleted successfully.']);
            exit;
        }

        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid notification ID provided.']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action requested.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
