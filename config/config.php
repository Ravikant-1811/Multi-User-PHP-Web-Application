<?php
// Base URL configuration
// Development: '/MUPWA/' | Production: '/'
define('BASE_URL', '/MUPWA/');

// put your database production connection here
$server = 'localhost';
$username = 'root';
$password = '';
$database = 'asgroup';



try {
    // I have used PDO for database connection
    $conn = new PDO("mysql:host=$server;dbname=$database", $username, $password);
    // and set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Ensure native prepared statements (needed for LIMIT/OFFSET integers)
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // check the connection
    // echo "Connected successfully";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}


// Helper function to generate URLs with BASE_URL prefix
function url($path = '')
{
    // Remove leading slash if present to avoid double slashes
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}