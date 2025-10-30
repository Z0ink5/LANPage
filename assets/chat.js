(function($) {
    'use strict';

    var state = (window.chatConfig && window.chatConfig.state) ? window.chatConfig.state : {};
    var strings = (window.chatConfig && window.chatConfig.strings) ? window.chatConfig.strings : {};
    var pollingInterval = null;
    var onlineInterval = null;
    var pingInterval = null;

    function setLoginFeedback(message, isError) {
        var $feedback = $('#chat-login-feedback');
        if (!message) {
            $feedback.hide();
            return;
        }
        $feedback.text(message);
        if (isError) {
            $feedback.removeClass('text-success').addClass('text-danger');
        } else {
            $feedback.removeClass('text-danger').addClass('text-success');
        }
        $feedback.show();
    }

    function setSendFeedback(message) {
        var $feedback = $('#chat-send-feedback');
        if (!message) {
            $feedback.hide();
            return;
        }
        $feedback.text(message).show();
    }

    function formatTime(timestamp) {
        var date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function renderMessages(messages) {
        var $container = $('#chat-messages');
        var isAtBottom = ($container.scrollTop() + $container.innerHeight() + 10) >= $container[0].scrollHeight;
        $container.empty();
        messages.forEach(function(message) {
            var $item = $('<div/>').addClass('list-group-item');
            var $header = $('<div/>').addClass('d-flex justify-content-between align-items-center');
            var $name = $('<strong/>').text(message.user.name + ':');
            $name.css('color', message.user.color || '#ffffff');
            if (message.user.is_admin) {
                $name.append($('<span/>').addClass('label label-info ml-2').text('admin'));
            }
            var $time = $('<small/>').addClass('text-muted').text(formatTime(message.created_at));
            $header.append($name).append($time);
            var $body = $('<div/>').addClass('chat-message-body').text(message.body);
            $item.append($header).append($body);

            if (state.isAdmin) {
                var $tools = $('<div/>').addClass('btn-group btn-group-xs pull-right').attr('role', 'group');
                var $delete = $('<button/>').addClass('btn btn-danger').attr('type', 'button').text(strings.moderation_delete || 'Delete');
                var $timeout = $('<button/>').addClass('btn btn-warning').attr('type', 'button').text(strings.moderation_timeout || 'Timeout');
                var $ban = $('<button/>').addClass('btn btn-default').attr('type', 'button').text(strings.moderation_ban || 'Ban');
                $delete.on('click', function() { moderate('delete_message', { message_id: message.id }); });
                $timeout.on('click', function() { moderate('timeout_user', { user_id: message.user.id, minutes: 10 }); });
                $ban.on('click', function() { moderate('ban_user', { user_id: message.user.id }); });
                $tools.append($delete, $timeout, $ban);
                $item.append($('<div/>').addClass('clearfix').append($tools));
            }

            $container.append($item);
        });
        if (isAtBottom) {
            $container.scrollTop($container[0].scrollHeight);
        }
    }

    function renderUsers(users) {
        var $container = $('#chat-online');
        $container.empty();
        if (!users.length) {
            $('<li/>').addClass('list-group-item text-muted').text(strings.nobody_online || 'Nobody online').appendTo($container);
            return;
        }
        users.forEach(function(user) {
            var $item = $('<li/>').addClass('list-group-item');
            var $name = $('<span/>').text(user.name);
            $name.css('color', user.color || '#ffffff');
            if (user.is_admin) {
                $name.append($('<span/>').addClass('label label-info ml-2').text('admin'));
            }
            $item.append($name);
            $container.append($item);
        });
    }

    function updateBanNotice(banExpires) {
        var $notice = $('#chat-ban-notice');
        if (!state.isAuthenticated) {
            $notice.hide();
            return false;
        }
        if (!banExpires || banExpires <= Math.floor(Date.now() / 1000)) {
            $notice.hide();
            return false;
        }
        var until = new Date(banExpires * 1000).toLocaleString();
        var template = strings.banned_until || 'You are banned until %s.';
        $notice.text(template.replace('%s', until)).show();
        return true;
    }

    function refreshState(newState) {
        state = $.extend({}, state, newState || {});
        if (state.isAdmin) {
            $('#chat-admin-help').show();
        }
        if (state.displayName) {
            $('#chat-name').val(state.displayName);
        }
        if (state.color) {
            $('#chat-color').val(state.color);
        }
        var banned = updateBanNotice(state.banExpires);
        var canSend = state.isAuthenticated && !banned;
        $('#chat-message').prop('disabled', !canSend);
        $('#chat-send-form button').prop('disabled', !canSend);
    }

    function moderate(action, payload) {
        $.ajax({
            url: 'chat/system/moderate.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify($.extend({ action: action }, payload || {}))
        }).done(function() {
            loadMessages();
            loadUsers();
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            setSendFeedback(response.message || strings.moderation_failed || 'Moderation failed');
        });
    }

    function loadMessages() {
        if (!state.isAuthenticated) {
            return;
        }
        $.getJSON('chat/system/retr.php').done(function(response) {
            if (response && response.status === 'ok') {
                renderMessages(response.messages || []);
            }
        });
    }

    function loadUsers() {
        if (!state.isAuthenticated) {
            return;
        }
        $.getJSON('chat/system/wwo.php').done(function(response) {
            if (response && response.status === 'ok') {
                renderUsers(response.users || []);
            }
        });
    }

    function ping() {
        if (!state.isAuthenticated) {
            return;
        }
        $.getJSON('chat/system/ping.php').done(function(response) {
            if (response && response.status === 'ok') {
                refreshState({
                    isAuthenticated: true,
                    isAdmin: response.user && response.user.is_admin,
                    banExpires: response.user ? response.user.ban_expires : null
                });
            }
        });
    }

    $('#chat-login-form').on('submit', function(event) {
        event.preventDefault();
        var payload = {
            name: $('#chat-name').val(),
            color: $('#chat-color').val(),
            adminToken: $('#chat-admin-token').val()
        };
        $.ajax({
            url: 'chat/system/login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload)
        }).done(function(response) {
            setLoginFeedback(strings.saved || 'Saved', false);
            $('#chat-admin-token').val('');
            refreshState({
                isAuthenticated: true,
                isAdmin: response.user && response.user.is_admin,
                displayName: response.user ? response.user.name : payload.name,
                color: response.user ? response.user.color : payload.color,
                banExpires: response.user ? response.user.ban_expires : null
            });
            startPolling();
            loadMessages();
            loadUsers();
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            setLoginFeedback(response.message || 'Unable to save', true);
            if (response.banExpires) {
                refreshState({
                    isAuthenticated: true,
                    banExpires: response.banExpires
                });
            }
        });
    });

    $('#chat-send-form').on('submit', function(event) {
        event.preventDefault();
        if (!state.isAuthenticated) {
            setSendFeedback(strings.nickname_required || 'Please set your nickname first.');
            return;
        }
        var message = $('#chat-message').val();
        if (!message.trim()) {
            setSendFeedback(strings.message_empty || 'Message cannot be empty');
            return;
        }
        $.ajax({
            url: 'chat/system/send.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ message: message })
        }).done(function() {
            $('#chat-message').val('');
            setSendFeedback('');
            loadMessages();
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            setSendFeedback(response.message || strings.send_failed || 'Failed to send message');
            if (response.ban_expires || response.banExpires) {
                refreshState({ banExpires: response.ban_expires || response.banExpires });
            }
        });
    });

    function startPolling() {
        if (!state.isAuthenticated) {
            return;
        }
        if (!pollingInterval) {
            pollingInterval = window.setInterval(loadMessages, 5000);
        }
        if (!onlineInterval) {
            onlineInterval = window.setInterval(loadUsers, 10000);
        }
        if (!pingInterval) {
            pingInterval = window.setInterval(ping, 15000);
        }
    }

    refreshState(state);

    if (state.isAuthenticated) {
        startPolling();
        loadMessages();
        loadUsers();
        ping();
    }

    $(document).on('visibilitychange', function() {
        if (!state.isAuthenticated) {
            return;
        }
        if (!document.hidden) {
            loadMessages();
            loadUsers();
            ping();
        }
    });

})(jQuery);
