<?php
session_start();

// Database configuration
$env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../.env');
$servername = $env["XAI_SERVERNAME"] ?? '';
$username = $env["XAI_USERNAME"] ?? '';
$password = $env["XAI_PASSWORD"] ?? '';
$dbname = $env["XAI_DBNAME"] ?? '';
$debug = $env["XAI_DEBUG"] ?? 0;
$download_pw = $env["XAI_DOWNLOAD_PW"] ?? '';

// logout support
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
  $_SESSION['xai_download_auth'] = false;
  session_regenerate_id(true);
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

function detect_os($ua)
{
  if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
    return 'iOS';
  }
  if (stripos($ua, 'Android') !== false) {
    return 'Android';
  }
  if (stripos($ua, 'Windows') !== false) {
    return 'Windows';
  }
  if (stripos($ua, 'Macintosh') !== false) {
    return 'Mac';
  }
  return '';
}

function session_length_seconds($start, $end)
{
  if (empty($start) || empty($end)) {
    return '';
  }

  $start_ts = strtotime($start);
  $end_ts = strtotime($end);

  if ($start_ts === false || $end_ts === false) {
    return '';
  }

  return max(0, $end_ts - $start_ts);
}

// login guard based on $download_pw
$auth_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pw'])) {
  $submitted_pw = (string)$_POST['download_pw'];
  if ($download_pw !== '' && hash_equals((string)$download_pw, $submitted_pw)) {
    $_SESSION['xai_download_auth'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
  $auth_error = 'Invalid password.';
}

if (!($_SESSION['xai_download_auth'] ?? false)) {
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <title>Data Download</title>
    <link rel="stylesheet" href="style.css" type="text/css">
  </head>
  <body>
    <h3>Data Download</h3>
    <?php if ($auth_error !== ''): ?>
      <p style="color: red;"><?php echo htmlspecialchars($auth_error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post" action="">
      <label for="download_pw">Password:</label>
      <input type="password" id="download_pw" name="download_pw" required>
      <button type="submit">Login</button>
    </form>
  </body>
  </html>
  <?php
  exit;
}

$download_type = $_GET['download'] ?? '';
if ($download_type !== 'sessions' && $download_type !== 'demographics' && $download_type !== 'trials' && $download_type !== 'knowdata' && $download_type !== 'trials_wide') {
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <title>Data Downloads</title>
    <link rel="stylesheet" href="style.css" type="text/css">
  </head>
  <body>
    <h3>Data Downloads</h3>
    <ul>
    <li><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?download=sessions">Download session data</a></li>
    <li><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?download=demographics">Download demographics</a></li>
    <li><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?download=trials">Download trial data (long)</a></li>
    <li><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?download=trials_wide">Download trial data (wide)</a></li>
    <li><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?download=knowdata">Download knowledge data (wide)</a></li>
    </ul>
    <form method="get" action="">
      <input type="hidden" name="logout" value="1">
      <button type="submit">Logout</button>
    </form>
  </body>
  </html>
  <?php
  exit;
}


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = '';
$filename = '';
$header = [];

if ($download_type === 'sessions') {
  $sql = "SELECT id, browser_lang, vis_type, referrer, start_time, end_time, user_agent FROM xaisession;";
  $filename = 'session.csv';
  $header = ['id', 'browser_lang', 'vis_type', 'referrer', 'sess_len_s', 'os'];
} elseif ($download_type === 'demographics') {
  $sql = "SELECT sessid, age, sex, edu FROM xaidemograph;";
  $filename = 'demographics.csv';
  $header = ['sessid', 'age', 'sex', 'edu'];
} elseif ($download_type === 'trials') {
  $sql = "SELECT sessid, time, session_order, trial_type, trial_id, vis_type, leftright, CASE WHEN clsf_id=1 THEN 1 ELSE '0' END AS correct FROM xaidata;";
  $filename = 'trials_long.csv';
  $header = ['sessid', 'time', 'session_order', 'trial_type', 'trial_id', 'vis_type', 'leftright', 'correct'];
} elseif ($download_type === 'trials_wide') {
  $sql = "SELECT sessid, trial_id, CASE WHEN clsf_id=1 THEN 1 ELSE 0 END AS correct FROM xaidata WHERE trial_id > 0;";
  $filename = 'trials_wide.csv';
  $header = [];
} elseif ($download_type === 'knowdata') {
  $sql = "SELECT sessid, target, correct FROM xaiknowdata WHERE trial_id>0;";
  $filename = 'knowledge.csv';
  $header = [];
} else {
  die("unknown download");
}

$result = $conn->query($sql);

if (!$result) {
  http_response_code(500);
  echo "Error retrieving statistics: " . $conn->error;
  $conn->close();
  exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if ($download_type === 'knowdata') {
  // pivot long to wide before download
  $targets = [];
  $targets_seen = [];
  $sess_rows = [];

  while ($row = $result->fetch_assoc()) {
    $sessid = (string)($row['sessid'] ?? '');
    $target = (string)($row['target'] ?? '');
    $is_correct = ((string)($row['correct'] ?? '') === '1');

    if ($target !== '' && !isset($targets_seen[$target])) {
      $targets_seen[$target] = true;
      $targets[] = $target;
    }

    if (!isset($sess_rows[$sessid])) {
      $sess_rows[$sessid] = [];
    }

    if ($target !== '' && $is_correct) {
      $sess_rows[$sessid][$target] = 1;
    } elseif ($target !== '' && !isset($sess_rows[$sessid][$target])) {
      $sess_rows[$sessid][$target] = 0;
    }
  }

  sort($targets, SORT_NATURAL);
  ksort($sess_rows, SORT_NATURAL);

  $wide_header = array_merge(['sessid'], $targets);
  fputcsv($output, $wide_header, ',', '"', '\\');

  foreach ($sess_rows as $sessid => $target_values) {
    $out_row = [$sessid];
    foreach ($targets as $target) {
      $out_row[] = $target_values[$target] ?? 0;
    }
    fputcsv($output, $out_row, ',', '"', '\\');
  }

  fclose($output);
  $conn->close();
  exit;
}

if ($download_type === 'trials_wide') {
  // pivot long to wide before download
  $trial_ids = [];
  $trial_ids_seen = [];
  $sess_rows = [];

  while ($row = $result->fetch_assoc()) {
    $sessid = (string)($row['sessid'] ?? '');
    $trial_id = (string)($row['trial_id'] ?? '');
    $is_correct = ((string)($row['correct'] ?? '') === '1');

    if ($trial_id !== '' && !isset($trial_ids_seen[$trial_id])) {
      $trial_ids_seen[$trial_id] = true;
      $trial_ids[] = $trial_id;
    }

    if (!isset($sess_rows[$sessid])) {
      $sess_rows[$sessid] = [];
    }

    if ($trial_id !== '' && $is_correct) {
      $sess_rows[$sessid][$trial_id] = 1;
    } elseif ($trial_id !== '' && !isset($sess_rows[$sessid][$trial_id])) {
      $sess_rows[$sessid][$trial_id] = 0;
    }
  }

  sort($trial_ids, SORT_NATURAL);
  ksort($sess_rows, SORT_NATURAL);

  $target_headers = array_map(fn($t): string => "T$t", $trial_ids);
  $wide_header = array_merge(['sessid'], $target_headers);
  fputcsv($output, $wide_header, ',', '"', '\\');

  foreach ($sess_rows as $sessid => $trial_values) {
    $out_row = [$sessid];
    foreach ($trial_ids as $trial_id) {
      $out_row[] = $trial_values[$trial_id] ?? 0;
    }
    fputcsv($output, $out_row, ',', '"', '\\');
  }

  fclose($output);
  $conn->close();
  exit;
}

// everything else copied row by row
fputcsv($output, $header, ',', '"', '\\');
while ($row = $result->fetch_assoc()) {
  if ($download_type === 'sessions') {
    $sess_len = session_length_seconds($row['start_time'] ?? null, $row['end_time'] ?? null);
    $os = detect_os((string)($row['user_agent'] ?? ''));

    fputcsv($output, [
      $row['id'] ?? '',
      $row['browser_lang'] ?? '',
      $row['vis_type'] ?? '',
      $row['referrer'] ?? '',
      $sess_len,
      $os,
    ], ',', '"', '\\');
  } elseif ($download_type === 'demographics') {
    fputcsv($output, [
      $row['sessid'] ?? '',
      $row['age'] ?? '',
      $row['sex'] ?? '',
      $row['edu'] ?? '',
    ], ',', '"', '\\');
  } elseif ($download_type === 'trials') {
    fputcsv($output, [
      $row['sessid'] ?? '',
      $row['time'] ?? '',
      $row['session_order'] ?? '',
      $row['trial_type'] ?? '',
      $row['trial_id'] ?? '',
      $row['vis_type'] ?? '',
      $row['leftright'] ?? '',
      $row['correct'] ?? '',
    ], ',', '"', '\\');
  } else {
    die("unknown download");
  }
}

fclose($output);
$conn->close();
exit;
