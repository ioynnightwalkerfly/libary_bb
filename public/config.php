<?php
// Database configuration for libary_bb
const DB_HOST = 'localhost';
const DB_NAME = 'libary_bb';
const DB_USER = 'root';
const DB_PASS = '';

function get_db_connection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
