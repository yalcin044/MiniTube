<?php

// ERAY YALCIN - 20220702105
// channel.php
// Fetches channel information, channel videos and subscription status.
// It also handles subscribe and unsubscribe actions.

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "ERAY_YALCIN";

// Connect to the MiniTube database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Read current user and selected channel from URL
$user_id = $_GET['user_id'];
$current_channel_id = $_GET['channel_id'];

if (!$user_id) {
    die("No user_id provided.");
}

if (!$current_channel_id) {
    die("No channel_id provided.");
}

// Handle subscribe / unsubscribe form actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == "subscribe") {
        $sql = "INSERT INTO SUBSCRIPTIONS (subscriber_id, channel_id, subscribed_at) 
                VALUES ($user_id, $current_channel_id, CURDATE())";

        if (mysqli_query($conn, $sql) === FALSE) {
            die("Subscribe failed: " . mysqli_error($conn));
        }
    } else if ($_POST['action'] == "unsubscribe") {
        $sql = "DELETE FROM SUBSCRIPTIONS 
                WHERE subscriber_id = $user_id 
                AND channel_id = $current_channel_id";

        if (mysqli_query($conn, $sql) === FALSE) {
            die("Unsubscribe failed: " . mysqli_error($conn));
        }
    }

    // Reload the same channel page after the update
    header("Location: channel.php?user_id=$user_id&channel_id=$current_channel_id");
    exit();
}

// Check if current user is subscribed to this channel
$sql = "SELECT subscription_id 
        FROM SUBSCRIPTIONS 
        WHERE subscriber_id = $user_id 
        AND channel_id = $current_channel_id";

$check_result = mysqli_query($conn, $sql);

if ($check_result === FALSE) {
    die("Subscription check failed: " . mysqli_error($conn));
}

$is_subscribed = (mysqli_num_rows($check_result) > 0);

// Fetch selected channel information and subscriber count
$sql = "SELECT 
            CHANNELS.channel_id,
            CHANNELS.name,
            CHANNELS.channel_image,
            CHANNELS.category,
            CHANNELS.created_on,
            CHANNELS.description,
            USERS.full_name AS owner_name,
            USERS.country AS owner_country,
            COUNT(SUBSCRIPTIONS.subscription_id) AS subscribers
        FROM CHANNELS
        LEFT JOIN USERS 
            ON CHANNELS.owner_id = USERS.user_id
        LEFT JOIN SUBSCRIPTIONS 
            ON CHANNELS.channel_id = SUBSCRIPTIONS.channel_id
        WHERE CHANNELS.channel_id = $current_channel_id
        GROUP BY CHANNELS.channel_id";

$channel_info = mysqli_query($conn, $sql);

if ($channel_info === FALSE) {
    die("Channel info query failed: " . mysqli_error($conn));
}

$current_channel = mysqli_fetch_array($channel_info);

if (!$current_channel) {
    die("Channel not found.");
}

// Fetch videos uploaded by this channel
$sql = "SELECT
            video_id,
            url,
            title,
            duration_seconds,
            uploaded_at,
            view_count
        FROM VIDEOS
        WHERE channel_id = $current_channel_id
        ORDER BY uploaded_at DESC";

$channel_videos = mysqli_query($conn, $sql);

if ($channel_videos === FALSE) {
    die("Channel videos query failed: " . mysqli_error($conn));
}

// Display the channel page
include "channel.html";

?>