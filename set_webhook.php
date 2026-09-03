<?php
$master_token = getenv('MAIN_BOT_TOKEN');
$render_url = getenv('RENDER_EXTERNAL_URL');

if (!$master_token || !$render_url) {
    echo "Missing environment variables: MAIN_BOT_TOKEN or RENDER_EXTERNAL_URL\n";
    exit;
}

$webhook_url = rtrim($render_url, '/') . "/bot/master";
$api_endpoint = "https://api.telegram.org/bot{$master_token}/setWebhook?url=" . urlencode($webhook_url);

$res = file_get_contents($api_endpoint);
echo "Master Bot Webhook Response: " . $res . "\n";
