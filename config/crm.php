<?php
declare(strict_types=1);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "csmpl@12";
$DB_NAME = "crm_project";
$DB_PORT = 3306;

function db_connect(): mysqli
{
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    if ($mysqli->connect_error) {
        throw new RuntimeException("Database connection failed.");
    }
    $mysqli->set_charset("utf8mb4");
    return $mysqli;
}
