<?php

include_once 'common.php';
include_once 'i18n.php';

$knowledge = [
  ['idx' => 1, 'acronym' => 'GPT', 'solution' => 'Generative Pre-trained Transformer', 'distractors' => ['General Predictive Transformer', 'Generative Performance Tool', 'Guided Parsed Text-Generator'],],
  ['idx' => 2, 'acronym' => 'SVM', 'solution' => 'Support Vector Machine', 'distractors' => ['Supervised Vector Model', 'Software Version Management', 'Statistical Variance Measurement'],],
  ['idx' => 3, 'acronym' => 'LSTM', 'solution' => 'Long Short-Term Memory', 'distractors' => ['Linear Support Transformation Matrix', 'Large Scale Trading Method', 'Longitudinal Statistical Time Model'],],
  ['idx' => 4, 'acronym' => 'BLEU', 'solution' => 'BiLingual Evaluation Understudy', 'distractors' => ['Binary Logic Evaluation Unit', 'Bayesian Linguistic Entropy Unit', 'Balanced Load Enhancement Utility'],],
  ['idx' => 5, 'acronym' => 'DNN', 'solution' => 'Deep Neural Network', 'distractors' => ['Dynamic Network Node', 'Distributed Neural Network', 'Deterministic Network Node'],],
  ['idx' => 6, 'acronym' => 'PCA', 'solution' => 'Principal Component Analysis', 'distractors' => ['Predictive Classification Algorithm', 'Progressive Clustering Analysis', 'Primary Correlation Analysis']],
  ['idx' => 7, 'acronym' => 'VAE', 'solution' => 'Variational Auto-Encoder', 'distractors' => ['Vector Analysis Engine', 'Variable Assignment Engine', 'Virtual Architecture Encoder']],
];

// knowledge choice
$sql = "CREATE TABLE IF NOT EXISTS xaiknowdata (" .
  "id INT NOT NULL AUTO_INCREMENT, " .
  "time DATETIME NOT NULL DEFAULT UTC_TIMESTAMP, " .
  "trial_id INT NOT NULL, " .
  "target VARCHAR(255) NOT NULL, " .
  "choice VARCHAR(255) NOT NULL, " .
  "correct INT NOT NULL, " .
  "session_order INT NOT NULL, " .
  "sessid INT NOT NULL, " .
  "PRIMARY KEY (id)) ENGINE = InnoDB;";
$result = $conn->query($sql);

function print_know_question() {
  echo '<div>' . i18n('AbkuerzungFrage') . '</div>';
}

//! \return true if all trials have been completed
function print_knowledge_sel(): bool {
  global $conn;
  global $sessid;
  global $knowledge;

  list($seen_trials, $intro_shown) = get_seen_ids($conn, 'xaiknowdata', $sessid);
  $session_order = count($seen_trials);
  if ($session_order == count($knowledge)) return true;

  // if we get here, then there is still stuff to do
  print_header(i18n('KIWissen'), 100.0 * $session_order / count($knowledge));

  // show explanation if this is the first trial
  if(!$intro_shown) {
    // print intro trial
  echo '<div>' . i18n('Danke') . '</div>';
  echo '<div>' . i18n('KIWissenIntro') . '</div>';
  echo '<div>' . i18n('KIWissenErklaerung') . '</div>';
  echo '<div class="knowform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_knowledge">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="-1">';
  echo '<input type="hidden" name="trial_id" value="-1">';
  echo '<input type="hidden" name="correct" value="-1">';
  echo '<input type="hidden" name="choice" value="x">';
  echo '<input type="hidden" name="target" value="x">';
  echo '<button class="continuebtn" type="submit">' . i18n('Weiter') . '</button>';
  echo '</form></div>';

    return false;
  }

  $trial_id = pick_rand_open_trial($knowledge, $seen_trials);
  if ($trial_id === null) die("No more trials available.");

  print_know_question();

  echo '<div>' . i18n('WasBedeutetBeg') . ' <b>' . $knowledge[$trial_id]['acronym'] . '</b>' . i18n('WasBedeutetEnd') . '</div>';

  $options = $knowledge[$trial_id]['distractors'];
  array_push($options, $knowledge[$trial_id]['solution']);
  shuffle($options);

  foreach ($options as $option) {
    $correct = ($option == $knowledge[$trial_id]['solution']) ? 1 : 0;
    echo '<div class="knowform"><form method="post">';
    echo '<input type="hidden" name="opcode" value="sel_knowledge">';
    echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
    echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
    echo '<input type="hidden" name="trial_id" value="'. $knowledge[$trial_id]['idx'] . '">';
    echo '<input type="hidden" name="correct" value="' . $correct . '">';
    echo '<input type="hidden" name="choice" value="' . $option . '">';
    echo '<input type="hidden" name="target" value="' . $knowledge[$trial_id]['solution'] . '">';
  echo '<button type="submit" class="knowbtn">' . $option . '</button>';
    echo '</form></div>';
  }

  // add generic "I don't know" option at the bottom
  echo '<div class="knowform"><form method="post">';
  echo '<input type="hidden" name="opcode" value="sel_knowledge">';
  echo '<input type="hidden" name="sessid" value="'. $sessid . '">';
  echo '<input type="hidden" name="session_order" value="'. $session_order . '">';
  echo '<input type="hidden" name="trial_id" value="'. $knowledge[$trial_id]['idx'] . '">';
  echo '<input type="hidden" name="correct" value="-1">';
  echo '<input type="hidden" name="choice" value="Weiß nicht">';
  echo '<input type="hidden" name="target" value="' . $knowledge[$trial_id]['solution'] . '">';
  echo '<button type="submit" class="knowbtn">' . i18n('WeissNicht') . '</button>';
  echo '</form></div>';

  return false;
}

function on_sel_knowledge() {
  global $conn;
  global $sessid;

  // Get all the data
  $trial_id = $_POST["trial_id"];
  $session_order = $_POST["session_order"];
  $target = $_POST["target"];
  $choice = $_POST["choice"];
  $correct = $_POST["correct"];

  // Prepare and execute the SQL query
  $sql = "INSERT INTO xaiknowdata (trial_id,target,choice,correct,session_order,sessid) VALUES (?,?,?,?,?,?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("issiii", $trial_id, $target, $choice, $correct, $session_order, $sessid);
  if (!$stmt->execute()) {
    echo "Error saving entry: " . $stmt->error;
  }
  $stmt->close();
}

?>
