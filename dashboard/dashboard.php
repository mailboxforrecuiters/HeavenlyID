<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

$staff = hid_staff_require_login();

$ownerType = (string)($_GET["owner_type"] ?? "");
$userSiteId = isset($_GET["user_site_id"]) ? (int)$_GET["user_site_id"] : 0;
$guestEmail = trim((string)($_GET["guest_email"] ?? ""));

$ownersStmt = $pdo->query("
  SELECT
    c.owner_type,
    c.user_site_id,
    c.guest_email,
    u.first_name,
    u.last_name,
    u.email,
    COUNT(*) AS design_count,
    SUM(CASE WHEN c.is_paid = 1 THEN 1 ELSE 0 END) AS paid_count,
    MAX(COALESCE(c.updated_at, c.created_at)) AS last_activity
  FROM card_designs_v2 c
  LEFT JOIN users u
    ON u.site_id = c.user_site_id
  GROUP BY c.owner_type, c.user_site_id, c.guest_email, u.first_name, u.last_name, u.email
  ORDER BY last_activity DESC
");
$owners = $ownersStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedOwner = null;
$cards = [];

if ($ownerType === "user" && $userSiteId > 0) {
  $stmt = $pdo->prepare("
    SELECT
      c.id,
      c.design_title,
      c.is_paid,
      c.paid_at,
      c.created_at,
      c.updated_at,
      c.user_site_id,
      c.owner_type,
      c.guest_email,
      u.first_name,
      u.last_name,
      u.email
    FROM card_designs_v2 c
    LEFT JOIN users u
      ON u.site_id = c.user_site_id
    WHERE c.owner_type = 'user'
      AND c.user_site_id = ?
    ORDER BY COALESCE(c.updated_at, c.created_at) DESC
  ");
  $stmt->execute([$userSiteId]);
  $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $selectedOwner = $cards[0] ?? null;
}

if ($ownerType === "guest" && $guestEmail !== "") {
  $stmt = $pdo->prepare("
    SELECT
      c.id,
      c.design_title,
      c.is_paid,
      c.paid_at,
      c.created_at,
      c.updated_at,
      c.user_site_id,
      c.owner_type,
      c.guest_email,
      NULL AS first_name,
      NULL AS last_name,
      c.guest_email AS email
    FROM card_designs_v2 c
    WHERE c.owner_type = 'guest'
      AND c.guest_email = ?
    ORDER BY COALESCE(c.updated_at, c.created_at) DESC
  ");
  $stmt->execute([$guestEmail]);
  $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $selectedOwner = $cards[0] ?? null;
}

function staff_status_pill($paid): string {
  return ((int)$paid === 1)
    ? '<span class="pill paid">Paid</span>'
    : '<span class="pill pending">Pending</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Heavenly ID Staff Dashboard</title>
  <style>
    @font-face{font-family:"Minion Pro";src:url("../MinionPro-Medium.woff") format("woff");font-weight:500;font-style:normal;font-display:swap}
    :root{--blue:#1d3468;--brown:#5f452f;--gold:#d8ae55;--cream:rgba(255,255,255,.58);--line:rgba(216,174,85,.36);--shadow:0 18px 42px rgba(67,51,39,.14)}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;font-family:"Minion Pro",Georgia,serif;color:var(--brown);background:#f6f1ef url("../bgtest.jpg") center center / cover no-repeat}
    a{color:inherit}
    .top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.86);border-bottom:1px solid var(--line);backdrop-filter:blur(8px);padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .brand{font-size:24px;font-weight:900;color:var(--blue)}
    .staff{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 13px;border-radius:12px;border:1px solid rgba(0,0,0,.1);text-decoration:none;font-weight:900;background:#1d3468;color:#fff}
    .btn.dark{background:#222}
    .page{max-width:1480px;margin:0 auto;padding:22px 18px 36px}
    .breadcrumbs{font-size:15px;font-weight:800;margin-bottom:14px;color:#5f452f}
    .breadcrumbs a{color:var(--blue);text-decoration:none}
    .layout{display:grid;grid-template-columns:360px minmax(0,1fr);gap:18px;align-items:start}
    .panel{background:var(--cream);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow);backdrop-filter:blur(8px)}
    .panel h2{margin:0;padding:18px 18px 6px;color:var(--blue);font-size:32px;line-height:1}
    .sub{margin:0;padding:0 18px 14px;font-size:16px}
    .owner-list{max-height:calc(100vh - 190px);overflow:auto;padding:8px 12px 14px}
    .owner{display:block;text-decoration:none;border:1px solid rgba(216,174,85,.24);border-radius:16px;padding:12px;margin:0 0 10px;background:rgba(255,255,255,.46)}
    .owner.active{outline:3px solid rgba(29,52,104,.18);border-color:rgba(29,52,104,.45)}
    .owner-name{font-weight:900;color:var(--blue);font-size:17px;line-height:1.15;word-break:break-word}
    .owner-meta{font-size:13px;margin-top:5px;color:#6f5336}
    .cards{padding:10px 18px 18px}
    .card-row{display:grid;grid-template-columns:minmax(0,1fr) 110px 170px 130px;gap:12px;align-items:center;background:rgba(255,255,255,.52);border:1px solid rgba(216,174,85,.26);border-radius:16px;padding:13px;margin-bottom:10px}
    .card-title{font-size:19px;font-weight:900;color:var(--blue)}
    .small{font-size:13px;color:#6f5336;margin-top:3px}
    .pill{display:inline-block;padding:7px 11px;border-radius:999px;font-weight:900;font-size:13px}
    .paid{background:#e7f7ed;color:#0b6b2f;border:1px solid #bfe9cd}
    .pending{background:#fff7e6;color:#7a4b00;border:1px solid #ffe1a6}
    .empty{padding:18px;background:rgba(255,255,255,.48);border:1px solid rgba(216,174,85,.26);border-radius:16px;font-size:18px}
    @media(max-width:900px){.layout{grid-template-columns:1fr}.owner-list{max-height:320px}.card-row{grid-template-columns:1fr}.top{position:relative}}
  </style>
</head>
<body>
<header class="top">
  <div class="brand">Heavenly ID Staff Dashboard</div>
  <div class="staff">
    <span><?= hid_staff_h($staff["name"]) ?> / <?= hid_staff_h($staff["role"]) ?></span>
    <a class="btn dark" href="logout.php">Logout</a>
  </div>
</header>

<main class="page">
  <nav class="breadcrumbs">
    <a href="dashboard.php">Dashboard</a>
    <?php if ($selectedOwner): ?>
      &nbsp;/&nbsp; <?= hid_staff_h(hid_staff_owner_label($selectedOwner)) ?>
    <?php endif; ?>
  </nav>

  <div class="layout">
    <aside class="panel">
      <h2>Customers</h2>
      <p class="sub">Scroll through customers/guests with saved designs.</p>
      <div class="owner-list">
        <?php if (!$owners): ?>
          <div class="empty">No saved designs found.</div>
        <?php else: ?>
          <?php foreach ($owners as $o): ?>
            <?php
              $active = false;
              if ($ownerType === "user" && (int)$o["user_site_id"] === $userSiteId && $o["owner_type"] === "user") $active = true;
              if ($ownerType === "guest" && (string)$o["guest_email"] === $guestEmail && $o["owner_type"] === "guest") $active = true;
            ?>
            <a class="owner <?= $active ? 'active' : '' ?>" href="<?= hid_staff_h(hid_staff_owner_url($o)) ?>">
              <div class="owner-name"><?= hid_staff_h(hid_staff_owner_label($o)) ?></div>
              <div class="owner-meta">
                <?= (int)$o["design_count"] ?> design(s) · <?= (int)$o["paid_count"] ?> paid<br>
                Last activity: <?= hid_staff_h($o["last_activity"]) ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <section class="panel">
      <?php if (!$selectedOwner): ?>
        <h2>Select a customer</h2>
        <div class="cards"><div class="empty">Choose a customer on the left to view their saved card titles.</div></div>
      <?php else: ?>
        <h2><?= hid_staff_h(hid_staff_owner_label($selectedOwner)) ?></h2>
        <p class="sub">Click a design title to view the fully constructed front/back card.</p>

        <div class="cards">
          <?php foreach ($cards as $c): ?>
            <div class="card-row">
              <div>
                <div class="card-title"><?= hid_staff_h($c["design_title"] ?: ("Design #" . $c["id"])) ?></div>
                <div class="small">ID: <?= (int)$c["id"] ?></div>
              </div>
              <div><?= staff_status_pill($c["is_paid"]) ?></div>
              <div class="small">Updated:<br><?= hid_staff_h($c["updated_at"] ?: $c["created_at"]) ?></div>
              <div><a class="btn" href="design_view.php?id=<?= (int)$c["id"] ?>">View Design</a></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
</body>
</html>
