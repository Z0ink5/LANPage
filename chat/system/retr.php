<?php
require_once __DIR__ . '/common.php';

$user = chat_require_user();
chat_touch_user((int)$user['id']);

$db = chat_db();
global $chat_history_limit;
$limit = max(20, min(500, (int)($chat_history_limit ?? 200)));
$query = $db->prepare('SELECT m.id, m.body, m.created_at, u.name, u.color, u.id AS user_id, u.is_admin
        FROM messages m
        INNER JOIN users u ON u.id = m.user_id
        WHERE m.is_deleted = 0
        ORDER BY m.id DESC
        LIMIT :limit');
$query->bindValue(':limit', $limit, SQLITE3_INTEGER);
$result = $query->execute();
$messages = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $messages[] = [
        'id' => (int)$row['id'],
        'body' => $row['body'],
        'created_at' => (int)$row['created_at'],
        'user' => [
            'id' => (int)$row['user_id'],
            'name' => $row['name'],
            'color' => $row['color'],
            'is_admin' => (int)$row['is_admin'] === 1
        ]
    ];
}
$messages = array_reverse($messages);

chat_json_response([
    'status' => 'ok',
    'messages' => $messages
]);
