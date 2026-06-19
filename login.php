<?php

// ERAY YALCIN - 20220702105
// login.php
// Authenticates the user with username and password.
// If authentication is successful, redirects the user to feed.php with user_id.

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "ERAY_YALCIN";

// Connect to the MiniTube database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Read login form values
$login_username = $_POST['username'];
$login_password = $_POST['password'];

// Find the user by username
$sql = "SELECT user_id, password 
        FROM USERS 
        WHERE username = '$login_username'";

$result = mysqli_query($conn, $sql);

if ($result === FALSE) {
    die("Query failed: " . mysqli_error($conn));
}

$authenticated_user = mysqli_fetch_array($result);

// Redirect to homepage if username exists and password is correct
if ($authenticated_user && $authenticated_user['password'] === $login_password) {
    header("Location: feed.php?user_id=" . $authenticated_user['user_id']);
    exit();
} else {
    echo "Invalid username or password. <a href='login.html'>Try again</a>";
}

?>