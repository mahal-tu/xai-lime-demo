<?php

// Database configuration
$env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../.env');
$servername = $env["XAI_SERVERNAME"];
$username = $env["XAI_USERNAME"];
$password = $env["XAI_PASSWORD"];
$dbname = $env["XAI_DBNAME"];
$debug = $env["XAI_DEBUG"] ?? 0;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Statistics</title>
  <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>

<?php
$sql = "SELECT count(*) from xaisession;";
$result = $conn->query($sql);
if ($result) {
  $row = $result->fetch_row();
  $count = $row[0];
  echo "Total number of sessions: $count<br>";
} else {
  echo "<p>Error retrieving statistics: " . $conn->error . "</p>";
}

$sql = "SELECT count(*) from xaisession where end_time is not NULL;";
$result = $conn->query($sql);
if ($result) {
  $row = $result->fetch_row();
  $count = $row[0];
  echo "Number of completed sessions: $count<br>";
} else {
  echo "<p>Error retrieving statistics: " . $conn->error . "</p>";
}

$sql = "SELECT 1 - (sum(clsf_id) - count(*))/count(*) from xaidata where vis_type='maf' and sessid in (select id from xaisession where end_time is not NULL);";
$result = $conn->query($sql);
if ($result) {
  $row = $result->fetch_row();
  $p_maf = $row[0];
  echo "maf correct: $p_maf<br>";
} else {
  echo "<p>Error retrieving statistics: " . $conn->error . "</p>";
}

$sql = "SELECT 1 - (sum(clsf_id) - count(*))/count(*) from xaidata where vis_type='haf' and sessid in (select id from xaisession where end_time is not NULL);";
$result = $conn->query($sql);
if ($result) {
  $row = $result->fetch_row();
  $p_haf = $row[0];
  echo "haf correct: $p_haf<br>";
} else {
  echo "<p>Error retrieving statistics: " . $conn->error . "</p>";
}
?>

</body>
</html>
