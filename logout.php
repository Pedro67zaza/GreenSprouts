<?php
// Start the session (required to access session data)
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session completely
session_destroy();

// Redirect to login page or homepage
header("Location: login.php"); // Change to your login page
exit();
?>