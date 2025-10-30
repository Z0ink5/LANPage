<?php
require_once __DIR__ . '/common.php';

$data = array_merge($_POST ?? [], chat_read_json());
$name = trim($data['name'] ?? '');
$color = trim($data['color'] ?? '#ffffff');
$adminToken = trim($data['adminToken'] ?? '');

if ($name === '') {
    chat_json_response(['status' => 'error', 'message' => 'Display name required'], 422);
}

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#ffffff';
}

$db = chat_db();
$userId = chat_current_user_id();
$now = time();

if ($userId === null) {
    $stmt = $db->prepare('INSERT INTO users (name, color, is_admin, last_active) VALUES (:name, :color, :admin, :last_active)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':color', $color, SQLITE3_TEXT);
    $stmt->bindValue(':admin', 0, SQLITE3_INTEGER);
    $stmt->bindValue(':last_active', $now, SQLITE3_INTEGER);
    $stmt->execute();
    $userId = (int)$db->lastInsertRowID();
    $_SESSION['chat_user_id'] = $userId;
} else {
    $stmt = $db->prepare('UPDATE users SET name = :name, color = :color WHERE id = :id');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':color', $color, SQLITE3_TEXT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
}

chat_touch_user($userId);
$user = chat_fetch_user($userId);

if ($adminToken !== '' && isset($chat_admin_tokens) && is_array($chat_admin_tokens)) {
    if (in_array($adminToken, $chat_admin_tokens, true)) {
        $stmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE id = :id');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        $user['is_admin'] = 1;
    }
}

if (chat_is_banned($user)) {
    chat_json_response([
        'status' => 'error',
        'message' => 'Banned',
        'banExpires' => $user['ban_expires']
    ], 403);
}

chat_json_response([
    'status' => 'ok',
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'color' => $user['color'],
        'is_admin' => (int)$user['is_admin'] === 1,
        'ban_expires' => $user['ban_expires']
    ]
]);
