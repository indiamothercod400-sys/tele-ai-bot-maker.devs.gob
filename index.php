<?php
require_once 'database.php';

$master_token = getenv('MAIN_BOT_TOKEN');
$openrouter_key = getenv('OPENROUTER_API_KEY');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($uri, '/'));

if ($uri === '/' || $uri === '') {
    echo "⚡ Premium AI Bot Maker Engine is Running Smoothly!";
    exit;
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    echo "No update received";
    exit;
}

// Master Bot Handler
if (isset($path_parts[0]) && $path_parts[0] === 'bot' && isset($path_parts[1]) && $path_parts[1] === 'master') {
    handle_master_bot($update, $master_token);
    exit;
}

// User Bot Handler
if (isset($path_parts[0]) && $path_parts[0] === 'bot' && isset($path_parts[1])) {
    $user_token = $path_parts[1];
    handle_user_bot($update, $user_token, $openrouter_key);
    exit;
}

// ================= MASTER BOT UI LOGIC =================
function handle_master_bot($update, $master_token) {
    $api_url = "https://api.telegram.org/bot{$master_token}/";
    
    // Callback Queries (Inline Button Action)
    if (isset($update['callback_query'])) {
        $chat_id = $update['callback_query']['message']['chat']['id'];
        $message_id = $update['callback_query']['message']['message_id'];
        $data_code = $update['callback_query']['data'];

        if ($data_code === 'create_bot') {
            $text = "<b>✨ Create Your Own Custom AI Bot</b>\n\n"
                  . "<i>ধাপ ১:</i> Telegram এর <b>@BotFather</b> এ যান।\n"
                  . "<i>ধাপ ২:</i> একটি নতুন বট তৈরি করে তার <b>HTTP API Token</b> কপি করুন।\n"
                  . "<i>ধাপ ৩:</i> সেই টোকেনটি এখানে মেসেজ হিসেবে পেস্ট করে পাঠিয়ে দিন! 🚀\n\n"
                  . "👇 <b>নিচে আপনার Token টি লিখুন:</b>";
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']]
                ]
            ];

            edit_telegram_msg($api_url, $chat_id, $message_id, $text, $keyboard);
        }
        elseif ($data_code === 'main_menu') {
            send_master_home_screen($api_url, $chat_id, $message_id);
        }
        return;
    }

    // Direct Messages
    if (isset($update['message']['text'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = trim($update['message']['text']);

        if ($text === '/start') {
            send_master_home_screen($api_url, $chat_id);
        } 
        elseif (preg_match('/^[0-9]+:[a-zA-Z0-9_-]+$/', $text)) {
            // Save Token
            save_bot($chat_id, $text);
            
            // Auto Webhook Setup
            $server_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
            $target_webhook = "{$server_url}/bot/{$text}";
            file_get_contents("https://api.telegram.org/bot{$text}/setWebhook?url=" . urlencode($target_webhook));

            $success_msg = "🎉 <b>Congratulations! Your AI Bot is Live!</b>\n\n"
                         . "🤖 <b>Status:</b> 🟢 Active & Ready\n"
                         . "⚡ <b>Powered By:</b> OpenRouter Free Engine\n"
                         . "🧠 <b>Context Memory:</b> Enabled (Last 5 Messages)\n"
                         . "🌐 <b>Webhook:</b> Connected Automatically\n\n"
                         . "✨ আপনার টোকেন দেওয়া বটটি এখন সম্পূর্ণ প্রস্তুত! চ্যাটে গিয়ে কাজ শুরু করতে পারেন। 🚀";

            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '➕ Create Another Bot', 'callback_data' => 'create_bot']]
                ]
            ];

            send_telegram_msg($api_url, $chat_id, $success_msg, $keyboard);
        } 
        else {
            $error_msg = "❌ <b>Invalid Bot Token Format!</b>\n\n"
                       . "অনুগ্রহ করে <b>@BotFather</b> থেকে সঠিক Token নিয়ে এখানে পেস্ট করুন।\n"
                       . "<i>উদাহরণ:</i> <code>123456789:ABCdefGhIJKlmNoPQRstuVWXyz</code>";
            
            send_telegram_msg($api_url, $chat_id, $error_msg);
        }
    }
}

function send_master_home_screen($api_url, $chat_id, $message_id = null) {
    $text = "👑 <b>Welcome to AI Chatbot Creator Engine!</b>\n\n"
          . "⚡ আপনার কি নিজের একটি AI Assistant প্রয়োজন?\n"
          . "💎 কোন রকম কোডিং ছাড়াই বানিয়ে নিন আপনার নিজস্ব Telegram AI Bot!\n\n"
          . "<b>🔥 Features:</b>\n"
          . "• 🎭 OpenRouter AI Free Engine Integration\n"
          . "• ⚡ Context Memory (Remembers previous chats)\n"
          . "• 💻 Auto Code Copy Box Support\n"
          . "• ♾️ Create Unlimited Custom AI Bots\n\n"
          . "👇 শুরু করতে নিচের বাটনে চাপ দিন:";

    $keyboard = [
        'inline_keyboard' => [
            [['text' => '✨ Create Your Own AI Chatbot', 'callback_data' => 'create_bot']],
            [['text' => '🌐 Website / Hosting', 'url' => 'https://render.com']]
        ]
    ];

    if ($message_id) {
        edit_telegram_msg($api_url, $chat_id, $message_id, $text, $keyboard);
    } else {
        send_telegram_msg($api_url, $chat_id, $text, $keyboard);
    }
}

// ================= USER BOT LOGIC =================
function handle_user_bot($update, $user_token, $openrouter_key) {
    $api_url = "https://api.telegram.org/bot{$user_token}/";

    if (isset($update['message']['text'])) {
        $chat_id = $update['message']['chat']['id'];
        $user_text = trim($update['message']['text']);

        if ($user_text === '/start') {
            $welcome = "✨ <b>Hello! I am your personal AI Assistant.</b> 🤖\n\n"
                     . "আমি আপনাকে যে কোনো প্রশ্নের উত্তর দিতে এবং বিভিন্ন কোড তৈরিতে সাহায্য করতে পারি।\n\n"
                     . "💡 <i>আমাকে যেকোনো বিষয় নিয়ে প্রশ্ন বা কোড লিখতে বলুন!</i>";
            send_telegram_msg($api_url, $chat_id, $welcome);
            return;
        }

        // Get history (Last 5 message pairs)
        $history = get_chat_history($user_token, $chat_id);

        // Save current user message
        save_chat_message($user_token, $chat_id, 'user', $user_text);

        // Call OpenRouter with memory context
        $ai_reply = call_openrouter_with_memory($openrouter_key, $history, $user_text);

        // Save AI response to history
        save_chat_message($user_token, $chat_id, 'assistant', $ai_reply);

        // Format code blocks into Telegram HTML copy boxes
        $formatted_reply = format_telegram_code_blocks($ai_reply);

        send_telegram_msg($api_url, $chat_id, $formatted_reply);
    }
}

// ================= API & UTILITIES =================
function call_openrouter_with_memory($api_key, $history, $latest_prompt) {
    $url = "https://openrouter.ai/api/v1/chat/completions";

    $messages = [
        ["role" => "system", "content" => "You are a helpful, polite, and accurate AI assistant. Format any code clearly using markdown code blocks with triple backticks."]
    ];

    // Append context memory (past 5 messages)
    foreach ($history as $msg) {
        $messages[] = $msg;
    }

    // Append latest prompt
    $messages[] = ["role" => "user", "content" => $latest_prompt];

    $data = [
        "model" => "openrouter/free",
        "messages" => $messages
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json",
        "HTTP-Referer: https://render.com",
        "X-Title: Premium AI Bot Maker"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? "⚠️ দুঃখিত, এই মুহূর্তে উত্তর তৈরি করতে সমস্যা হচ্ছে। কিছুক্ষণ পর আবার চেষ্টা করুন।";
}

// Converts Markdown Code Blocks ```code``` to Telegram HTML <pre><code>code</code></pre> for easy copy
function format_telegram_code_blocks($text) {
    // Escape standard HTML characters first
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Pattern to catch triple backtick code blocks
    $pattern = '/```(?:[a-zA-Z0-9_-]+)?\s*([\s\S]*?)```/';
    
    $text = preg_replace_callback($pattern, function($matches) {
        $code = trim($matches[1]);
        return "<pre><code>" . $code . "</code></pre>";
    }, $text);

    return $text;
}

function send_telegram_msg($api_url, $chat_id, $text, $keyboard = null) {
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode($keyboard);
    }

    $ch = curl_init($api_url . "sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    curl_close($ch);
}

function edit_telegram_msg($api_url, $chat_id, $message_id, $text, $keyboard = null) {
    $payload = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode($keyboard);
    }

    $ch = curl_init($api_url . "editMessageText");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    curl_close($ch);
}
