<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/protected/pdo.php";
require_once __DIR__ . "/card_designs_v2_lib.php";

if (empty($_SESSION["user_id"])) {
  hid_v2_json(["success" => false, "error" => "Not logged in"], 401);
}

$userId = (int)$_SESSION["user_id"];
$cardId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($cardId <= 0) hid_v2_json(["success" => false, "error" => "Missing id"], 400);

try {
  $stmt = $pdo->prepare("
    SELECT
      sc.*,
      COALESCE(sc.verse_text, bv.verse_text) AS verse_text
    FROM card_designs_v2 sc
    LEFT JOIN bible_verses bv
      ON bv.verse_reference = sc.favorite_verse_ref
    WHERE sc.id = ?
      AND sc.owner_type = 'user'
      AND sc.user_site_id = ?
    LIMIT 1
  ");
  $stmt->execute([$cardId, $userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) hid_v2_json(["success" => false, "error" => "Not found"], 404);

  $row["received_jesus"] = $row["received_jesus_date"] ?? "";
  $row["front_theme_path"] = hid_v2_asset_path("newcrdbg", (string)($row["front_theme_file"] ?? ""));
  $row["back_theme_path"] = hid_v2_asset_path("backcrdbg", (string)($row["back_theme_file"] ?? ""));
  $row["foreground_path"] = hid_v2_asset_path("foreground", (string)($row["foreground_file"] ?? ""));

  hid_v2_json(["success" => true, "card" => $row]);
} catch (Throwable $e) {
  hid_v2_json(["success" => false, "error" => "DB error"], 500);
}
