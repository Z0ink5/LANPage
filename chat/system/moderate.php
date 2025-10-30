<?php
require_once __DIR__ . '/common.php';

$admin = chat_require_admin();
$data = array_merge($_POST ?? [], chat_read_json());
$action = $data['action'] ?? '';
$db = chat_db();

switch ($action) {
    case 'delete_message':
        $messageId = isset($data['message_id']) ? (int)$data['message_id'] : 0;
        if ($messageId <= 0) {
            chat_json_response(['status' => 'error', 'message' => 'Invalid message id'], 422);
        }
        $stmt = $db->prepare('UPDATE messages SET is_deleted = 1, deleted_at = :deleted WHERE id = :id');
        $stmt->bindValue(':deleted', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $messageId, SQLITE3_INTEGER);
        $stmt->execute();
        chat_json_response(['status' => 'ok']);
        break;
    case 'timeout_user':
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $minutes = isset($data['minutes']) ? max(1, (int)$data['minutes']) : 10;
        if ($userId <= 0) {
            chat_json_response(['status' => 'error', 'message' => 'Invalid user id'], 422);
        }
        $stmt = $db->prepare('UPDATE users SET ban_expires = :ban WHERE id = :id');
        $stmt->bindValue(':ban', time() + ($minutes * 60), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        chat_json_response(['status' => 'ok']);
        break;
    case 'ban_user':
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        if ($userId <= 0) {
            chat_json_response(['status' => 'error', 'message' => 'Invalid user id'], 422);
        }
        $stmt = $db->prepare('UPDATE users SET ban_expires = :ban WHERE id = :id');
        $stmt->bindValue(':ban', time() + (365 * 24 * 60 * 60), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        chat_json_response(['status' => 'ok']);
        break;
    case 'unban_user':
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        if ($userId <= 0) {
            chat_json_response(['status' => 'error', 'message' => 'Invalid user id'], 422);
        }
        $stmt = $db->prepare('UPDATE users SET ban_expires = NULL WHERE id = :id');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        chat_json_response(['status' => 'ok']);
        break;
    default:
        chat_json_response(['status' => 'error', 'message' => 'Unknown action'], 400);
}
