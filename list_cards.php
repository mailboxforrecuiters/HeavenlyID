<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/protected/pdo.php";
require_once __DIR__ . "/card_designs_v2_lib.php";

if (empty($_SESSION["user_id"])) {
  hid_v2_json(["success" => true, "cards" => []]);
}

$userId = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("
  SELECT id, design_title, created_at, updated_at, is_paid, paid_at, download_token
  FROM card_designs_v2
  WHERE owner_type = 'user'
    AND user_site_id = ?
  ORDER BY COALESCE(updated_at, created_at) DESC
");
$stmt->execute([$userId]);

hid_v2_json(["success" => true, "cards" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
