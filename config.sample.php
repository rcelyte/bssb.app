<?php

use Instasell\Instarecord\Instarecord;

global $config;

/**
 * Sentry error logging
 */
if (!defined('IS_TEST_RUN')) {
    Sentry\init(['dsn' => 'SET_ME']);
}

/**
 * cache_enabled, boolean
 *  → If enabled: Enable compilation and response caching behavior (for production).
 */
$config['cache_enabled'] = true;

/**
 * response_cache_enabled, boolean
 *  → If enabled: Enable response caching behavior.
 */
$config['response_cache_enabled'] = true;

/**
 * hashids_salt, string
 *  → The salt used to calculate hashids.
 */
$config['hashids_salt'] = "🧂";

/**
 * Instarecord configuration
 */
$dbConfig = Instarecord::config();
$dbConfig->unix_socket = "/var/lib/mysql/mysql.sock";
$dbConfig->username = "user";
$dbConfig->password = "password";
$dbConfig->database = "bssb";