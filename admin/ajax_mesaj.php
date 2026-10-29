<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi']);
    exit;
}

if (!verifyCsrf()) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Oturum doğrulaması başarısız']);
    exit;
}

$action = (string) ($_POST['action'] ?? '');

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($id === false || $id === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz ID']);
    exit;
}

try {
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);

        $unread = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

        echo json_encode(['success' => true, 'unread_count' => $unread]);
    } elseif ($action === 'read') {
        $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
        $stmt->execute([$id]);

        $unread = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

        echo json_encode(['success' => true, 'unread_count' => $unread]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Bilinmeyen işlem']);
    }
} catch (PDOException $e) {
    error_log('[GYMTY] Mesaj işlemi hatası: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası']);
}
