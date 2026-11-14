<?php
// guest_checkout.php — Heavenly ID guest confirmation (same checkout logic, updated visual design)
require_once __DIR__ . '/shopify_checkout_config.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Guest Checkout | Heavenly ID</title>
  <style>
    @font-face {
      font-family: 'Minion Pro';
      src: url('MinionPro-Medium.woff') format('woff');
      font-style: normal;
      font-weight: 500;
      font-display: swap;
    }
    :root {
      --hid-navy:#223a71;
      --hid-ink:#3c312c;
      --hid-gold:#d0aa55;
      --hid-gold-deep:#a67d35;
      --hid-line:rgba(190,170,132,.65);
    }
    *, *::before, *::after {box-sizing:border-box;}
    html,body {margin:0;min-height:100%;}
    body {
      position:relative;
      isolation:isolate;
      background:#ece7e0;
      color:var(--hid-ink);
      font-family:'Minion Pro', Georgia, serif;
      overflow-x:hidden;
    }
    body::before {
      content:'';position:fixed;inset:0;z-index:-1;pointer-events:none;
      background:url('bg_no_text_v3.jpg') center center / cover no-repeat;
      opacity:.30;
    }
    .wrap {max-width:1270px;width:100%;margin:0 auto;padding:24px 22px 50px;}
    .top {display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;}
    .top h1 {margin:0;font-size:clamp(29px,3vw,39px);line-height:1.1;color:var(--hid-navy);font-weight:600;}
    .top p {margin:7px 0 0;color:#66574b;font-size:17px;}
    .panel {
      background:rgba(255,252,247,.86);
      border:1px solid rgba(190,170,132,.65);
      border-radius:22px;
      box-shadow:0 18px 45px rgba(64,48,38,.12);
      padding:clamp(17px,2.5vw,30px);
      backdrop-filter:blur(5px);
    }
    .section-title {margin:0 0 7px;color:var(--hid-navy);font-size:27px;font-weight:600;}
    .section-desc {margin:0 0 18px;color:#65574f;font-size:16px;line-height:1.45;}
    .grid {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:12px 0 24px;}
    .imgbox {
      min-width:0;
      background:rgba(255,255,255,.78);
      border:1px solid rgba(208,170,85,.48);
      border-radius:17px;
      padding:12px;
      box-shadow:0 9px 24px rgba(64,48,38,.08);
    }
    .imgbox-head {color:var(--hid-navy);font-size:19px;font-weight:600;margin:2px 3px 11px;}
    .imgbox img {display:block;width:100%;height:auto;aspect-ratio:1000 / 667;object-fit:contain;border-radius:13px;background:#f6edd7;}
    .form-grid {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:7px;}
    .field {min-width:0;}
    label {display:block;color:var(--hid-navy);font-size:18px;font-weight:600;margin-bottom:8px;}
    label .optional {color:#71655a;font-size:14px;font-weight:400;}
    input {
      display:block;width:100%;min-height:50px;border:1px solid #d2c5b4;border-radius:12px;
      padding:12px 15px;background:rgba(255,255,255,.95);color:var(--hid-navy);
      outline:none;font:17px 'Minion Pro', Georgia,serif;
    }
    input::placeholder {color:#7c8aa7;opacity:1;}
    input:focus-visible {border-color:var(--hid-gold-deep);box-shadow:0 0 0 3px rgba(208,170,85,.21);}
    .row {display:flex;gap:11px;flex-wrap:wrap;align-items:center;margin-top:20px;}
    button {
      font:600 17px 'Minion Pro', Georgia, serif;
      border:1px solid #d5b273;
      background:linear-gradient(180deg,#fffefb,#eee5d9);
      color:var(--hid-navy);padding:12px 19px;border-radius:12px;
      box-shadow:0 3px 9px rgba(63,45,24,.08);cursor:pointer;
      transition:filter .16s ease,transform .16s ease;
    }
    button:hover {filter:brightness(.98);transform:translateY(-1px);}
    button:focus-visible {outline:3px solid #b48a3f;outline-offset:2px;}
    button.primary {
      background:linear-gradient(180deg,#ffe9ad 0%,#f4c64c 100%);
      border-color:#d2a034;color:#1b315f;font-weight:700;
    }
    .note {min-height:25px;margin-top:14px;font-size:16px;line-height:1.45;color:#66574b;overflow-wrap:anywhere;}
    .err {color:#a32323;}
    .ok {color:#166b36;}
    .muted {color:#66574b;}
    @media(max-width:760px) {
      .wrap {padding:14px 12px 30px;}
      .top {gap:12px;}
      .grid,.form-grid {grid-template-columns:1fr;}
      .grid {gap:12px;}
      .section-title {font-size:24px;}
      button.primary {width:100%;}
    }
  </style>
</head>
<body>
<?php
$headerContext = ['active'=>'build','show_contact'=>true,'show_socials'=>true];
include_once __DIR__ . '/header.php';
?>
<main class="wrap">
  <div class="top">
    <div>
      <h1>Heavenly ID Guest Checkout</h1>
      <p>Review your custom card before continuing to Shopify.</p>
    </div>
    <button type="button" id="btnBack">Back to Builder</button>
  </div>
  <section class="panel" aria-labelledby="confirmTitle">
    <h2 class="section-title" id="confirmTitle">Confirm Your Card Design</h2>
    <p class="section-desc">Make sure the front and back previews look right before you proceed.</p>
    <div class="grid">
      <div class="imgbox">
        <div class="imgbox-head">Front of Card</div>
        <img id="prevFront" alt="Front card design preview" />
      </div>
      <div class="imgbox">
        <div class="imgbox-head">Back of Card</div>
        <img id="prevBack" alt="Back card design preview" />
      </div>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="email">Email Address <span class="optional">(required for your access code)</span></label>
        <input id="email" type="email" placeholder="you@example.com" autocomplete="email" required />
      </div>
      <div class="field">
        <label for="title">Design Title <span class="optional">(optional)</span></label>
        <input id="title" type="text" placeholder="My Heavenly ID" />
      </div>
    </div>
    <div class="row">
      <button class="primary" type="button" id="btnContinue">Save + Continue to Shopify Checkout</button>
    </div>
    <div class="note" id="status" role="status" aria-live="polite"></div>
  </section>
</main>
<script>
  // same Shopify config as cardbuilder.php
  // Selected store comes from the shared JSON configuration (active_store = dev or live).
  window.CB_SHOPIFY = <?= json_encode(hid_shopify_checkout_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;

  const KEY_PAYLOAD = "cb_guest_checkout_payload_v1";
  const KEY_FRONT   = "cb_guest_preview_front_v1";
  const KEY_BACK    = "cb_guest_preview_back_v1";

  const $ = (id) => document.getElementById(id);

  const payloadRaw = sessionStorage.getItem(KEY_PAYLOAD);
  const front = sessionStorage.getItem(KEY_FRONT) || "";
  const back  = sessionStorage.getItem(KEY_BACK) || "";

  if (!payloadRaw) {
    $("status").innerHTML = '<span class="err">Missing guest checkout data. Please go back and click Checkout again.</span>';
  }

  $("prevFront").src = front || "";
  $("prevBack").src = back || "";

  $("btnBack").addEventListener("click", () => {
    // keep sessionStorage so the builder can resume
    window.location.href = "cardbuilder.php";
  });

  function getShopifyCfg(){
    const cfg = (window.CB_SHOPIFY || {});
    return {
      domain: String(cfg.domain || "").trim(),
      token: String(cfg.storefrontAccessToken || "").trim(),
      apiVersion: String(cfg.apiVersion || "").trim() || "2026-01",
      productIdOrGid: String(cfg.productId || cfg.productGid || "").trim()
    };
  }
  function toProductGid(v){
    v = String(v||"").trim();
    if (!v) return "";
    if (v.startsWith("gid://")) return v;
    if (/^\d+$/.test(v)) return `gid://shopify/Product/${v}`;
    return v;
  }
  async function shopifyGraphql(query, variables){
    const { domain, token, apiVersion } = getShopifyCfg();
    if (!domain || !token) throw new Error("Missing Shopify config.");
    const clean = domain.replace(/^https?:\/\//i,"");
    const endpoint = `https://${clean}/api/${apiVersion}/graphql.json`;

    const res = await fetch(endpoint, {
      method:"POST",
      headers:{
        "Content-Type":"application/json",
        "X-Shopify-Storefront-Access-Token": token
      },
      body: JSON.stringify({ query, variables: variables || {} })
    });
    const json = await res.json().catch(()=>({}));
    if (!res.ok) throw new Error("Shopify HTTP " + res.status);
    if (json.errors?.length) throw new Error(json.errors.map(e=>e.message).join("; "));
    return json.data;
  }
  async function getFirstVariantGidForProduct(productIdOrGid){
    const productGid = toProductGid(productIdOrGid);
    if (!productGid) throw new Error("Missing Shopify product id.");
    const q = `
      query GetFirstVariant($id: ID!) {
        product(id: $id) {
          variants(first: 1) { edges { node { id } } }
        }
      }`;
    const data = await shopifyGraphql(q, { id: productGid });
    const gid = data?.product?.variants?.edges?.[0]?.node?.id || "";
    if (!gid) throw new Error("Could not find a product variant.");
    return String(gid);
  }
  // These are product design details, not customer account or payment information.
  // The authoritative, unabridged design remains in card_designs_v2.payload_json.
  function printableDesignJson(rawPayload, designCode) {
    const p = (rawPayload && typeof rawPayload === "object") ? rawPayload : {};
    const keys = [
      "design_title", "full_name", "iam_status", "spiritual_gifts",
      "received_jesus_date", "favorite_verse_ref", "verse_text",
      "letter_of_intent", "name_font_resized", "name_font_size_px",
      "name_layout_left_px", "name_layout_top_px", "name_layout_width_px",
      "name_layout_height_px", "name_safe_right_px", "name_available_width_px",
      "name_text_align", "name_padding_left_px", "letter_font_resized",
      "letter_font_size_px", "front_theme_file", "back_theme_file",
      "front_theme_style", "foreground_file"
    ];
    const result = { design_id: String(designCode), schema: "heavenly-id-card-v2" };
    for (const key of keys) {
      const value = p[key];
      if (value !== undefined && value !== null) result[key] = value;
    }
    return result;
  }

  async function createCartCheckoutUrl(designCode, designPayload, customerEmail){
    const { productIdOrGid } = getShopifyCfg();
    const variantGid = await getFirstVariantGidForProduct(productIdOrGid);

    // Line-item properties make the design easy to identify directly in Shopify Orders.
    // The JSON order note is a convenience copy for the printer, while the
    // full server-side payload and previews remain the source of truth.
    const design = printableDesignJson(designPayload, designCode);
    const meta = [
      { key: "design_id", value: String(designCode) },
      { key: "Card name", value: String(design.full_name || "") },
      { key: "Spiritual gift", value: String(design.spiritual_gifts || "") },
      { key: "Received Jesus", value: String(design.received_jesus_date || "") },
      { key: "Bible verse", value: String(design.favorite_verse_ref || "") },
      { key: "Front artwork", value: String(design.front_theme_file || "") },
      { key: "Back artwork", value: String(design.back_theme_file || "") },
      { key: "Cross artwork", value: String(design.foreground_file || "") }
    ];
    const m = `
      mutation CreateCart($input: CartInput!) {
        cartCreate(input: $input) {
          cart { checkoutUrl }
          userErrors { message }
        }
      }`;
    const input = {
      lines: [{ quantity: 1, merchandiseId: variantGid, attributes: meta }],
      note: JSON.stringify(design),
      attributes: [{key: "heavenly_design_id", value: String(designCode)}],
      buyerIdentity: {email: customerEmail}
    };
    const data = await shopifyGraphql(m, { input });
    const errs = data?.cartCreate?.userErrors || [];
    if (errs.length) throw new Error(errs.map(e=>e.message).join("; "));
    const url = data?.cartCreate?.cart?.checkoutUrl || "";
    if (!url) throw new Error("Shopify did not return checkout URL.");
    return String(url);
  }

  $("btnContinue").addEventListener("click", async () => {
    const email = ($("email").value || "").trim();
    const title = ($("title").value || "").trim();
    const status = $("status");

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      status.innerHTML = '<span class="err">Please enter a valid email.</span>';
      return;
    }
    if (!payloadRaw) {
      status.innerHTML = '<span class="err">Missing design payload. Go back and try again.</span>';
      return;
    }

    status.textContent = "Saving your design…";

    let saved;
    try{
      saved = await fetch("guest_save_unregistered.php", {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({
          email,
          design_title: title,
          payload: JSON.parse(payloadRaw),
          preview_front_dataurl: front || "",
          preview_back_dataurl: back || ""
        })
      }).then(r => r.json());
    }catch(e){
      status.innerHTML = '<span class="err">Save failed. Please try again.</span>';
      return;
    }

    if (!saved || !saved.success || !saved.code) {
      status.innerHTML = '<span class="err">' + (saved?.error || "Save failed.") + '</span>';
      return;
    }

    status.innerHTML = '<span class="ok">Saved!</span> Redirecting to Shopify checkout…<br/>' +
        '<span class="muted">After payment is confirmed, we will email your access code to: <b>' + email.replace(/</g,"&lt;").replace(/>/g,"&gt;") + '</b></span>';

    try{
      const url = await createCartCheckoutUrl(saved.code, JSON.parse(payloadRaw), email);
      window.location.href = url;
    }catch(e){
      status.innerHTML = '<span class="err">Shopify checkout failed:</span> ' + (e.message || e);
    }
  });
</script>
</body>
</html>