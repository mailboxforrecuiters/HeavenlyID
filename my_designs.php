<?php
session_start();
require_once __DIR__ . "/protected/pdo.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function hid_md_phpmailer_bootstrap(): bool {
  if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) return true;

  $files = [
    __DIR__ . "/PHPMailer/src/Exception.php",
    __DIR__ . "/PHPMailer/src/PHPMailer.php",
    __DIR__ . "/PHPMailer/src/SMTP.php",
  ];

  foreach ($files as $file) {
    if (!is_file($file)) return false;
  }

  require_once $files[0];
  require_once $files[1];
  require_once $files[2];

  return class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
}

function hid_md_send_status_request_email(array $row): bool {
  $firstName = trim((string)($row["first_name"] ?? ""));
  $lastName  = trim((string)($row["last_name"] ?? ""));
  $email     = trim((string)($row["email"] ?? ""));
  $designTitle = trim((string)($row["design_title"] ?? ""));
  $designId = (int)($row["id"] ?? 0);

  $displayFirst = $firstName !== "" ? $firstName : "Customer";
  $displayLast  = $lastName !== "" ? $lastName : "";

  $subject = "Card Design Status for - " . $displayFirst . ", " . $displayLast;

  $body =
    $displayFirst . ", " . $displayLast . " , has requested an update on their card design, please respond to their request at " . ($email !== "" ? $email : "[email not found]") . "\r\n\r\n" .
    "Design Title: " . ($designTitle !== "" ? $designTitle : "-") . "\r\n" .
    "Design ID: " . ($designId > 0 ? $designId : "-") . "\r\n";

  @file_put_contents(
    __DIR__ . "/card_status_request_email.log",
    "[" . date("Y-m-d H:i:s") . "] Status request email for design " . $designId . "\n" . $body . "\n\n",
    FILE_APPEND
  );

  if (hid_md_phpmailer_bootstrap()) {
    try {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = "localhost";
      $mail->Port = 25;
      $mail->SMTPAuth = false;
      $mail->SMTPAutoTLS = false;
      $mail->SMTPSecure = false;

      $mail->CharSet = "UTF-8";
      $mail->setFrom("info@heavenlyid.com", "Heavenly ID");
      if ($email !== "") {
        $mail->addReplyTo($email, trim($firstName . " " . $lastName) ?: $email);
      } else {
        $mail->addReplyTo("info@heavenlyid.com", "Heavenly ID");
      }
      $mail->addAddress("info@threadkraze.com", "Thread Kraze");

      $mail->Subject = $subject;
      $mail->Body = $body;
      $mail->AltBody = $body;
      $mail->isHTML(false);

      $mail->send();

      @file_put_contents(__DIR__ . "/card_status_request_email.log", "[" . date("Y-m-d H:i:s") . "] PHPMailer sent.\n\n", FILE_APPEND);
      return true;
    } catch (Throwable $e) {
      @file_put_contents(__DIR__ . "/card_status_request_email.log", "[" . date("Y-m-d H:i:s") . "] PHPMailer failed: " . $e->getMessage() . "\n\n", FILE_APPEND);
    }
  }

  $headers = [
    "From: Heavenly ID <info@heavenlyid.com>",
    "Reply-To: " . ($email !== "" ? $email : "info@heavenlyid.com"),
    "Content-Type: text/plain; charset=UTF-8",
  ];

  $ok = @mail("info@threadkraze.com", $subject, $body, implode("\r\n", $headers));
  @file_put_contents(__DIR__ . "/card_status_request_email.log", "[" . date("Y-m-d H:i:s") . "] mail() fallback: " . ($ok ? "sent" : "failed") . "\n\n", FILE_APPEND);

  return (bool)$ok;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "request_status_update") {
  header("Content-Type: application/json; charset=utf-8");

  if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Please sign in first."]);
    exit;
  }

  $userSiteId = (int)$_SESSION["user_id"];
  $designId = (int)($_POST["design_id"] ?? 0);

  if ($designId <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing design id."]);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT
      c.id,
      c.design_title,
      c.user_site_id,
      u.first_name,
      u.last_name,
      u.email
    FROM card_designs_v2 c
    LEFT JOIN users u
      ON u.site_id = c.user_site_id
    WHERE c.id = ?
      AND c.owner_type = 'user'
      AND c.user_site_id = ?
    LIMIT 1
  ");
  $stmt->execute([$designId, $userSiteId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Design not found."]);
    exit;
  }

  $sent = hid_md_send_status_request_email($row);

  echo json_encode([
    "success" => $sent,
    "message" => $sent ? "Your status update request was sent." : "Could not send the request email. Please try again."
  ]);
  exit;
}

if (empty($_SESSION["user_id"])) {
  header("Location: /index.php");
  exit;
}

$userSiteId = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("
  SELECT
    c.id,
    c.design_title,
    c.created_at,
    c.updated_at,
    c.is_paid,
    c.paid_at,
    c.download_token
  FROM card_designs_v2 c
  WHERE c.owner_type = 'user'
    AND c.user_site_id = ?
  ORDER BY COALESCE(c.updated_at, c.created_at) DESC
");
$stmt->execute([$userSiteId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Designs</title>
  <style>
    @font-face{
      font-family:"Minion Pro";
      src:url("MinionPro-Medium.woff") format("woff");
      font-weight:500;
      font-style:normal;
      font-display:swap;
    }

    :root{
      --md-blue:#1d3468;
      --md-brown:#5f452f;
      --md-gold:#d8ae55;
      --md-cream:rgba(255,255,255,.40);
      --md-line:rgba(216,174,85,.34);
      --md-shadow:0 18px 42px rgba(67,51,39,.14);
    }

    *{box-sizing:border-box;}

    html,body{
      min-height:100%;
      margin:0;
      font-family:"Minion Pro", Georgia, serif;
      color:var(--md-brown);
      overflow-x:hidden;
      background:#f6f1ef url("bgtest.jpg") center center / cover no-repeat;
      background-attachment:scroll;
    }

    body::before{
      content:"";
      position:fixed;
      inset:0;
      pointer-events:none;
      z-index:-1;
    }

    body,
    input,
    button,
    textarea,
    select{
      font-family:"Minion Pro", Georgia, serif;
    }

    .md-page{
      width:100%;
      min-height:calc(100vh - var(--hh-total-h, 0px));
      padding:32px 18px 46px;
    }

    .md-wrap{
      width:100%;
      max-width:1080px;
      margin:0 auto;
    }

    .md-card{
      background:var(--md-cream);
      border:1px solid var(--md-line);
      border-radius:24px;
      padding:22px;
      box-shadow:var(--md-shadow);
      backdrop-filter:blur(8px);
    }

    .md-topbar{
      display:flex;
      gap:16px;
      align-items:flex-start;
      justify-content:space-between;
      margin-bottom:18px;
      flex-wrap:wrap;
    }

    .md-title{
      margin:0 0 8px;
      font-size:clamp(32px, 4vw, 52px);
      line-height:1;
      color:var(--md-blue);
      font-weight:700;
      text-shadow:0 1px 0 rgba(255,255,255,.42);
    }

    .md-sub{
      color:#5f452f;
      margin:0;
      font-size:18px;
      line-height:1.45;
    }

    .md-actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
    }

    .md-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:42px;
      padding:10px 14px;
      border-radius:12px;
      font-weight:800;
      font-size:16px;
      text-decoration:none;
      border:1px solid rgba(0,0,0,.10);
      box-shadow:0 10px 20px rgba(0,0,0,.08);
      white-space:nowrap;
      cursor:pointer;
    }

    .md-btn-primary{
      background:linear-gradient(180deg, #396fcd 0%, #234790 100%);
      color:#fff;
      border-color:#1d3871;
    }

    .md-btn-status{
      background:linear-gradient(180deg, #f8f8ff 0%, #d8e3ff 100%);
      color:#1d3468;
      border-color:#9fb7ee;
    }

    .md-hidden-download{
      display:none !important;
    }

    .md-btn-gold{
      background:linear-gradient(180deg, #f2d992 0%, #d8ae55 100%);
      color:#593b14;
      border-color:#b8872f;
    }

    .md-btn-dark{
      background:rgba(31,31,31,.92);
      color:#fff;
    }

    .md-btn[aria-disabled="true"]{
      opacity:.55;
      pointer-events:none;
    }

    .md-empty{
      margin:16px 0 0;
      padding:18px;
      border-radius:16px;
      background:rgba(255,255,255,.34);
      border:1px solid rgba(255,255,255,.38);
      font-size:18px;
    }

    .md-table-wrap{
      width:100%;
      overflow-x:auto;
    }

    .md-table{
      width:100%;
      border-collapse:separate;
      border-spacing:0 10px;
      min-width:760px;
    }

    .md-table th,
    .md-table td{
      padding:14px 12px;
      font-size:16px;
      text-align:left;
      vertical-align:middle;
    }

    .md-table th{
      color:#3d2b21;
      font-weight:800;
      font-size:15px;
      text-transform:uppercase;
      letter-spacing:.04em;
    }

    .md-row td{
      background:rgba(255,255,255,.52);
      border-top:1px solid rgba(216,174,85,.22);
      border-bottom:1px solid rgba(216,174,85,.22);
    }

    .md-row td:first-child{
      border-left:1px solid rgba(216,174,85,.22);
      border-top-left-radius:14px;
      border-bottom-left-radius:14px;
    }

    .md-row td:last-child{
      border-right:1px solid rgba(216,174,85,.22);
      border-top-right-radius:14px;
      border-bottom-right-radius:14px;
    }

    .md-design-name{
      color:var(--md-blue);
      font-weight:800;
      font-size:17px;
    }

    .md-id{
      color:#6f5336;
      font-size:13px;
      opacity:.82;
    }

    .md-pill{
      display:inline-block;
      padding:7px 11px;
      border-radius:999px;
      font-weight:800;
      font-size:13px;
    }

    .md-paid{
      background:#e7f7ed;
      color:#0b6b2f;
      border:1px solid #bfe9cd;
    }

    .md-pending{
      background:#fff7e6;
      color:#7a4b00;
      border:1px solid #ffe1a6;
    }

    .md-cell-actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }

    .md-status-note{
      margin:14px 0 0;
      min-height:22px;
      font-size:15px;
      font-weight:800;
      color:var(--md-blue);
    }

    .md-status-note.is-error{
      color:#b00020;
    }

    @media (max-width:900px){
      .md-page{
        padding:24px 14px 40px;
      }

      .md-card{
        padding:18px;
        border-radius:20px;
      }

      .md-topbar{
        align-items:center;
        text-align:center;
        justify-content:center;
      }

      .md-actions{
        width:100%;
        justify-content:center;
      }
    }

    @media (max-width:720px){
      .md-table-wrap{
        overflow:visible;
      }

      .md-table,
      .md-table thead,
      .md-table tbody,
      .md-table tr,
      .md-table th,
      .md-table td{
        display:block;
        width:100%;
        min-width:0;
      }

      .md-table{
        border-spacing:0;
      }

      .md-table thead{
        position:absolute;
        width:1px;
        height:1px;
        overflow:hidden;
        clip:rect(0 0 0 0);
      }

      .md-row{
        margin:0 0 14px;
        padding:12px;
        background:rgba(255,255,255,.52);
        border:1px solid rgba(216,174,85,.26);
        border-radius:16px;
      }

      .md-row td,
      .md-row td:first-child,
      .md-row td:last-child{
        border:0;
        border-radius:0;
        background:transparent;
        padding:9px 2px;
      }

      .md-row td::before{
        content:attr(data-label);
        display:block;
        margin-bottom:4px;
        color:#3d2b21;
        font-size:12px;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.05em;
      }

      .md-cell-actions{
        justify-content:flex-start;
      }

      .md-btn{
        flex:1 1 130px;
      }
    }

    @media (max-width:480px){
      .md-page{
        padding:18px 12px 34px;
      }

      .md-card{
        padding:16px 14px;
      }

      .md-actions,
      .md-cell-actions{
        flex-direction:column;
      }

      .md-btn{
        width:100%;
      }
    }
  </style>
</head>
<body>
<?php
$headerContext = [
  'active' => 'download',
  'show_contact' => true,
  'show_socials' => true,
];
include_once __DIR__ . '/header.php';
?>

<main class="md-page">
  <div class="md-wrap">
    <section class="md-card" aria-labelledby="mdTitle">
      <div class="md-topbar">
        <div>
          <h1 class="md-title" id="mdTitle">My Designs</h1>
          <p class="md-sub">Paid designs will show download access later. Use Update Status to request a design update from Thread Kraze.</p>
          <p class="md-status-note" id="statusRequestNote" aria-live="polite"></p>
        </div>
        <div class="md-actions">
          <a class="md-btn md-btn-dark" href="/cardbuilder.php">Open Builder</a>
          <a class="md-btn md-btn-dark" href="/logout.php">Logout</a>
        </div>
      </div>

      <?php if (!$rows): ?>
        <p class="md-empty">No designs found yet.</p>
      <?php else: ?>
        <div class="md-table-wrap">
          <table class="md-table">
            <thead>
              <tr>
                <th>Design</th>
                <th>Status</th>
                <th>Last Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <?php
                  $isPaid = ((int)$r["is_paid"] === 1 && !empty($r["download_token"]));
                  $statusHtml = $isPaid
                    ? '<span class="md-pill md-paid">Paid</span>'
                    : '<span class="md-pill md-pending">Pending</span>';
                  $dt = $r["updated_at"] ?: $r["created_at"];
                  $downloadUrl = $isPaid ? ("/download_design.php?token=" . urlencode($r["download_token"])) : "#";
                  $loadUrl = "/cardbuilder.php?load_id=" . urlencode($r["id"]);
                  $payAttrs = $isPaid
                    ? 'href="#" aria-disabled="true"'
                    : 'href="#" data-pay-design-id="' . (int)$r["id"] . '"';
                ?>
                <tr class="md-row">
                  <td data-label="Design">
                    <span class="md-design-name"><?= h($r["design_title"] ?: ("Design #" . $r["id"])) ?></span><br>
                    <span class="md-id">ID: <?= (int)$r["id"] ?></span>
                  </td>
                  <td data-label="Status"><?= $statusHtml ?></td>
                  <td data-label="Last Updated"><?= h($dt) ?></td>
                  <td data-label="Actions">
                    <div class="md-cell-actions">
                      <a class="md-btn md-btn-dark" href="<?= h($loadUrl) ?>">Edit</a>

                      <!-- Download code preserved but intentionally hidden for later. -->
                      <a class="md-btn md-btn-primary md-hidden-download" href="<?= h($downloadUrl) ?>" <?= $isPaid ? "" : 'aria-disabled="true"' ?>>Download</a>

                      <button type="button" class="md-btn md-btn-status" data-status-design-id="<?= (int)$r["id"] ?>">Update Status</button>
                      <a class="md-btn md-btn-gold" <?= $payAttrs ?>>Pay for Design</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>


<script>
(function(){
  "use strict";

  window.CB_SHOPIFY = window.CB_SHOPIFY || {
    domain: "heavenly-id-checkout.myshopify.com",
    storefrontAccessToken: "0f5a95b76314849b31cd92d0e1b7ef66",
    apiVersion: "2026-01",
    productId: "14870420685165"
  };

  function getShopifyCfg() {
    const cfg = window.CB_SHOPIFY || {};
    return {
      domain: String(cfg.domain || "").trim(),
      token: String(cfg.storefrontAccessToken || "").trim(),
      apiVersion: String(cfg.apiVersion || "").trim() || "2026-01",
      productIdOrGid: String(cfg.productId || cfg.productGid || "").trim()
    };
  }

  function toProductGid(productIdOrGid) {
    const v = String(productIdOrGid || "").trim();
    if (!v) return "";
    if (v.startsWith("gid://")) return v;
    if (/^\d+$/.test(v)) return "gid://shopify/Product/" + v;
    return v;
  }

  async function shopifyGraphql(query, variables) {
    const cfg = getShopifyCfg();
    if (!cfg.domain || !cfg.token) throw new Error("Missing Shopify checkout configuration.");

    const cleanDomain = cfg.domain.replace(/^https?:\/\//i, "");
    const endpoint = "https://" + cleanDomain + "/api/" + cfg.apiVersion + "/graphql.json";

    const res = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Shopify-Storefront-Access-Token": cfg.token
      },
      body: JSON.stringify({ query: query, variables: variables || {} })
    });

    const json = await res.json().catch(function(){ return {}; });
    if (!res.ok) throw new Error("Shopify API HTTP " + res.status);
    if (json.errors && json.errors.length) {
      throw new Error(json.errors.map(function(e){ return e.message; }).join("; "));
    }
    return json.data;
  }

  async function getFirstVariantGidForProduct(productIdOrGid) {
    const productGid = toProductGid(productIdOrGid);
    if (!productGid) throw new Error("Missing Shopify product id.");

    const cacheKey = "hid_variant_gid_" + productGid;
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) return cached;
    } catch (_) {}

    const query = `
      query GetFirstVariant($id: ID!) {
        product(id: $id) {
          variants(first: 1) {
            edges {
              node {
                id
                availableForSale
              }
            }
          }
        }
      }
    `;

    const data = await shopifyGraphql(query, { id: productGid });
    const variantGid = data && data.product && data.product.variants && data.product.variants.edges &&
      data.product.variants.edges[0] && data.product.variants.edges[0].node &&
      data.product.variants.edges[0].node.id ? data.product.variants.edges[0].node.id : "";

    if (!variantGid) throw new Error("Could not find the Shopify product variant.");
    try { sessionStorage.setItem(cacheKey, String(variantGid)); } catch (_) {}
    return String(variantGid);
  }

  async function createShopifyCartAndGetCheckoutUrl(designId) {
    const cfg = getShopifyCfg();
    const variantGid = await getFirstVariantGidForProduct(cfg.productIdOrGid);

    const mutation = `
      mutation CreateCart($lines: [CartLineInput!]!) {
        cartCreate(input: { lines: $lines }) {
          cart {
            checkoutUrl
          }
          userErrors {
            message
          }
        }
      }
    `;

    const lines = [{
      quantity: 1,
      merchandiseId: variantGid,
      attributes: [
        { key: "design_id", value: String(designId) }
      ]
    }];

    const data = await shopifyGraphql(mutation, { lines: lines });
    const errors = data && data.cartCreate && data.cartCreate.userErrors ? data.cartCreate.userErrors : [];
    if (errors.length) {
      throw new Error(errors.map(function(e){ return e.message; }).join("; "));
    }

    const url = data && data.cartCreate && data.cartCreate.cart ? data.cartCreate.cart.checkoutUrl : "";
    if (!url) throw new Error("Shopify did not return a checkout URL.");
    return String(url);
  }

  document.addEventListener("click", async function(e) {
    const statusBtn = e.target.closest("[data-status-design-id]");
    if (statusBtn) {
      e.preventDefault();

      const designId = String(statusBtn.getAttribute("data-status-design-id") || "").trim();
      const note = document.getElementById("statusRequestNote");
      const oldText = statusBtn.textContent;

      if (note) {
        note.classList.remove("is-error");
        note.textContent = "";
      }

      if (!/^\d+$/.test(designId)) {
        if (note) {
          note.classList.add("is-error");
          note.textContent = "Invalid design id.";
        }
        return;
      }

      statusBtn.textContent = "Sending...";
      statusBtn.disabled = true;

      try {
        const fd = new FormData();
        fd.append("action", "request_status_update");
        fd.append("design_id", designId);

        const res = await fetch("/my_designs.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin"
        });
        const json = await res.json().catch(function(){ return {}; });

        if (!res.ok || !json.success) {
          throw new Error(json.error || json.message || "Could not send status request.");
        }

        if (note) {
          note.classList.remove("is-error");
          note.textContent = json.message || "Your status update request was sent.";
        }
        statusBtn.textContent = "Requested";
      } catch (err) {
        if (note) {
          note.classList.add("is-error");
          note.textContent = err && err.message ? err.message : "Could not send status request.";
        }
        statusBtn.textContent = oldText;
        statusBtn.disabled = false;
      }
      return;
    }

    const btn = e.target.closest("[data-pay-design-id]");
    if (!btn) return;

    e.preventDefault();

    if (btn.getAttribute("aria-disabled") === "true") return;

    const designId = String(btn.getAttribute("data-pay-design-id") || "").trim();
    if (!/^\d+$/.test(designId)) {
      alert("Invalid design id.");
      return;
    }

    const oldText = btn.textContent;
    btn.textContent = "Redirecting...";
    btn.setAttribute("aria-disabled", "true");

    try {
      const checkoutUrl = await createShopifyCartAndGetCheckoutUrl(designId);
      window.location.href = checkoutUrl;
    } catch (err) {
      console.error(err);
      btn.textContent = oldText;
      btn.removeAttribute("aria-disabled");
      alert((err && err.message) ? err.message : "Could not start checkout. Please try again.");
    }
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
