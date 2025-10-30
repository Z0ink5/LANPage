<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rootDir = dirname(__DIR__, 2);
$configLoaded = false;
if (file_exists($rootDir . '/config.php')) {
    require_once $rootDir . '/config.php';
    $configLoaded = true;
}
if (!$configLoaded && file_exists($rootDir . '/config.sample.php')) {
    require_once $rootDir . '/config.sample.php';
    $configLoaded = true;
}
if (!$configLoaded) {
    http_response_code(500);
    die('Configuration missing');
}

require_once $rootDir . '/db/db.php';

if (!isset($enable_chat) || $enable_chat !== true) {
    http_response_code(503);
    die('Chat disabled');
}

if (!isset($chat_history_limit) || !is_int($chat_history_limit)) {
    $chat_history_limit = 200;
}

/**
 * @return SQLite3
 */
function chat_db(): SQLite3
{
    global $chat_db, $rootDir;
    if (!isset($chat_db)) {
        $chat_db = new SQLite3($rootDir . '/db/chat.db');
        $chat_db->exec('PRAGMA foreign_keys = ON');
    }
    return $chat_db;
}

function chat_current_user_id(): ?int
{
    return isset($_SESSION['chat_user_id']) ? (int)$_SESSION['chat_user_id'] : null;
}

function chat_fetch_user(?int $userId = null): ?array
{
    $userId = $userId ?? chat_current_user_id();
    if ($userId === null) {
        return null;
    }
    $db = chat_db();
    $stmt = $db->prepare('SELECT id, name, color, is_admin, last_active, ban_expires FROM users WHERE id = :id');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if (!$row) {
        unset($_SESSION['chat_user_id']);
        return null;
    }
    return $row;
}

function chat_is_admin(?array $user = null): bool
{
    $user = $user ?? chat_fetch_user();
    return $user !== null && (int)$user['is_admin'] === 1;
}

function chat_is_banned(?array $user = null): bool
{
    $user = $user ?? chat_fetch_user();
    if ($user === null) {
        return false;
    }
    if ($user['ban_expires'] === null) {
        return false;
    }
    return (int)$user['ban_expires'] > time();
}

function chat_touch_user(int $userId): void
{
    $db = chat_db();
    $stmt = $db->prepare('UPDATE users SET last_active = :ts WHERE id = :id');
    $stmt->bindValue(':ts', time(), SQLITE3_INTEGER);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
}

function chat_cleanup_old_messages(): void
{
    global $deleteOldComments, $deleteOldCommentsAfter;
    if (empty($deleteOldComments)) {
        return;
    }
    $threshold = strtotime($deleteOldCommentsAfter ?? '-24 hours');
    if ($threshold === false) {
        return;
    }
    $db = chat_db();
    $stmt = $db->prepare('DELETE FROM messages WHERE created_at < :threshold');
    $stmt->bindValue(':threshold', $threshold, SQLITE3_INTEGER);
    $stmt->execute();
}

function chat_read_json(): array
{
    $contents = file_get_contents('php://input');
    if ($contents === false || $contents === '') {
        return [];
    }
    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : [];
}

function chat_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function chat_require_user(): array
{
    $user = chat_fetch_user();
    if ($user === null) {
        chat_json_response(['status' => 'error', 'message' => 'Not authenticated'], 401);
    }
    return $user;
}

function chat_require_admin(): array
{
    $user = chat_require_user();
    if (!chat_is_admin($user)) {
        chat_json_response(['status' => 'error', 'message' => 'Admin privileges required'], 403);
    }
    return $user;
}
