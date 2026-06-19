<?php

// ERAY YALCIN - 20220702105
// install.php
// Creates the MiniTube database, creates all required tables,
// fills them by executing insert.sql, and redirects the user to login.html.

include "generate_data.php";

// Generate insert.sql before creating and filling the database
generate_sql_file();

$servername = "localhost";
$username = "root";
$password = "mysql";
$database_name = "ERAY_YALCIN";

// Create MySQL connection without selecting a database yet
$conn = mysqli_connect($servername, $username, $password);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Recreate the database from scratch
$sql = "DROP DATABASE IF EXISTS $database_name";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Deleting the database failed: " . mysqli_error($conn));
}

$sql = "CREATE DATABASE $database_name";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating database failed: " . mysqli_error($conn));
}

if (mysqli_select_db($conn, $database_name) === FALSE) {
    die("Selecting database failed: " . mysqli_error($conn));
}

// Create USERS table
$sql = "CREATE TABLE USERS(
            user_id INT NOT NULL AUTO_INCREMENT,
            username VARCHAR(100) UNIQUE,
            password VARCHAR(100),
            user_image VARCHAR(200),
            full_name VARCHAR(100),
            email VARCHAR(100) UNIQUE,
            country VARCHAR(100),
            joined_on DATE,
            bio VARCHAR(500),
            PRIMARY KEY (user_id)
)";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating the USERS table failed: " . mysqli_error($conn));
}

// Create CHANNELS table
$sql = "CREATE TABLE CHANNELS(
            channel_id INT NOT NULL AUTO_INCREMENT,
            owner_id INT NOT NULL UNIQUE,
            channel_image VARCHAR(200),
            name VARCHAR(100),
            description VARCHAR(500),
            created_on DATE,
            category VARCHAR(100),
            PRIMARY KEY (channel_id),
            FOREIGN KEY (owner_id) REFERENCES USERS (user_id)
)";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating the CHANNELS table failed: " . mysqli_error($conn));
}

// Create VIDEOS table
$sql = "CREATE TABLE VIDEOS(
            video_id INT NOT NULL AUTO_INCREMENT,
            channel_id INT NOT NULL,
            title VARCHAR(100),
            description VARCHAR(500),
            url VARCHAR(200),
            duration_seconds INT,
            uploaded_at DATE,
            view_count INT DEFAULT 0,
            like_count INT DEFAULT 0,
            PRIMARY KEY (video_id),
            FOREIGN KEY (channel_id) REFERENCES CHANNELS (channel_id)
)";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating the VIDEOS table failed: " . mysqli_error($conn));
}

// Create SUBSCRIPTIONS table
$sql = "CREATE TABLE SUBSCRIPTIONS(
            subscription_id INT NOT NULL AUTO_INCREMENT,
            subscriber_id INT NOT NULL,
            channel_id INT NOT NULL,
            subscribed_at DATE,
            PRIMARY KEY (subscription_id),
            FOREIGN KEY (subscriber_id) REFERENCES USERS (user_id),
            FOREIGN KEY (channel_id) REFERENCES CHANNELS (channel_id),
            UNIQUE KEY (subscriber_id, channel_id)
)";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating the SUBSCRIPTIONS table failed: " . mysqli_error($conn));
}

// Create COMMENTS table
$sql = "CREATE TABLE COMMENTS(
            comment_id INT NOT NULL AUTO_INCREMENT,
            video_id INT NOT NULL,
            user_id INT NOT NULL,
            parent_comment_id INT NULL,
            body VARCHAR(500),
            posted_at DATE,
            PRIMARY KEY (comment_id),
            FOREIGN KEY (parent_comment_id) REFERENCES COMMENTS (comment_id),
            FOREIGN KEY (video_id) REFERENCES VIDEOS (video_id),
            FOREIGN KEY (user_id) REFERENCES USERS (user_id)
)";

if (mysqli_query($conn, $sql) === FALSE) {
    die("Creating the COMMENTS table failed: " . mysqli_error($conn));
}

// Execute generated insert.sql file to fill all tables
if (!file_exists("insert.sql")) {
    die("insert.sql not found");
}

$sql_content = file_get_contents("insert.sql");

if (mysqli_multi_query($conn, $sql_content) === FALSE) {
    die("Error executing insert.sql: " . mysqli_error($conn));
}

// Go to login page after successful installation
header("Location: login.html");
exit();

?>