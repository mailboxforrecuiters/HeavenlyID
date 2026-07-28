<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

$staff = hid_staff_require_login();
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  exit("Missing design id.");
}

$stmt = $pdo->prepare("
  SELECT
    c.*,
    u.first_name,
    u.last_name,
    u.email,
    u.address,
    u.city,
    u.state,
    u.zipcode
  FROM card_designs_v2 c
  LEFT JOIN users u
    ON u.site_id = c.user_site_id
  WHERE c.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  exit("Design not found.");
}

$frontPath = hid_staff_asset_path((string)($row["front_theme_file"] ?? ""), ["newcrdbg"]);
$backPath = hid_staff_asset_path((string)($row["back_theme_file"] ?? ""), ["newcrdbg", "backcrdbg"]);
$foregroundPath = hid_staff_asset_path((string)($row["foreground_file"] ?? ""), ["foreground"]);

$fullName = (string)($row["full_name"] ?? "");
$spiritualGifts = (string)($row["spiritual_gifts"] ?? "");
$received = hid_staff_format_date($row["received_jesus_date"] ?? "");
$verse = (string)($row["favorite_verse_ref"] ?? "");
$letter = (string)($row["letter_of_intent"] ?? "");
[$letterPrefix, $letterBody] = hid_staff_extract_letter_body($letter, $fullName);

$ownerUrl = hid_staff_owner_url($row);
$ownerLabel = hid_staff_owner_label($row);

$shipAddress = trim((string)($row["address"] ?? ""));
$shipCity = trim((string)($row["city"] ?? ""));
$shipState = trim((string)($row["state"] ?? ""));
$shipZip = trim((string)($row["zipcode"] ?? ""));
$shipLine2 = trim(implode(", ", array_filter([$shipCity, $shipState])));
if ($shipZip !== "") {
  $shipLine2 = trim($shipLine2 . " " . $shipZip);
}
$hasShipping = ($shipAddress !== "" || $shipLine2 !== "");

$nameFontSize = (float)($row["name_font_size_px"] ?? 82);
if ($nameFontSize <= 0) $nameFontSize = 82;
$letterFontSize = (float)($row["letter_font_size_px"] ?? 18);
if ($letterFontSize <= 0) $letterFontSize = 18;

$payload = [];
$payloadRaw = (string)($row["payload_json"] ?? "");
if ($payloadRaw !== "") {
  $decoded = json_decode($payloadRaw, true);
  if (is_array($decoded)) $payload = $decoded;
}

function hid_staff_payload_lookup(array $payload, array $keys, $default = "") {
  $scopes = [$payload];
  foreach (["fields", "layout", "name_layout", "nameLayout", "print", "card", "data"] as $scopeKey) {
    if (isset($payload[$scopeKey]) && is_array($payload[$scopeKey])) $scopes[] = $payload[$scopeKey];
  }
  foreach ($scopes as $scope) {
    foreach ($keys as $key) {
      if (isset($scope[$key]) && $scope[$key] !== "") return $scope[$key];
    }
  }
  return $default;
}

function hid_staff_float_clamp($value, float $default, float $min, float $max): float {
  if ($value === null || $value === "") return $default;
  if (is_string($value)) $value = preg_replace('/[^0-9.\-]/', '', $value);
  $num = (float)$value;
  if ($num <= 0 && $min > 0) $num = $default;
  return max($min, min($max, $num));
}

function hid_staff_text_align($value, string $default = "left"): string {
  $value = strtolower(trim((string)$value));
  return in_array($value, ["left", "center", "right"], true) ? $value : $default;
}

function hid_staff_year_only($value): string {
  $s = trim((string)$value);
  if ($s === "") return "";
  if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', $s, $m)) return $m[1];
  if (preg_match('/\b(\d{4})\b/', $s, $m)) return $m[1];
  return $s;
}

$received = hid_staff_year_only($received);

$nameFontSize = hid_staff_float_clamp(
  hid_staff_payload_lookup($payload, ["name_font_size_px", "nameFontSizePx", "nameFontSize"], $row["name_font_size_px"] ?? $nameFontSize),
  $nameFontSize,
  20,
  88
);

$nameFontResized = (string)hid_staff_payload_lookup($payload, ["name_font_resized", "nameFontResized"], $row["name_font_resized"] ?? "0");
$nameLayoutLeft = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_layout_left_px", "nameLayoutLeftPx", "name_left_px"], $row["name_layout_left_px"] ?? ""), 405, 0, 1000);
$nameLayoutTop = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_layout_top_px", "nameLayoutTopPx", "name_top_px"], $row["name_layout_top_px"] ?? ""), 252, 0, 655);
$nameLayoutWidth = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_layout_width_px", "nameLayoutWidthPx", "name_width_px"], $row["name_layout_width_px"] ?? ""), 473, 80, 720);
$nameLayoutHeight = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_layout_height_px", "nameLayoutHeightPx", "name_height_px"], $row["name_layout_height_px"] ?? ""), 112, 32, 160);
$nameSafeRight = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_safe_right_px", "nameSafeRightPx"], $row["name_safe_right_px"] ?? ""), 896, 300, 1000);
$nameAvailableWidth = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_available_width_px", "nameAvailableWidthPx"], $row["name_available_width_px"] ?? ""), $nameLayoutWidth, 80, 720);
$nameTextAlign = hid_staff_text_align(hid_staff_payload_lookup($payload, ["name_text_align", "nameTextAlign"], $row["name_text_align"] ?? "left"), "left");
$namePaddingLeft = hid_staff_float_clamp(hid_staff_payload_lookup($payload, ["name_padding_left_px", "namePaddingLeftPx"], $row["name_padding_left_px"] ?? ""), 0, 0, 80);

$frontCardW = 1000;
$frontCardH = 655;
$infoLeft = 405;
$infoTop = 252;
$infoWidth = 630;
$rowsTop = $nameLayoutTop + $nameLayoutHeight + 43;

$defaultLetterIntentBody = "declare that I am a Citizen of Heaven, Chosen by God, redeemed by Christ, led by the Spirit & Committed to walk in faithful obedience to Jesus Wherever I go.";
$letterFull = trim($letter);
if ($letterFull === "" || preg_match('/^I\s+' . preg_quote($fullName, '/') . '\s*$/i', $letterFull)) {
  $letterBody = $defaultLetterIntentBody;
} else {
  $letterBody = preg_replace('/^\s*I\s+' . preg_quote($fullName, '/') . '\s*/i', '', $letterFull);
  $letterBody = trim((string)$letterBody);
  if ($letterBody === "") $letterBody = $defaultLetterIntentBody;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= hid_staff_h($row["design_title"] ?: "Design") ?> — Staff View</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@500;600;700;800&display=swap');
    @font-face{font-family:"Minion Pro";src:url("../MinionPro-Medium.woff") format("woff");font-weight:500;font-style:normal;font-display:swap}
    @font-face{font-family:"Bw Modelica Extra Bold";src:local("Bw Modelica Extra Bold"),local("BwModelica ExtraBold"),url("../BwModelica-ExtraBold.woff2") format("woff2"),url("../bw-modelica-extra-bold.woff2") format("woff2");font-weight:800;font-style:normal;font-display:swap}
    @font-face{font-family:"Bw Modelica Regular";src:local("Bw Modelica Regular"),local("BwModelica Regular"),url("../BwModelica-Regular.woff2") format("woff2"),url("../bw-modelica-regular.woff2") format("woff2");font-weight:400;font-style:normal;font-display:swap}
    @font-face{font-family:"Aurelly Signature";src:local("Aurelly Signature"),url("../AurellySignature.woff2") format("woff2"),url("../Aurelly Signature.woff2") format("woff2");font-weight:400;font-style:normal;font-display:swap}

    :root{--blue:#1d3468;--brown:#5f452f;--gold:#d8ae55;--cream:rgba(255,255,255,.58);--line:rgba(216,174,85,.36);--shadow:0 18px 42px rgba(67,51,39,.14)}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;font-family:"Minion Pro",Georgia,serif;color:var(--brown);background:#f6f1ef url("../bgtest.jpg") center center / cover no-repeat}
    .top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.86);border-bottom:1px solid var(--line);backdrop-filter:blur(8px);padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .brand{font-size:24px;font-weight:900;color:var(--blue)}
    .btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 13px;border-radius:12px;border:1px solid rgba(0,0,0,.1);text-decoration:none;font-weight:900;background:#1d3468;color:#fff}
    .btn.dark{background:#222}
    .page{max-width:1480px;margin:0 auto;padding:22px 18px 40px}
    .breadcrumbs{font-size:15px;font-weight:800;margin-bottom:14px;color:#5f452f}
    .breadcrumbs a{color:var(--blue);text-decoration:none}
    .panel{background:var(--cream);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow);backdrop-filter:blur(8px);padding:18px;margin-bottom:18px}
    h1{margin:0 0 8px;color:var(--blue);font-size:42px;line-height:1}
    .meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px}
    .meta div{background:rgba(255,255,255,.5);border:1px solid rgba(216,174,85,.24);border-radius:14px;padding:10px}
    .shipping-info{margin-top:12px;background:rgba(255,255,255,.5);border:1px solid rgba(216,174,85,.24);border-radius:14px;padding:12px}
    .shipping-lines{font-size:18px;font-weight:800;color:#1d3468;line-height:1.35}
    .label{font-size:12px;text-transform:uppercase;font-weight:900;letter-spacing:.05em;color:#6f5336}
    .value{font-size:16px;font-weight:800;color:#1d3468;word-break:break-word}
    .views{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start}
    .card-shell{background:rgba(255,255,255,.68);border:1px solid rgba(216,174,85,.3);border-radius:20px;padding:14px;overflow:auto}
    .card-title{font-size:22px;font-weight:900;color:var(--blue);margin:0 0 10px}
    .scale-wrap{width:100%;overflow:auto}
    .scaled{width:<?= (int)$frontCardW ?>px;height:<?= (int)$frontCardH ?>px;transform:scale(.58);transform-origin:top left;margin-bottom:-275px}
    .card-wrapper{width:<?= (int)$frontCardW ?>px;height:<?= (int)$frontCardH ?>px;position:relative;color:#000}
    .card-face{position:absolute;inset:0}
    .card-front{background-color:#f6edd7;background-image:url("<?= hid_staff_h($frontPath) ?>");background-repeat:no-repeat;background-position:center center;background-size:100% 100%;border-radius:28px;overflow:hidden;box-shadow:0 18px 45px rgba(64,48,38,.12)}
    .card-back{border-radius:28px;overflow:hidden;box-shadow:0 18px 45px rgba(64,48,38,.12)}
    .card-back img.back-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;border-radius:28px}
    .foreground-section{position:absolute;left:154px;top:207px;width:215px;aspect-ratio:708/1163;z-index:6;display:flex;align-items:center;justify-content:center;overflow:visible;background:transparent}
    .foreground-section img{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;object-position:center center;border:0;background:transparent}
    .info-block{position:absolute;top:0;left:0;width:100%;height:100%;display:block;z-index:6;pointer-events:none}
    .name-value{position:absolute;left:<?= hid_staff_h($nameLayoutLeft) ?>px;top:<?= hid_staff_h($nameLayoutTop) ?>px;width:<?= hid_staff_h($nameLayoutWidth) ?>px;height:<?= hid_staff_h($nameLayoutHeight) ?>px;display:flex;align-items:center;justify-content:<?= $nameTextAlign === "center" ? "center" : ($nameTextAlign === "right" ? "flex-end" : "flex-start") ?>;color:#d01818;font-family:"Aurelly Signature","Great Vibes",cursive;font-size:<?= hid_staff_h($nameFontSize) ?>px;font-weight:400;line-height:.92;text-align:<?= hid_staff_h($nameTextAlign) ?>;padding-left:<?= hid_staff_h($namePaddingLeft) ?>px;white-space:nowrap;overflow:visible;text-shadow:0 1px 0 rgba(255,255,255,.20)}
    .info-row{display:grid;grid-template-columns:230px minmax(0,1fr);column-gap:34px;align-items:center;justify-content:initial;width:630px;min-height:34px;margin:0 0 18px 0;gap:0}
    .info-row strong{min-width:0;width:230px;white-space:nowrap;font-size:24px;line-height:1.05;color:#182f66;font-family:"Bw Modelica Extra Bold","Montserrat",Arial,sans-serif;font-weight:800}
    .card-input-value{width:100%;font-size:24px;line-height:1.05;color:#182f66;font-family:"Bw Modelica Regular","Montserrat",Arial,sans-serif;font-weight:400;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .letter-intent{position:absolute;top:132px;left:124px;width:720px;height:190px;border-radius:0;overflow:visible;background:transparent;z-index:4}
    .letter-editor-shell{width:100%;height:auto;padding:0;color:#223a71;font-family:"Bw Modelica Regular","Montserrat",Arial,sans-serif;font-size:24px;line-height:1.42;text-align:left;word-wrap:normal;overflow:visible;display:block;background:transparent}
    .letter-fixed-prefix{display:inline;color:#223a71;font-family:"Bw Modelica Regular","Montserrat",Arial,sans-serif;font-size:24px;line-height:1.42;font-weight:400;white-space:normal;overflow-wrap:normal;margin:0;padding:0}
    .letter-fixed-prefix strong{font-weight:800}
    .letter-body{display:inline;color:#223a71;font-family:"Bw Modelica Regular","Montserrat",Arial,sans-serif;font-size:24px;font-weight:400;line-height:1.42;text-align:left;white-space:normal;overflow:visible;word-spacing:2px;letter-spacing:.1px}
    .raw{white-space:pre-wrap;background:rgba(255,255,255,.56);border:1px solid rgba(216,174,85,.28);border-radius:16px;padding:12px;font-family:ui-monospace,Consolas,monospace;color:#1f2937;max-height:300px;overflow:auto}
    @media(max-width:1100px){.views{grid-template-columns:1fr}.meta{grid-template-columns:1fr 1fr}.scaled{transform:scale(.5);margin-bottom:-330px}}
    @media(max-width:640px){.meta{grid-template-columns:1fr}.scaled{transform:scale(.34);margin-bottom:-430px}.card-shell{padding:10px}}
  
    /* Exact rebuild helpers from direct DB columns, with payload_json fallback */
    .front-info-rows{position:absolute;left:<?= hid_staff_h($infoLeft) ?>px;top:<?= hid_staff_h($rowsTop) ?>px;width:<?= hid_staff_h($infoWidth) ?>px;z-index:6;pointer-events:none}
    .name-layout-debug{display:none}
    .letter-fixed-prefix strong{font-family:"Bw Modelica Extra Bold","Montserrat",Arial,sans-serif;font-weight:800}

  </style>
</head>
<body>
<header class="top">
  <div class="brand">Heavenly ID Staff Design View</div>
  <div>
    <a class="btn dark" href="dashboard.php">Dashboard</a>
    <a class="btn dark" href="logout.php">Logout</a>
  </div>
</header>

<main class="page">
  <nav class="breadcrumbs">
    <a href="dashboard.php">Dashboard</a>
    &nbsp;/&nbsp; <a href="<?= hid_staff_h($ownerUrl) ?>"><?= hid_staff_h($ownerLabel) ?></a>
    &nbsp;/&nbsp; <?= hid_staff_h($row["design_title"] ?: ("Design #" . $row["id"])) ?>
  </nav>

  <section class="panel">
    <h1><?= hid_staff_h($row["design_title"] ?: ("Design #" . $row["id"])) ?></h1>
    <div class="meta">
      <div><div class="label">Owner</div><div class="value"><?= hid_staff_h($ownerLabel) ?></div></div>
      <div><div class="label">Status</div><div class="value"><?= ((int)$row["is_paid"] === 1) ? "Paid" : "Pending" ?></div></div>
      <div><div class="label">Created</div><div class="value"><?= hid_staff_h($row["created_at"]) ?></div></div>
      <div><div class="label">Updated</div><div class="value"><?= hid_staff_h($row["updated_at"] ?: $row["created_at"]) ?></div></div>
    </div>

    <div class="shipping-info">
      <div class="label">Shipping Information</div>
      <div class="shipping-lines">
        <?php if ($hasShipping): ?>
          <?php if ($shipAddress !== ""): ?>
            <?= hid_staff_h($shipAddress) ?><br>
          <?php endif; ?>
          <?php if ($shipLine2 !== ""): ?>
            <?= hid_staff_h($shipLine2) ?>
          <?php endif; ?>
        <?php else: ?>
          No shipping address on file.
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="views">
    <div class="card-shell">
      <h2 class="card-title">Front Design</h2>
      <div class="scale-wrap">
        <div class="scaled">
          <div class="card-wrapper">
            <div class="card-face card-front">
              <?php if ($foregroundPath): ?>
                <div class="foreground-section">
                  <img src="<?= hid_staff_h($foregroundPath) ?>" alt="Foreground image">
                </div>
              <?php endif; ?>

              <div class="info-block">
                <div class="name-value"><?= hid_staff_h($fullName !== "" ? $fullName : "Enter Full Name") ?></div>

                <div class="front-info-rows">
                  <div class="info-row">
                    <strong>Spiritual Gift:</strong>
                    <div class="card-input-value"><?= hid_staff_h($spiritualGifts) ?></div>
                  </div>

                  <div class="info-row">
                    <strong>Received Jesus:</strong>
                    <div class="card-input-value"><?= hid_staff_h($received) ?></div>
                  </div>

                  <div class="info-row">
                    <strong>Bible Verse:</strong>
                    <div class="card-input-value"><?= hid_staff_h($verse) ?></div>
                  </div>
                </div>

                <div class="name-layout-debug">
                  font=<?= hid_staff_h($nameFontSize) ?> resized=<?= hid_staff_h($nameFontResized) ?>
                  left=<?= hid_staff_h($nameLayoutLeft) ?> top=<?= hid_staff_h($nameLayoutTop) ?>
                  width=<?= hid_staff_h($nameLayoutWidth) ?> height=<?= hid_staff_h($nameLayoutHeight) ?>
                  safeRight=<?= hid_staff_h($nameSafeRight) ?> available=<?= hid_staff_h($nameAvailableWidth) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-shell">
      <h2 class="card-title">Back Design</h2>
      <div class="scale-wrap">
        <div class="scaled">
          <div class="card-wrapper">
            <div class="card-face card-back">
              <?php if ($backPath): ?>
                <img class="back-bg" src="<?= hid_staff_h($backPath) ?>" alt="Back card">
              <?php endif; ?>

              <div class="letter-intent">
                <div class="letter-editor-shell">
                  <span class="letter-fixed-prefix">I <strong><?= hid_staff_h($fullName !== "" ? $fullName : "Enter Full Name") ?></strong></span>
                  <span class="letter-body"><?= hid_staff_h($letterBody) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="panel">
    <h2 style="margin-top:0;color:var(--blue)">Saved Database Information</h2>
    <div class="raw"><?= hid_staff_h(json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></div>
  </section>
</main>
</body>
</html>
