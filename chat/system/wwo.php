<?php
require_once __DIR__ . '/common.php';

$user = chat_require_user();
chat_touch_user((int)$user['id']);

$db = chat_db();
$threshold = time() - 300;
$stmt = $db->prepare('SELECT id, name, color, is_admin FROM users WHERE last_active >= :threshold AND (ban_expires IS NULL OR ban_expires <= :now) ORDER BY name COLLATE NOCASE');
$stmt->bindValue(':threshold', $threshold, SQLITE3_INTEGER);
$stmt->bindValue(':now', time(), SQLITE3_INTEGER);
$result = $stmt->execute();
$users = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'color' => $row['color'],
        'is_admin' => (int)$row['is_admin'] === 1
    ];
}

chat_json_response([
    'status' => 'ok',
    'users' => $users
]);
