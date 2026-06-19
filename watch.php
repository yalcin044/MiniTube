<?php

// ERAY YALCIN - 20220702105
// watch.php
// Displays a selected video, increases its view count,
// fetches its comments with replies and handles new comments.

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "ERAY_YALCIN";

// Connect to the MiniTube database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Read current user and selected video from URL
$user_id = $_GET['user_id'];
$current_video_id = $_GET['video_id'];

if (!$user_id) {
    die("No user_id provided.");
}

if (!$current_video_id) {
    die("No video_id provided.");
}

// Handle new comment or reply form submission
if (!empty($_POST['comment_body'])) {
    $comment_body = mysqli_real_escape_string($conn, $_POST['comment_body']);

    if (!empty($_POST['parent_comment_id'])) {
        $parent_comment_id = intval($_POST['parent_comment_id']);

        $sql = "INSERT INTO COMMENTS (video_id, user_id, parent_comment_id, body, posted_at) 
                VALUES ($current_video_id, $user_id, $parent_comment_id, '$comment_body', CURDATE())";
    } 
    else {
        $sql = "INSERT INTO COMMENTS (video_id, user_id, parent_comment_id, body, posted_at) 
                VALUES ($current_video_id, $user_id, NULL, '$comment_body', CURDATE())";
    }

    if (mysqli_query($conn, $sql) === FALSE) {
        die("Comment insert failed: " . mysqli_error($conn));
    }

    // Reload the page so the new comment becomes visible
    header("Location: watch.php?user_id=$user_id&video_id=$current_video_id");
    exit();
}

// Increase view count whenever the video page loads
$sql = "UPDATE VIDEOS 
        SET view_count = view_count + 1 
        WHERE video_id = $current_video_id";

if (mysqli_query($conn, $sql) === FALSE) {
    die("View count update failed: " . mysqli_error($conn));
}

// Fetch video information and create popularity badge using SQL CASE
$sql = "SELECT 
            VIDEOS.video_id,
            VIDEOS.title,
            VIDEOS.url,
            VIDEOS.duration_seconds,
            VIDEOS.uploaded_at,
            VIDEOS.view_count,
            VIDEOS.channel_id,
            CHANNELS.name AS channel_name,
            USERS.country AS uploader_country,
            CASE
                WHEN VIDEOS.view_count >= 1000 THEN 'Popular'
                WHEN VIDEOS.view_count >= 100 THEN 'Trending'
                ELSE 'New'
            END AS popularity_badge
        FROM VIDEOS 
        INNER JOIN CHANNELS 
            ON VIDEOS.channel_id = CHANNELS.channel_id
        INNER JOIN USERS 
            ON CHANNELS.owner_id = USERS.user_id
        WHERE VIDEOS.video_id = $current_video_id";

$video_info = mysqli_query($conn, $sql);

if ($video_info === FALSE) {
    die("Video query failed: " . mysqli_error($conn));
}

$video = mysqli_fetch_array($video_info);

if (!$video) {
    die("Video not found.");
}

// Convert normal YouTube watch URL to embed URL
$embed_url = $video['url'];

if (strpos($embed_url, "watch?v=") !== false) {
    $embed_url = str_replace("watch?v=", "embed/", $embed_url);
}

if (strpos($embed_url, "?") !== false) {
    $embed_url .= "&autoplay=1&mute=1";
} 
else {
    $embed_url .= "?autoplay=1&mute=1";
}

// Fetch top-level comments and their replies with a self-join
$sql = "SELECT 
            parent.comment_id AS parent_id,
            parent.body AS parent_body,
            parent.posted_at AS parent_posted_at,
            parent_user.username AS parent_username,
            reply.comment_id AS reply_id,
            reply.body AS reply_body,
            reply.posted_at AS reply_posted_at,
            reply_user.username AS reply_username
        FROM COMMENTS parent
        INNER JOIN USERS parent_user 
            ON parent.user_id = parent_user.user_id
        LEFT JOIN COMMENTS reply 
            ON reply.parent_comment_id = parent.comment_id
        LEFT JOIN USERS reply_user 
            ON reply.user_id = reply_user.user_id
        WHERE parent.video_id = $current_video_id 
        AND parent.parent_comment_id IS NULL
        ORDER BY parent.posted_at DESC, parent.comment_id DESC, reply.posted_at ASC, reply.comment_id ASC";

$comments_result = mysqli_query($conn, $sql);

if ($comments_result === FALSE) {
    die("Comments query failed: " . mysqli_error($conn));
}

// Organize comments and replies before sending them to the HTML page
$comments = [];

while ($row = mysqli_fetch_array($comments_result)) {
    $parent_id = $row['parent_id'];
    $parent_index = -1;

    // Check whether this parent comment was already added
    for ($i = 0; $i < count($comments); $i++) {
        if ($comments[$i]['parent_id'] == $parent_id) {
            $parent_index = $i;
            break;
        }
    }

    // Add parent comment only once
    if ($parent_index == -1) {
        $comments[] = [
            'parent_id' => $parent_id,
            'parent_username' => $row['parent_username'],
            'parent_posted_at' => $row['parent_posted_at'],
            'parent_body' => $row['parent_body'],
            'replies' => []
        ];

        $parent_index = count($comments) - 1;
    }

    // Add reply if this row has a reply
    if (!empty($row['reply_id'])) {
        $comments[$parent_index]['replies'][] = [
            'reply_username' => $row['reply_username'],
            'reply_posted_at' => $row['reply_posted_at'],
            'reply_body' => $row['reply_body']
        ];
    }
}

// Display the watch page
include "watch.html";

?>