<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/protected/pdo.php";
require_once __DIR__ . "/card_designs_v2_lib.php";

if (empty($_SESSION["user_id"])) {
  hid_v2_json(["success" => false, "error" => "Not logged in"], 401);
}

$userId = (int)$_SESSION["user_id"];
$raw = file_get_contents("php://input");
$data = json_decode($raw ?: "{}", true);
if (!is_array($data)) hid_v2_json(["success" => false, "error" => "Invalid JSON"], 400);

$savedId = (int)($data["saved_id"] ?? 0);
$n = hid_v2_normalize_payload($data);

if ($n["design_title"] === "" || mb_strlen($n["design_title"]) > 120) {
  hid_v2_json(["success" => false, "error" => "Design title is required (max 120 chars)."], 400);
}
if ($n["full_name"] === "") {
  hid_v2_json(["success" => false, "error" => "Full Name is required."], 400);
}
if ($n["front_theme_file"] === "") {
  hid_v2_json(["success" => false, "error" => "Please select a front card graphic."], 400);
}

$payload = $n;
$payload["saved_id"] = $savedId > 0 ? $savedId : null;
$payload["owner_type"] = "user";
$payload["user_site_id"] = $userId;
$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($payloadJson === false) $payloadJson = "{}";

try {
  if ($savedId > 0) {
    $chk = $pdo->prepare("
      SELECT id
      FROM card_designs_v2
      WHERE id = ?
        AND owner_type = 'user'
        AND user_site_id = ?
      LIMIT 1
    ");
    $chk->execute([$savedId, $userId]);
    if (!$chk->fetchColumn()) {
      hid_v2_json(["success" => false, "error" => "Design not found or not yours."], 403);
    }

    $stmt = $pdo->prepare("
      UPDATE card_designs_v2
      SET design_title = ?,
          full_name = ?,
          iam_status = ?,
          spiritual_gifts = ?,
          received_jesus_date = ?,
          favorite_verse_ref = ?,
          verse_text = ?,
          letter_of_intent = ?,
          name_font_resized = ?,
          name_font_size_px = ?,
          name_layout_left_px = ?,
          name_layout_top_px = ?,
          name_layout_width_px = ?,
          name_layout_height_px = ?,
          name_safe_right_px = ?,
          name_available_width_px = ?,
          name_text_align = ?,
          name_padding_left_px = ?,
          letter_font_resized = ?,
          letter_font_size_px = ?,
          front_theme_file = ?,
          back_theme_file = ?,
          front_theme_style = ?,
          foreground_file = ?,
          payload_json = ?,
          updated_at = NOW()
      WHERE id = ?
        AND owner_type = 'user'
        AND user_site_id = ?
      LIMIT 1
    ");
    $stmt->execute([
      $n["design_title"],
      $n["full_name"],
      $n["iam_status"],
      $n["spiritual_gifts"],
      $n["received_jesus_date"],
      $n["favorite_verse_ref"],
      $n["verse_text"] !== "" ? $n["verse_text"] : null,
      $n["letter_of_intent"] !== "" ? $n["letter_of_intent"] : null,
      $n["name_font_resized"],
      $n["name_font_size_px"],
      $n["name_layout_left_px"],
      $n["name_layout_top_px"],
      $n["name_layout_width_px"],
      $n["name_layout_height_px"],
      $n["name_safe_right_px"],
      $n["name_available_width_px"],
      $n["name_text_align"],
      $n["name_padding_left_px"],
      $n["letter_font_resized"],
      $n["letter_font_size_px"],
      $n["front_theme_file"],
      $n["back_theme_file"],
      $n["front_theme_style"],
      $n["foreground_file"],
      $payloadJson,
      $savedId,
      $userId
    ]);

    hid_v2_json(["success" => true, "saved_id" => $savedId]);
  }

  $stmt = $pdo->prepare("
    INSERT INTO card_designs_v2
    (owner_type, user_site_id, design_title, full_name, iam_status, spiritual_gifts, received_jesus_date,
     favorite_verse_ref, verse_text, letter_of_intent, name_font_resized, name_font_size_px,
     name_layout_left_px, name_layout_top_px, name_layout_width_px, name_layout_height_px,
     name_safe_right_px, name_available_width_px, name_text_align, name_padding_left_px,
     letter_font_resized, letter_font_size_px, front_theme_file, back_theme_file, front_theme_style,
     foreground_file, payload_json, created_at, updated_at)
    VALUES
    ('user', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
  ");
  $stmt->execute([
    $userId,
    $n["design_title"],
    $n["full_name"],
    $n["iam_status"],
    $n["spiritual_gifts"],
    $n["received_jesus_date"],
    $n["favorite_verse_ref"],
    $n["verse_text"] !== "" ? $n["verse_text"] : null,
    $n["letter_of_intent"] !== "" ? $n["letter_of_intent"] : null,
    $n["name_font_resized"],
    $n["name_font_size_px"],
    $n["name_layout_left_px"],
    $n["name_layout_top_px"],
    $n["name_layout_width_px"],
    $n["name_layout_height_px"],
    $n["name_safe_right_px"],
    $n["name_available_width_px"],
    $n["name_text_align"],
    $n["name_padding_left_px"],
    $n["letter_font_resized"],
    $n["letter_font_size_px"],
    $n["front_theme_file"],
    $n["back_theme_file"],
    $n["front_theme_style"],
    $n["foreground_file"],
    $payloadJson
  ]);

  hid_v2_json(["success" => true, "saved_id" => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  hid_v2_json(["success" => false, "error" => "Server error saving design."], 500);
}
