<?php
mb_internal_encoding("UTF-8");
error_reporting(E_ALL ^  E_NOTICE); 

if (file_exists(stream_resolve_include_path('config.php'))) {
	include_once('config.php');
}
else if (file_exists(stream_resolve_include_path('config.sample.php'))) {
	include_once('config.sample.php');
}

// Database Connections
if ($enable_stats == true) { 
	$stats_db = new SQLite3('db/lan.db');
	$game_db = new SQLite3('db/game.db');
};
 
if ($enable_competition == true) {
        $competition_db = new SQLite3('db/competition.db');
};

if ($enable_chat == true) {
        $chat_db = new SQLite3('db/chat.db');
        $chat_db->exec('PRAGMA foreign_keys = ON');
        $chat_db->exec('CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                color TEXT DEFAULT "#ffffff",
                is_admin INTEGER NOT NULL DEFAULT 0,
                last_active INTEGER NOT NULL,
                ban_expires INTEGER DEFAULT NULL
        )');
        $chat_db->exec('CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                body TEXT NOT NULL,
                created_at INTEGER NOT NULL,
                is_deleted INTEGER NOT NULL DEFAULT 0,
                deleted_at INTEGER DEFAULT NULL,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )');
};

?>