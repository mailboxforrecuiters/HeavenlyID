<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/*
  /dashboard shared staff auth helpers.

  Important:
  The login page is hardcoded and does not need the database just to verify credentials.
  The database is loaded only after staff login is confirmed, so a PDO/path issue will not block the login check itself.
*/

function hid_staff_bootstrap_db(): void {
  global $pdo;

  if (isset($pdo) && $pdo instanceof PDO) {
    return;
  }

  $hidPdoPath = __DIR__ . "/../protected/pdo.php";
  if (!is_file($hidPdoPath)) {
    $hidPdoPath = dirname(__DIR__) . "/protected/pdo.php";
  }

  require_once $hidPdoPath;
}

function hid_staff_h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

function hid_staff_asset_file($value): string {
  $s = trim((string)$value);
  if ($s === "") return "";
  $s = str_replace("\\", "/", $s);
  $base = basename($s);
  return preg_match('/^[A-Za-z0-9._ -]+\.(png|jpe?g|webp|gif)$/i', $base) ? $base : "";
}

function hid_staff_asset_path(string $file, array $dirs): string {
  $file = hid_staff_asset_file($file);
  if ($file === "") return "";

  foreach ($dirs as $dir) {
    $dir = trim($dir, "/");
    $abs = dirname(__DIR__) . "/" . $dir . "/" . $file;
    if (is_file($abs)) return "/" . $dir . "/" . rawurlencode($file);
  }

  $fallback = trim((string)($dirs[0] ?? ""), "/");
  return $fallback !== "" ? "/" . $fallback . "/" . rawurlencode($file) : rawurlencode($file);
}

function hid_staff_format_date($iso): string {
  $s = trim((string)$iso);
  if ($s === "") return "";
  $ts = strtotime($s);
  if (!$ts) return $s;

  $d = (int)date("j", $ts);
  $suffix = "th";
  if (!($d >= 11 && $d <= 13)) {
    if ($d % 10 === 1) $suffix = "st";
    elseif ($d % 10 === 2) $suffix = "nd";
    elseif ($d % 10 === 3) $suffix = "rd";
  }

  return date("M ", $ts) . $d . $suffix . date(", Y", $ts);
}

function hid_staff_current(): ?array {
  if (empty($_SESSION["hid_staff_id"])) return null;

  return [
    "id" => (int)$_SESSION["hid_staff_id"],
    "name" => (string)($_SESSION["hid_staff_name"] ?? ""),
    "email" => (string)($_SESSION["hid_staff_email"] ?? ""),
    "role" => (string)($_SESSION["hid_staff_role"] ?? ""),
  ];
}

function hid_staff_require_login(): array {
  $staff = hid_staff_current();
  if (!$staff) {
    header("Location: index.php");
    exit;
  }

  hid_staff_bootstrap_db();
  return $staff;
}

function hid_staff_owner_label(array $row): string {
  $ownerType = (string)($row["owner_type"] ?? "");
  if ($ownerType === "user") {
    $name = trim((string)($row["first_name"] ?? "") . " " . (string)($row["last_name"] ?? ""));
    $email = trim((string)($row["email"] ?? ""));
    if ($name !== "" && $email !== "") return $name . " <" . $email . ">";
    if ($name !== "") return $name;
    if ($email !== "") return $email;
    return "Registered User #" . (int)($row["user_site_id"] ?? 0);
  }

  $guestEmail = trim((string)($row["guest_email"] ?? ""));
  return $guestEmail !== "" ? "Guest <" . $guestEmail . ">" : "Guest";
}

function hid_staff_owner_url(array $row): string {
  $ownerType = (string)($row["owner_type"] ?? "");
  if ($ownerType === "user") {
    return "dashboard.php?owner_type=user&user_site_id=" . urlencode((string)(int)($row["user_site_id"] ?? 0));
  }
  return "dashboard.php?owner_type=guest&guest_email=" . urlencode((string)($row["guest_email"] ?? ""));
}

function hid_staff_extract_letter_body(string $letter, string $fullName): array {
  $letter = trim($letter);
  $fullName = trim($fullName);
  $prefix = $fullName !== "" ? "I " . $fullName : "I";

  $body = $letter;
  if ($fullName !== "" && stripos($body, $prefix) === 0) {
    $body = trim(substr($body, strlen($prefix)));
  }

  return [$prefix, $body];
}
