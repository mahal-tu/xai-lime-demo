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

// user session
$sql = "CREATE TABLE IF NOT EXISTS xaisession (" .
  "id INT NOT NULL AUTO_INCREMENT, " .
  "start_time DATETIME NOT NULL DEFAULT UTC_TIMESTAMP, " .
  "end_time DATETIME NULL, " .
  "user_agent VARCHAR(255) NULL, " .
  "browser_lang VARCHAR(2) NULL, " .
  "win_width int DEFAULT -1, " .
  "win_height int DEFAULT -1, " .
  "referrer VARCHAR(255) NULL, " .
  "http_referrer VARCHAR(1023) NULL, " .
  "user_email VARCHAR(255) NULL, " .
  "vis_type VARCHAR(4) DEFAULT 'haf', " .
  "flags JSON NOT NULL DEFAULT (JSON_OBJECT()), " .
  "PRIMARY KEY (id)) ENGINE = InnoDB;";
$result = $conn->query($sql);

# run in phpMyAdmin
# ALTER TABLE xaisession ADD COLUMN http_referrer VARCHAR(1023) DEFAULT NULL;

$sessid = -1;

include_once 'i18n.php';
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo i18n('KIExplainableTitle'); ?></title>
<link rel="stylesheet" href="style.css" type="text/css">
<script>
document.addEventListener('DOMContentLoaded', () => {
  const windowWidth = window.innerWidth;
  const windowHeight = window.innerHeight;
  const aspectRatio = windowWidth / windowHeight;

  var e = document.getElementById('win_width');
  if(e) e.value = windowWidth;
  e = document.getElementById('win_height');
  if(e) e.value = windowHeight;

  var e = document.getElementById('button-container');
  if(e) {
    if (aspectRatio > .65) {
      e.classList.add('landscape');
    } else {
      e.classList.add('portrait');
    }
  }
});

window.addEventListener('resize', () => {
  var e = document.getElementById('button-container');
  if (e) {
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;
    const aspectRatio = windowWidth / windowHeight;

    if (aspectRatio > .65) {
      e.classList.remove('portrait');
      e.classList.add('landscape');
    } else {
      e.classList.remove('landscape');
      e.classList.add('portrait');
    }
  }
});

function openImprint() {
  const width = window.innerWidth * 0.8; // 80% der Viewport-Breite
  const height = window.innerHeight * 0.8; // 80% der Viewport-Höhe
  window.open('imprint.html', 'Impressum', `width=${width},height=${height}`);
}

function openDatenschutz() {
  const width = window.innerWidth * 0.8; // 80% der Viewport-Breite
  const height = window.innerHeight * 0.8; // 80% der Viewport-Höhe
  window.open('datenschutz.html', 'Datenschutz', `width=${width},height=${height}`);
}

</script>
</head>
<body>


<?php

function print_header(string $section, float $completion) {
  if ($completion < 0) {
    echo '<div class="head">' . i18n('KIHead') . '</div>';
  } else {
    printf('<div class="head">' . i18n('KIHead') . ' - %s <div id=progress>%.0f%%</div></div>', $section, $completion);
  }
  // open content area
  echo '<div id="content">';
}

function print_footer() {
  // close content area
  echo '</div>';
  echo '<div class="foot">(c) your name 2026'
    . ' - <a href="#" onclick="openImprint(); return false;">Impressum</a>'
    . ' - <a href="#" onclick="openDatenschutz(); return false;">Datenschutz</a>'
    . ($GLOBALS['debug'] ? ' - <a href="/xai/">restart</a>' : '')
    . '</div>';
}

function print_login() {
  $referrer = substr($_GET['ref'] ?? '', 0, 64);
  $http_referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 1023);
  print_header('', -1);
  echo '<div>' . i18n('WelcomeText') . '</div>';
  echo '<div>' . i18n('IntroText1') . '</div>';
  echo '<div>' . i18n('IntroText2') . '</div>';
  echo '<div>' . i18n('IntroText3') . '</div>';
  echo '<div>' . i18n('IntroText4') . '</div>';
  echo '<div>' . i18n('IntroText5') . '</div>';
  echo '<div><form method="POST">';
  echo '<input type="hidden" name="opcode" value="login">';
  echo '<input type="hidden" id="win_width" name="win_width" value="-1">';
  echo '<input type="hidden" id="win_height" name="win_height" value="-1">';
  echo '<input type="hidden" name="referrer" value="' . htmlspecialchars($referrer) . '">';
  echo '<input type="hidden" name="http_referrer" value="' . htmlspecialchars($http_referrer) . '">';
  echo '<button class="continuebtn" type="submit">' . i18n('StartButton') . '</button>';
  echo '</form></div>';
  print_footer();
}

include 'trials.php';

include 'know_quest.php';

// knowledge choice
$sql = "CREATE TABLE IF NOT EXISTS xaidemograph (" .
  "id INT NOT NULL AUTO_INCREMENT, " .
  "time DATETIME NOT NULL DEFAULT UTC_TIMESTAMP, " .
  "age INT NULL, " .
  "sex VARCHAR(8) NULL, " .
  "edu VARCHAR(64) NULL, " .
  "sessid INT NOT NULL, " .
  "PRIMARY KEY (id)) ENGINE = InnoDB;";
$result = $conn->query($sql);


function print_demographics():bool {
  global $conn;
  global $sessid;
  global $trials;

  $sql = "SELECT count(*) as seen FROM xaidemograph WHERE sessid=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $sessid);
  if (!$stmt->execute()) {
      echo "Error getting session: " . $stmt->error;
  }
  $stmt->bind_result($seen);
  $stmt->fetch();
  $stmt->close();
  if($seen >= 1) return true;

  if(get_session_flag('demograph') != true) {
    print_header(i18n('DemographicsHeader'), 0.0);
    echo '<div>' . i18n('DemographicsIntro') . '</div>';
    echo '<div>' . i18n('DemographicsVoluntary') . '</div>';
    echo '<form method="post">';
    echo '<input type="hidden" name="sessid" value="' . $sessid . '">';
    echo '<input type="hidden" name="opcode" value="transition">';
    echo '<input type="hidden" name="section" value="demograph">';
    echo '<div><button class="continuebtn" type="submit">' . i18n('Weiter') . '</button></div>';
    echo '</form>';
    return false;
  } else {
    print_header(i18n('DemographicsHeader'), 50.0);

    echo '<div>' . i18n('DemographicsFrage') . '</div>';
    echo '<form method="post">';
    echo '<input type="hidden" name="sessid" value="' . $sessid . '">';
    echo '<input type="hidden" name="opcode" value="demograph">';
    echo '<div>' . i18n('DemographicsAlter') . ': <input type="number" name="age"></div>';
    echo '<div>' . i18n('DemographicsGender') . ':<br>' .
      '<label><input type="radio" name="sex" value="m">' . i18n('DemographicsGenderM') . '</label>' .
      '<label><input type="radio" name="sex" value="w">' . i18n('DemographicsGenderF') . '</label>' .
      '<label><input type="radio" name="sex" value="d">' . i18n('DemographicsGenderD') . '</label></div>';
    echo '<div>' . i18n('DemographicsEducation') . ':<br>' .
      '<label><input type="radio" name="abschluss" value="1-Grundschule">' . i18n('DemographicsEduGrund') . '</label><br>' .
      '<label><input type="radio" name="abschluss" value="2-Oberschule">' . i18n('DemographicsEduOber') . '</label><br>' .
      '<label><input type="radio" name="abschluss" value="3-Abitur">' . i18n('DemographicsEduAbi') . '</label><br>' .
      '<label><input type="radio" name="abschluss" value="4-Bachelor">' . i18n('DemographicsEduHochB') . '</label><br>' .
      '<label><input type="radio" name="abschluss" value="5-Master">' . i18n('DemographicsEduHochM') . '</label><br>' .
      '<label><input type="radio" name="abschluss" value="6-Promotion">' . i18n('DemographicsEduPhD') . '</label><br>' .
      '</div>';
    echo '<div><button class="continuebtn" type="submit">' . i18n('Weiter') . '</button></div>';
    echo '</form>';
    return false;
  }
}

function print_end_screen() {
  global $sessid;
  global $conn;

  echo '<div>' . i18n('EndeDanke') . '</div>';

  $sql = "SELECT COUNT(user_email) FROM xaisession WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $sessid);
  if (!$stmt->execute()) {
      echo "Error getting session: " . $stmt->error;
  }
  $stmt->bind_result($user_email);
  $stmt->fetch();
  $stmt->close();

  if($user_email == 0) {
    $sql = "UPDATE xaisession SET end_time = UTC_TIMESTAMP where id=(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sessid);
    if (!$stmt->execute()) {
      echo "Error finalizing session: " . $stmt->error;
    }
    $stmt->close();

    echo '<div><form method="post">';
    echo i18n('LotteryText') . '<br>';
    echo '<input type="hidden" name="sessid" value="' . $sessid . '">';
    echo '<input type="hidden" name="opcode" value="email">';
    echo '<input type="text" class="freetext" maxlength="60" name="email">&nbsp;';
    echo '<button class="button" type="submit">' . i18n('SubmitButton') . '</button>';
    echo '</form></div>';
    echo '<div>' . i18n('FensterSchliessenAlt') . '</div>';
  } else {
    echo '<div>' . i18n('FensterSchliessen') . '</div>';
  }
}

function print_main_view() {
  $vis_type = get_vis_type();

  if(print_training($vis_type)) {
    if(print_lime_sel($vis_type)) {
      if(print_knowledge_sel()) {
        if(print_demographics()) {
          print_header(i18n('ProgressEnde'), 100.0);
          print_end_screen();
        }
      }
    }
  }

  print_footer();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (array_key_exists("sessid", $_POST)) $sessid = $_POST["sessid"];
  //echo "session " . $sessid;

  $opcode = $_POST["opcode"] ?? 'noop';

  if ($opcode == "login") {
    $vis_types = ['haf', 'maf'];
    $vis_type = $vis_types[rand(0, count($vis_types)-1)];

    $lang = i18n_get_lang();
    $win_width = $_POST["win_width"] ?? -1;
    $win_height = $_POST["win_height"] ?? -1;

    $referrer = $_POST["referrer"] ?? '';
    $http_referrer = $_POST["http_referrer"] ?? '';

    $sql = "INSERT INTO xaisession (user_agent,browser_lang,win_width,win_height,referrer,http_referrer,vis_type) VALUES (?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiisss", $_SERVER['HTTP_USER_AGENT'], $lang, $win_width, $win_height, $referrer, $http_referrer, $vis_type);
    if (!$stmt->execute()) {
      echo "Error creating session: " . $stmt->error;
    }
    $stmt->close();
    $sessid = $conn->insert_id;


    print_main_view();
  } else if ($opcode == "sel_img") {
    on_sel_image();
    print_main_view();
  } else if ($opcode == "sel_knowledge") {
    on_sel_knowledge();
    print_main_view();

  } else if ($opcode == "demograph") {

    // Get all the data
    $age = $_POST["age"] ?? '-1';
    $sex = $_POST["sex"] ?? 'none';
    $edu = $_POST["abschluss"] ?? 'empty';

    // Prepare and execute the SQL query
    $sql = "INSERT INTO xaidemograph (age,sex,edu,sessid) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $age, $sex, $edu, $sessid);
    if (!$stmt->execute()) {
      echo "Error saving entry: " . $stmt->error;
    }
    $stmt->close();

    print_main_view();

  } else if ($opcode == "transition") {
    set_session_flag($_POST["section"] ?? 'empty');
    print_main_view();
  } else if ($opcode == "email") {
    $email = $_POST["email"];

    // Prepare and execute the SQL query
    $sql = "UPDATE xaisession SET user_email = (?) where id=(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $sessid);
    if (!$stmt->execute()) {
      echo "Error finalizing session: " . $stmt->error;
    }
    $stmt->close();

    print_main_view();
  } else {
    // should never get here!
    if ($sessid != -1) {
      print_main_view();
    } else {
      print_login();
    }
  }

} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (array_key_exists("sessid", $_GET)) $sessid = $_GET["sessid"];
  if ($sessid != -1) {
    print_main_view();
  } else {
    print_login();
  }
}

$conn->close();
?>
</body>
</html>
