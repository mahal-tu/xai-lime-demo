<?php

/**
 * Fetch seen trial/question IDs for a session.
 */
function get_seen_ids($conn, $table, $sessid) {
    $sql = "SELECT DISTINCT trial_id as seen FROM $table WHERE sessid=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sessid);
    if (!$stmt->execute()) {
        echo "Error getting session: " . $stmt->error;
    }
    $stmt->bind_result($seen);
    $seen_trials = [];
    $intro_shown = false;
    while($stmt->fetch()) {
        if ($seen >= 0) {
            $seen_trials[] = $seen;
        } else {
            $intro_shown = true;
        }
    }
    $stmt->close();
    return [$seen_trials, $intro_shown];
}

/**
 * Pick a random open trial/question.
 */
function pick_rand_open_trial($all, $seen) {
    $open = [];
    for ($i = 0; $i < count($all); $i++) {
        if (!in_array($all[$i]['idx'], $seen)) {
            $open[] = $i;
        }
    }
    if (count($open) === 0) return null;
    return $open[rand(0, count($open)-1)];
}

function get_session_flag($flag): bool {
  global $conn;
  global $sessid;

  $json_flag = '$.'. $flag;
  $sql = "SELECT count(*) as flag FROM xaisession WHERE id=? AND JSON_EXTRACT(flags, ?) = true;";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $sessid, $json_flag);
  if (!$stmt->execute()) {
    echo "Error getting session: " . $stmt->error;
    return false;
  }
  $stmt->bind_result($flag);
  $stmt->fetch();
  $stmt->close();

  return $flag > 0;
}

function set_session_flag($flag) {
  global $conn;
  global $sessid;

  $json_flag = '$.'. $flag;
  $sql = "UPDATE xaisession SET flags = JSON_SET(flags, ?, true) WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $json_flag, $sessid);
  if (!$stmt->execute()) {
    echo "Error updating session: " . $stmt->error;
  }
  $stmt->close();
}

function get_vis_type(): string {
  global $conn;
  global $sessid;

  $sql = "SELECT vis_type FROM xaisession WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $sessid);
  if (!$stmt->execute()) {
    echo "Error getting session: " . $stmt->error;
    return "haf"; // default value
  }
  $stmt->bind_result($vis_type);
  $stmt->fetch();
  $stmt->close();

  return $vis_type;
}
