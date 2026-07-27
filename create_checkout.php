<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/protected/pdo.php";
require_once __DIR__ . "/card_designs_v2_lib.php";

if (empty($_SESSION["user_id"])) {
  hid_v2_json(["success" => false, "error" => "Not logged in"], 401);
}

$userId = (int)$_SESSION["user_id"];
$savedId = (int)($_POST["saved_id"] ?? 0);
if ($savedId <= 0) hid_v2_json(["success" => false, "error" => "Missing saved_id"], 400);

$checkoutToken = hid_v2_token64();

$stmt = $pdo->prepare("
  UPDATE card_designs_v2
  SET checkout_token = ?,
      checkout_token_created_at = NOW(),
      updated_at = NOW()
  WHERE id = ?
    AND owner_type = 'user'
    AND user_site_id = ?
  LIMIT 1
");
$stmt->execute([$checkoutToken, $savedId, $userId]);

if ($stmt->rowCount() < 1) {
  hid_v2_json(["success" => false, "error" => "Design not found or not yours."], 404);
}

hid_v2_json([
  "success" => true,
  "token" => $checkoutToken,
  "url" => "/successful_pay.php?t=" . urlencode($checkoutToken)
]);
