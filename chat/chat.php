<?php
require_once __DIR__ . '/system/common.php';

$currentUser = chat_fetch_user();
$state = [
    'isAuthenticated' => $currentUser !== null,
    'isAdmin' => $currentUser ? chat_is_admin($currentUser) : false,
    'banExpires' => $currentUser['ban_expires'] ?? null,
    'userId' => $currentUser['id'] ?? null,
    'displayName' => $currentUser['name'] ?? '',
    'color' => $currentUser['color'] ?? '#ffffff'
];

if ($currentUser !== null && chat_is_banned($currentUser)) {
    $state['isBanned'] = true;
}

$strings = [
    'moderation_delete' => $chat['moderation_delete'] ?? 'Delete',
    'moderation_timeout' => $chat['moderation_timeout'] ?? 'Timeout',
    'moderation_ban' => $chat['moderation_ban'] ?? 'Ban',
    'nobody_online' => $chat['nobody_online'] ?? 'Nobody online',
    'saved' => $chat['saved'] ?? 'Saved',
    'nickname_required' => $chat['nickname_required'] ?? 'Please set your nickname first.',
    'message_empty' => $chat['message_empty'] ?? 'Message cannot be empty',
    'moderation_failed' => $chat['moderation_failed'] ?? 'Moderation failed',
    'send_failed' => $chat['send_failed'] ?? 'Failed to send message',
    'banned_until' => $chat['banned_until'] ?? 'You are banned until %s.'
];
?>
<div class="container-fluid" id="<?php echo htmlspecialchars($nav['chat']); ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-header">
                <h1><?php echo htmlspecialchars($nav['chat']); ?></h1>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo htmlspecialchars($chat['join'] ?? 'Join chat'); ?></div>
                <div class="panel-body">
                    <form id="chat-login-form">
                        <div class="form-group">
                            <label for="chat-name"><?php echo htmlspecialchars($chat['nickname'] ?? 'Nickname'); ?></label>
                            <input type="text" class="form-control" id="chat-name" name="name" value="<?php echo htmlspecialchars($state['displayName']); ?>" maxlength="50" required>
                        </div>
                        <div class="form-group">
                            <label for="chat-color"><?php echo htmlspecialchars($chat['color'] ?? 'Name color'); ?></label>
                            <input type="color" class="form-control" id="chat-color" name="color" value="<?php echo htmlspecialchars($state['color']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="chat-admin-token"><?php echo htmlspecialchars($chat['admin_token'] ?? 'Admin token'); ?></label>
                            <input type="password" class="form-control" id="chat-admin-token" name="adminToken" autocomplete="off" placeholder="<?php echo htmlspecialchars($chat['admin_token_placeholder'] ?? 'Optional'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($chat['save'] ?? 'Save'); ?></button>
                        <div class="help-block small" id="chat-login-feedback" style="display:none;"></div>
                    </form>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo htmlspecialchars($chat['users_online'] ?? 'Users online'); ?></div>
                <ul class="list-group" id="chat-online" style="max-height: 260px; overflow-y: auto;"></ul>
            </div>
            <div class="panel panel-default" id="chat-admin-help"<?php if (!$state['isAdmin']) { echo ' style="display:none;"'; } ?>>
                <div class="panel-heading"><?php echo htmlspecialchars($chat['moderation'] ?? 'Moderation'); ?></div>
                <div class="panel-body">
                    <p class="small"><?php echo htmlspecialchars($chat['moderation_help'] ?? 'Use the buttons on messages to moderate the chat.'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo htmlspecialchars($chat['messages'] ?? 'Messages'); ?></div>
                <div class="panel-body">
                    <div id="chat-messages" class="list-group" style="max-height: 360px; overflow-y: auto;"></div>
                </div>
                <div class="panel-footer">
                    <form id="chat-send-form">
                        <div class="input-group">
                            <input type="text" class="form-control" id="chat-message" placeholder="<?php echo htmlspecialchars($chat['message_placeholder'] ?? 'Type a message...'); ?>" autocomplete="off" <?php echo $state['isAuthenticated'] ? '' : 'disabled'; ?>>
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-success" <?php echo $state['isAuthenticated'] ? '' : 'disabled'; ?>><?php echo htmlspecialchars($chat['send'] ?? 'Send'); ?></button>
                            </span>
                        </div>
                    </form>
                    <div class="text-danger small" id="chat-send-feedback" style="display:none;"></div>
                </div>
            </div>
            <div class="alert alert-warning" id="chat-ban-notice" style="display:none;"></div>
        </div>
    </div>
</div>
<script>
window.chatConfig = Object.assign({}, window.chatConfig || {}, <?php echo json_encode(['state' => $state, 'strings' => $strings], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>);
</script>
<?php
?>
