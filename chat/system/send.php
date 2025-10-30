<?php
require_once __DIR__ . '/common.php';

$user = chat_require_user();
if (chat_is_banned($user)) {
    chat_json_response([
        'status' => 'error',
        'message' => 'Banned',
        'ban_expires' => $user['ban_expires']
    ], 403);
}

$data = array_merge($_POST ?? [], chat_read_json());
$message = trim($data['message'] ?? $data['say'] ?? '');

if ($message === '') {
    chat_json_response(['status' => 'error', 'message' => 'Empty message'], 422);
}

if (mb_strlen($message) > 400) {
    $message = mb_substr($message, 0, 400);
}

$db = chat_db();
$statement = $db->prepare('INSERT INTO messages (user_id, body, created_at) VALUES (:user, :body, :created_at)');
$statement->bindValue(':user', $user['id'], SQLITE3_INTEGER);
$statement->bindValue(':body', $message, SQLITE3_TEXT);
$statement->bindValue(':created_at', time(), SQLITE3_INTEGER);
$statement->execute();

chat_touch_user((int)$user['id']);
chat_cleanup_old_messages();

chat_json_response(['status' => 'ok']);
