<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Feature Toggles
    |--------------------------------------------------------------------------
    */
    
    'new_device_alert_enabled' => env('NEW_DEVICE_ALERT_ENABLED', true),
    'new_location_alert_enabled' => env('NEW_LOCATION_ALERT_ENABLED', true),
    'activity_log_enabled' => env('ACTIVITY_LOG_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Login Security
    |--------------------------------------------------------------------------
    */
    
    'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
    'lockout_duration' => env('LOCKOUT_DURATION', 300), // 5 minutes in seconds
    
    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    */
    
    'max_active_sessions' => env('MAX_ACTIVE_SESSIONS', 5),
    'session_timeout_days' => env('SESSION_TIMEOUT_DAYS', 30),
];
