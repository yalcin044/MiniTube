<?php

// ERAY YALCIN - 20220702105
// generate_data.php
// Reads text files from the data folder and generates insert.sql
// with INSERT statements for USERS, CHANNELS, VIDEOS, SUBSCRIPTIONS and COMMENTS.

// Read non-empty lines from a text file
function read_lines_from_file($filename) {
    $full_path = "data/" . $filename;
    $lines = file($full_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        die("Error reading file: $full_path");
    }

    return $lines;
}

// Generate a random date between two dates
function generate_random_date($start_date, $end_date) {
    return date("Y-m-d", rand(strtotime($start_date), strtotime($end_date)));
}

// Pick a random item from an array
function pick_random($array) {
    return $array[array_rand($array)];
}

// Generate insert.sql
function generate_sql_file() {
    // Data size configuration
    $number_of_users = 200;
    $number_of_channels = 100;
    $number_of_videos = 400;
    $number_of_subscriptions = 240;
    $number_of_comments = 300;
    $number_of_replies = 40;

    // Read input files
    $first_names = read_lines_from_file("first_names.txt");
    $countries = read_lines_from_file("countries.txt");
    $user_bios = read_lines_from_file("user_bios.txt");
    $channel_categories = read_lines_from_file("channel_categories.txt");
    $channel_names = read_lines_from_file("channel_names.txt");
    $channel_descriptions = read_lines_from_file("channel_descriptions.txt");
    $video_titles = read_lines_from_file("video_titles.txt");
    $video_descriptions = read_lines_from_file("video_descriptions.txt");
    $video_urls = read_lines_from_file("video_urls.txt");
    $comment_bodies = read_lines_from_file("comment_bodies.txt");
    $reply_bodies = read_lines_from_file("reply_bodies.txt");

    // Open insert.sql for writing
    $sql_file = fopen("insert.sql", "w");

    if ($sql_file === false) {
        die("Unable to open insert.sql");
    }

    // USERS
    for ($user_id = 1; $user_id <= $number_of_users; $user_id++) {
        $full_name = pick_random($first_names);

        // user_id is added to the username to make each username unique
        $username = strtolower($full_name) . str_pad($user_id, 3, "0", STR_PAD_LEFT);

        // Same default password is used for all generated users to simplify testing
        $password = "1234";

        $image_id = rand(1, 70);
        $user_image = "https://i.pravatar.cc/150?img=$image_id";
        $email = $username . "@" . $username . ".com";
        $country = pick_random($countries);
        $joined_on = generate_random_date("2020-01-01", "2026-01-01");
        $bio = pick_random($user_bios);

        $sql = "INSERT INTO USERS (user_id, username, password, user_image, full_name, email, country, joined_on, bio) VALUES "
            . "($user_id, '$username', '$password', '$user_image', '$full_name', '$email', '$country', '$joined_on', '$bio');\n";

        fwrite($sql_file, $sql);
    }

    // CHANNELS
    // A channel owner must be unique because owner_id is UNIQUE in the CHANNELS table.
    // Therefore, all user ids are shuffled and the first $number_of_channels users are selected as owners.
    $all_user_ids = range(1, $number_of_users);
    shuffle($all_user_ids);
    $channel_owner_ids = array_slice($all_user_ids, 0, $number_of_channels);

    // This array stores the owner of each channel.
    // It is used later to prevent users from subscribing to their own channels.
    $channels_info = [];

    for ($channel_id = 1; $channel_id <= $number_of_channels; $channel_id++) {
        $owner_id = $channel_owner_ids[$channel_id - 1];
        $channel_image = "https://picsum.photos/seed/ch$channel_id/200";
        $name = pick_random($channel_names);
        $description = pick_random($channel_descriptions);
        $created_on = generate_random_date("2020-01-01", "2026-01-01");
        $category = pick_random($channel_categories);

        // Save the owner of this channel for subscription checks later
        $channels_info[$channel_id] = ['owner_id' => $owner_id];

        $sql = "INSERT INTO CHANNELS (channel_id, owner_id, channel_image, name, description, created_on, category) VALUES "
            . "($channel_id, $owner_id, '$channel_image', '$name', '$description', '$created_on', '$category');\n";

        fwrite($sql_file, $sql);
    }

    // VIDEOS
    for ($video_id = 1; $video_id <= $number_of_videos; $video_id++) {
        // Assign each video to a random existing channel
        $channel_id = rand(1, $number_of_channels);

        $title = pick_random($video_titles);
        $description = pick_random($video_descriptions);
        $url = pick_random($video_urls);
        $duration_seconds = rand(60, 1800);
        $uploaded_at = generate_random_date("2020-01-01", "2026-01-01");
        $view_count = rand(0, 5000);

        // like_count should not be greater than view_count
        $like_count = rand(0, $view_count);

        $sql = "INSERT INTO VIDEOS (video_id, channel_id, title, description, url, duration_seconds, uploaded_at, view_count, like_count) VALUES "
            . "($video_id, $channel_id, '$title', '$description', '$url', $duration_seconds, '$uploaded_at', $view_count, $like_count);\n";

        fwrite($sql_file, $sql);
    }

    // SUBSCRIPTIONS
    // This array stores already generated subscriptions.
    // It helps prevent duplicate subscriber-channel pairs.
    $subscriptions = [];
    $subscription_id = 1;

    while ($subscription_id <= $number_of_subscriptions) {
        // Randomly select a user and a channel for the subscription
        $subscriber_id = rand(1, $number_of_users);
        $channel_id = rand(1, $number_of_channels);

        // A user cannot subscribe to his/her own channel
        if ($channels_info[$channel_id]['owner_id'] == $subscriber_id) {
            continue;
        }

        $already_exists = false;

        // Check if the same user already subscribed to the same channel
        foreach ($subscriptions as $subscription) {
            if ($subscription['subscriber_id'] == $subscriber_id && $subscription['channel_id'] == $channel_id) {
                $already_exists = true;
                break;
            }
        }

        // If this subscription already exists, skip it and try another random pair
        if ($already_exists) {
            continue;
        }

        // Store the valid subscription pair so it is not generated again
        $subscriptions[] = ['subscriber_id' => $subscriber_id, 'channel_id' => $channel_id];

        $subscribed_at = generate_random_date("2020-01-01", "2026-01-01");

        $sql = "INSERT INTO SUBSCRIPTIONS (subscription_id, subscriber_id, channel_id, subscribed_at) VALUES "
            . "($subscription_id, $subscriber_id, $channel_id, '$subscribed_at');\n";

        fwrite($sql_file, $sql);

        $subscription_id++;
    }

    // TOP-LEVEL COMMENTS
    // Top-level comments are normal comments, so parent_comment_id is NULL.
    // Their video_id values are stored because replies must belong to the same video as their parent comment.
    $top_level_comments_info = [];

    for ($comment_id = 1; $comment_id <= $number_of_comments - $number_of_replies; $comment_id++) {
        $video_id = rand(1, $number_of_videos);
        $user_id = rand(1, $number_of_users);
        $body = pick_random($comment_bodies);
        $posted_at = generate_random_date("2020-01-01", "2026-01-01");

        // Store this comment id and its video id for reply generation
        $top_level_comments_info[$comment_id] = ['video_id' => $video_id];

        $sql = "INSERT INTO COMMENTS (comment_id, video_id, user_id, parent_comment_id, body, posted_at) VALUES "
            . "($comment_id, $video_id, $user_id, NULL, '$body', '$posted_at');\n";

        fwrite($sql_file, $sql);
    }

    // REPLIES
    // Replies are also stored in the COMMENTS table.
    // The difference is that parent_comment_id is not NULL.

    // Get all top-level comment ids so each reply can choose a valid parent comment
    $top_level_comment_ids = array_keys($top_level_comments_info);

    for ($reply_index = 1; $reply_index <= $number_of_replies; $reply_index++) {
        // Reply comment ids continue after the top-level comment ids
        $comment_id = $number_of_comments - $number_of_replies + $reply_index;

        // Pick a random top-level comment as the parent of this reply
        $parent_comment_id = pick_random($top_level_comment_ids);

        // Use the same video_id as the parent comment
        // This keeps the reply under the correct video page
        $video_id = $top_level_comments_info[$parent_comment_id]['video_id'];

        $user_id = rand(1, $number_of_users);
        $body = pick_random($reply_bodies);
        $posted_at = generate_random_date("2020-01-01", "2026-01-01");

        $sql = "INSERT INTO COMMENTS (comment_id, video_id, user_id, parent_comment_id, body, posted_at) VALUES "
            . "($comment_id, $video_id, $user_id, $parent_comment_id, '$body', '$posted_at');\n";

        fwrite($sql_file, $sql);
    }

    // Close insert.sql after writing all INSERT statements
    fclose($sql_file);
}

?>