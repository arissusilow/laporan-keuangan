<?php

return [
    'backup_path' => env('BACKUP_PATH', storage_path('app/private/backups')),
    'backup_retention_daily' => (int) env('BACKUP_RETENTION_DAILY', 14),
    'initial_user_password' => env('INITIAL_USER_PASSWORD'),
];
