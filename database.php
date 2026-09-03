<?php
$db = new SQLite3('/tmp/bots.db');

// Create user_bots table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS user_bots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    bot_token TEXT UNIQUE NOT NULL
)");

// Create chat_history table to store conversation context (Last 5 messages)
$db->exec("CREATE TABLE IF NOT EXISTS chat_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bot_token TEXT NOT NULL,
    chat_id TEXT NOT NULL,
    role TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

function save_bot($user_id, $token) {
    global $db;
    $stmt = $db->prepare("INSERT OR IGNORE INTO user_bots (user_id, bot_token) VALUES (:user_id, :token)");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    return $stmt->execute();
}

function save_chat_message($bot_token, $chat_id, $role, $message) {
    global $db;
    $stmt = $db->prepare("INSERT INTO chat_history (bot_token, chat_id, role, message) VALUES (:token, :chat_id, :role, :msg)");
    $stmt->bindValue(':token', $bot_token, SQLITE3_TEXT);
    $stmt->bindValue(':chat_id', $chat_id, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
    $stmt->execute();

    // Auto cleanup old messages (Keep only last 10 messages = 5 pairs)
    $db->exec("DELETE FROM chat_history WHERE id NOT IN (
        SELECT id FROM chat_history 
        WHERE bot_token = '{$bot_token}' AND chat_id = '{$chat_id}' 
        ORDER BY id DESC LIMIT 10
    ) AND bot_token = '{$bot_token}' AND chat_id = '{$chat_id}'");
}

function get_chat_history($bot_token, $chat_id) {
    global $db;
    $stmt = $db->prepare("SELECT role, message FROM chat_history WHERE bot_token = :token AND chat_id = :chat_id ORDER BY id ASC");
    $stmt->bindValue(':token', $bot_token, SQLITE3_TEXT);
    $stmt->bindValue(':chat_id', $chat_id, SQLITE3_TEXT);
    $result = $stmt->execute();

    $history = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $history[] = [
            "role" => $row['role'],
            "content" => $row['message']
        ];
    }
    return $history;
}
