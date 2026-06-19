<?php

// ERAY YALCIN - 20220702105
// feed.php
// Fetches data for the user's homepage and includes feed.html.

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "ERAY_YALCIN";

// Connect to the database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Read current user id from URL
$user_id = $_GET['user_id'];

if (!$user_id) {
    die("No user_id provided.");
}

// Fetch current user profile
$sql = "SELECT 
            full_name, 
            country, 
            joined_on, 
            bio, 
            user_image 
        FROM USERS 
        WHERE user_id = $user_id";

$user_profile = mysqli_query($conn, $sql);

if ($user_profile === FALSE) {
    die("Query failed: " . mysqli_error($conn));
}

$user = mysqli_fetch_array($user_profile);

if (!$user) {
    die("User not found.");
}

// Fetch latest videos from subscribed channels
$sql = "SELECT 
            VIDEOS.video_id, 
            VIDEOS.title, 
            CHANNELS.channel_id, 
            CHANNELS.name AS channel_name, 
            USERS.country AS uploader_country,
            DATEDIFF(CURDATE(), VIDEOS.uploaded_at) AS days_ago
        FROM SUBSCRIPTIONS 
        INNER JOIN CHANNELS 
            ON SUBSCRIPTIONS.channel_id = CHANNELS.channel_id
        INNER JOIN VIDEOS 
            ON CHANNELS.channel_id = VIDEOS.channel_id
        INNER JOIN USERS 
            ON CHANNELS.owner_id = USERS.user_id
        WHERE SUBSCRIPTIONS.subscriber_id = $user_id
        ORDER BY VIDEOS.uploaded_at DESC";

$latest_videos = mysqli_query($conn, $sql);

if ($latest_videos === FALSE) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch top 5 channels by subscriber count
$sql = "SELECT
            CHANNELS.channel_id,
            CHANNELS.channel_image,
            CHANNELS.name,
            COUNT(SUBSCRIPTIONS.subscriber_id) AS subscribers
        FROM CHANNELS
        LEFT JOIN SUBSCRIPTIONS
            ON CHANNELS.channel_id = SUBSCRIPTIONS.channel_id
        GROUP BY CHANNELS.channel_id
        ORDER BY subscribers DESC
        LIMIT 5";

$top_channels = mysqli_query($conn, $sql);

if ($top_channels === FALSE) {
    die("Query failed: " . mysqli_error($conn));
}

// Display the page
include "feed.html";

?>