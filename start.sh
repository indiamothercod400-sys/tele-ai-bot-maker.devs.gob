#!/bin/bash

# Default port assigned by Render, fallback to 8080
PORT="${PORT:-8080}"

# Register master bot webhook
php set_webhook.php

# Start built-in PHP HTTP Server
echo "Starting PHP Web Server on port $PORT..."
php -S 0.0.0.0:$PORT index.php
