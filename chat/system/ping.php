<?php
require_once __DIR__ . '/common.php';

$user = chat_require_user();
chat_touch_user((int)$user['id']);

chat_json_response([
    'status' => 'ok',
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'color' => $user['color'],
        'is_admin' => (int)$user['is_admin'] === 1,
        'ban_expires' => $user['ban_expires']
    ]
]);
