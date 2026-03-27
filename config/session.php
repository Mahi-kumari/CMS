<?php
declare(strict_types=1);

function start_secure_session(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    // Ensure cookies are sent to both /CRM/Admin and /CRM/Dashboard
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/CRM',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name($name);
    session_start();
}

function start_secure_session_from_role(?string $role = null): void
{
    $role = strtolower((string)$role);
    $name = 'CRM_USERSESSID';
    if ($role === 'admin') {
        $name = 'CRM_ADMINSESSID';
    } elseif ($role === 'user') {
        $name = 'CRM_USERSESSID';
    } elseif (isset($_COOKIE['CRM_USERSESSID'])) {
        $name = 'CRM_USERSESSID';
    } elseif (isset($_COOKIE['CRM_ADMINSESSID'])) {
        $name = 'CRM_ADMINSESSID';
    }
    start_secure_session($name);
}
