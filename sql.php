<?php

// ERAY YALCIN - 20220702105
// sql.php
// Runs an SQL query entered by the user and prepares the result for sql.html.

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "ERAY_YALCIN";

// Disable mysqli exceptions so invalid SQL queries can be shown as error messages
mysqli_report(MYSQLI_REPORT_OFF);

// Connect to the MiniTube database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Default values before the form is submitted
$result = null;
$error = "";
$affected_rows = 0;
$has_query = false;
$is_select = false;
$query = "";

// Run the submitted SQL query
if (!empty($_POST['query'])) {
    $has_query = true;
    $query = trim($_POST['query']);

    $result = mysqli_query($conn, $query);

    if ($result === FALSE) {
        $error = mysqli_error($conn);
    } 
    else if ($result === TRUE) {
        $affected_rows = mysqli_affected_rows($conn);
    } 
    else {
        $is_select = true;
    }
}

// Display the SQL query page
include "sql.html";

?>