<?php

include_once 'common.php';
include_once 'i18n.php';

include_once 'cases.php';

// lime choice
$sql = "CREATE TABLE IF NOT EXISTS xaidata (" .
  "id INT NOT NULL AUTO_INCREMENT, " .
  "time DATETIME NOT NULL DEFAULT UTC_TIMESTAMP, " .
  "trial_id INT NOT NULL, " .
  "trial_type VARCHAR(4) NOT NULL, " .
  "vis_type VARCHAR(4) NOT NULL, " .
  "with_pct INT NOT NULL, " .
  "trial_target INT NOT NULL, " .
  "clsf_id INT NOT NULL, " .
  "leftright INT NOT NULL, " .
  "session_order INT NOT NULL, " .
  "sessid INT NOT NULL, " .
  "PRIMARY KEY (id)) ENGINE = InnoDB;";
$result = $conn->query($sql);

function print_lime_question() {
  echo '<div>' . i18n('KITrainiert') . '</div>' .
    '<div><b>' . i18n('BessereKI') . '</b><br>' . i18n('WaehlenAus') . '</div>';
}

function print_lime_explanation($vis_type) {
  echo '<div class="explanation">' .
    ($vis_type === 'haf' ? i18n('ErklaerungHAF') : i18n('ErklaerungMAF')) .
    '</div>';
}

function get_trial($session_order) {
  global $conn;
  global $sessid;

  $sql = "SELECT trial_id as trial, clsf_id as clf, leftright as lr FROM xaidata WHERE sessid=? and session_order=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $sessid, $session_order);
  if (!$stmt->execute()) {
    echo "Error getting session: " . $stmt->error;
  }
  $stmt->bind_result($trial, $clf, $lr);
  $stmt->fetch();
  $stmt->close();
  return ['idx' => $trial, 'clf' => $clf, 'lr' => $lr];
}

function get_images($training, $trial_id) {
  foreach ($training as $trial) {
    if ($trial['idx'] == $trial_id) {
      return [$trial['clsf_1'], $trial['clsf_2']];
    }
  }
  die("No images found for trial ID: $trial_id");
}

//! \return true if all trials have been completed
function print_training($vis_type): bool {
  global $conn;
  global $sessid;
  global $training_cases_haf_p0;
  global $training_cases_maf_p0;

  $training = $vis_type === 'haf' ? $training_cases_haf_p0 : $training_cases_maf_p0;

  list($seen_trials, $intro_shown) = get_seen_ids($conn, 'xaidata', $sessid);
  $session_order = count($seen_trials);

  if($session_order > 0 && $session_order <= count($training)) {
    // print feedback for last trial
    $transition_flag = 'train_' . $session_order;
    if(get_session_flag($transition_flag) != true) {
      print_header(i18n('Training'), 100.0 * $session_order / count($training));

      #echo '<div>' . i18n('KITrainiert') . '</div>';

      $last_trial_data = get_trial($session_order - 1);
      # echo '<div>' . $last_trial_data['idx'] . '; ' . $last_trial_data['clf'] . '; ' . $last_trial_data['lr'] . '</div>';
      $order_reversed = false;
      if($last_trial_data['clf'] == 1) {
        echo '<div>' . i18n('Richtig') . '</div>';
        if($last_trial_data['lr'] !== 1) {
          $order_reversed = true;
        }
      } else {
        echo '<div>' . i18n('Falsch') . '</div>';
        if($last_trial_data['lr'] !== 2) {
          $order_reversed = true;
        }
      }
      $images = get_images($training, $last_trial_data['idx']);
      echo '<div id="button-container">';
      if ($order_reversed) {
        echo '<img class="wrong" src="' . $images[1] . '"><img class="correct" src="' . $images[0] . '">';
      } else {
        echo '<img class="correct" src="' . $images[0] . '"><img class="wrong" src="' . $images[1] . '">';
      }
      echo '</div>';

      if($session_order == count($training)) {
        echo '<div>' . i18n('TrainingAbgeschlossen') . '</div>';
      } else {
        # make sure that the button is not scrolled outside at the end of the training
        print_lime_explanation($vis_type);
      }

      echo '<form method="post">';
      echo '<input type="hidden" name="sessid" value="' . $sessid . '">';
      echo '<input type="hidden" name="opcode" value="transition">';
      echo '<input type="hidden" name="section" value="' . $transition_flag . '">';
      echo '<div><button class="continuebtn" type="submit">' . i18n('Weiter') . '</button></div>';
      echo '</form>';

      return false;
    }
  }

  // are we done?
  if ($session_order >= count($training)) return true;

  // if we get here, then there is still stuff to do
  print_header(i18n('Training'), 100.0 * $session_order / count($training));

  // print the actual trial
  print_lime_question();

  $trial_id = pick_rand_open_trial($training, $seen_trials);
  if ($trial_id === null) die("No more training trials available.");

  $classifiers = ['clsf_1', 'clsf_2'];
  $clsf_id = [1, 2];
  $left = rand(0, count($classifiers)-1);

  $img_l = $training[$trial_id][$classifiers[$left]];
  $img_r = $training[$trial_id][$classifiers[1-$left]];

  echo '<div id="button-container">';
  echo '<div class="limeform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_img">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
  echo '<input type="hidden" name="trial_id" value="'. $training[$trial_id]['idx'] . '">';
  echo '<input type="hidden" name="trial_type" value="'. $training[$trial_id]['trial_type'] . '">';
  echo '<input type="hidden" name="trial_target" value="'. $training[$trial_id]['trial_target'] . '">';
  echo '<input type="hidden" name="vis_type" value="'. $training[$trial_id]['vis_type'] . '">';
  echo '<input type="hidden" name="with_pct" value="'. $training[$trial_id]['with_pct'] . '">';
  echo '<input type="hidden" name="leftright" value="1">';
  echo '<input type="hidden" name="clsf_id" value="'. $clsf_id[$left] .'">';
  echo '<button type="submit" class="limebtn"><img src="' . $img_l . '"></button>';
  echo '</form></div>';

  echo '<div class="limeform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_img">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
  echo '<input type="hidden" name="trial_id" value="'. $training[$trial_id]['idx'] . '">';
  echo '<input type="hidden" name="trial_type" value="'. $training[$trial_id]['trial_type'] . '">';
  echo '<input type="hidden" name="trial_target" value="'. $training[$trial_id]['trial_target'] . '">';
  echo '<input type="hidden" name="vis_type" value="'. $training[$trial_id]['vis_type'] . '">';
  echo '<input type="hidden" name="with_pct" value="'. $training[$trial_id]['with_pct'] . '">';
  echo '<input type="hidden" name="leftright" value="2">';
  echo '<input type="hidden" name="clsf_id" value="'. $clsf_id[1-$left] .'">';
  echo '<button type="submit" class="limebtn"><img src="' . $img_r . '"></b></button>';
  echo '</form></div>';
  echo '</div>';

  print_lime_explanation($vis_type);

  return false;
}


//! \return true if all trials have been completed
function print_lime_sel($vis_type): bool {
  global $conn;
  global $sessid;
  global $trial_cases_haf_p0;
  global $training_cases_haf_p0;
  global $trial_cases_maf_p0;
  global $training_cases_maf_p0;

  $training = $vis_type === 'haf' ? $training_cases_haf_p0 : $training_cases_maf_p0;
  $trials = $vis_type === 'haf' ? $trial_cases_haf_p0 : $trial_cases_maf_p0;

  list($seen_trials, $intro_shown) = get_seen_ids($conn, 'xaidata', $sessid);
  $session_order = count($seen_trials);
  if ($session_order == count($trials) + count($training)) return true;

  // if we get here, then there is still stuff to do
  print_header(i18n('KIVergleichen'), 100.0 * ($session_order-count($training)) / count($trials));

  print_lime_question();

  $trial_id = pick_rand_open_trial($trials, $seen_trials);
  if ($trial_id === null) die("No more trials available.");

  $classifiers = ['clsf_1', 'clsf_2'];
  $clsf_id = [1, 2];
  $left = rand(0, count($classifiers)-1);

  $img_l = $trials[$trial_id][$classifiers[$left]];
  $img_r = $trials[$trial_id][$classifiers[1-$left]];

  echo '<div id="button-container">';
  echo '<div class="limeform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_img">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
  echo '<input type="hidden" name="trial_id" value="'. $trials[$trial_id]['idx'] . '">';
  echo '<input type="hidden" name="trial_type" value="'. $trials[$trial_id]['trial_type'] . '">';
  echo '<input type="hidden" name="trial_target" value="'. $trials[$trial_id]['trial_target'] . '">';
  echo '<input type="hidden" name="vis_type" value="'. $trials[$trial_id]['vis_type'] . '">';
  echo '<input type="hidden" name="with_pct" value="'. $trials[$trial_id]['with_pct'] . '">';
  echo '<input type="hidden" name="leftright" value="1">';
  echo '<input type="hidden" name="clsf_id" value="'. $clsf_id[$left] .'">';
  echo '<button type="submit" class="limebtn"><img src="' . $img_l . '"></button>';
  echo '</form></div>';

  echo '<div class="limeform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_img">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
  echo '<input type="hidden" name="trial_id" value="'. $trials[$trial_id]['idx'] . '">';
  echo '<input type="hidden" name="trial_type" value="'. $trials[$trial_id]['trial_type'] . '">';
  echo '<input type="hidden" name="trial_target" value="'. $trials[$trial_id]['trial_target'] . '">';
  echo '<input type="hidden" name="vis_type" value="'. $trials[$trial_id]['vis_type'] . '">';
  echo '<input type="hidden" name="with_pct" value="'. $trials[$trial_id]['with_pct'] . '">';
  echo '<input type="hidden" name="leftright" value="2">';
  echo '<input type="hidden" name="clsf_id" value="'. $clsf_id[1-$left] .'">';
  echo '<button type="submit" class="limebtn"><img src="' . $img_r . '"></b></button>';
  echo '</form></div>';
  echo '</div>';

  print_lime_explanation($vis_type);

  return false;
}

function on_sel_image() {
  global $conn;
  global $sessid;

  // Get all the data
  $trial_id = $_POST["trial_id"];
  $trial_type = $_POST["trial_type"];
  $trial_target = $_POST["trial_target"];
  $vis_type = $_POST["vis_type"];
  $with_pct = $_POST["with_pct"];
  $clsf_id = $_POST["clsf_id"];
  $leftright = $_POST["leftright"];
  $session_order = $_POST["session_order"];

  // Prepare and execute the SQL query
  $sql = "INSERT INTO xaidata (trial_id,trial_type,vis_type,with_pct,trial_target,clsf_id,leftright,session_order,sessid) VALUES (?,?,?,?,?,?,?,?,?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("issiiiiii", $trial_id, $trial_type, $vis_type, $with_pct, $trial_target, $clsf_id, $leftright, $session_order, $sessid);
  if (!$stmt->execute()) {
    echo "Error saving entry: " . $stmt->error;
  }
  $stmt->close();
}
?>
