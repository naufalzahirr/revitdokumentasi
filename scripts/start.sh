#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../public"
exec php -d upload_max_filesize=6M -d post_max_size=128M -d max_file_uploads=21 \
    -S "127.0.0.1:${1:-8005}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
