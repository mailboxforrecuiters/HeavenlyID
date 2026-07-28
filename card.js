// card.js — Heavenly ID card_designs_v2 workflow
// New workflow: no customer image uploads. Saves selected static asset filenames + text/fit data.
// Preserves Shopify Storefront checkout behavior:
// - registered design_id = numeric card_designs_v2.id
// - guest design_id = 20-character card_designs_v2.design_code

(() => {
  "use strict";

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const LOGGED_IN = (
    window.CB_IS_LOGGED_IN === true ||
    window.CB_IS_LOGGED_IN === "1" ||
    window.CB_IS_LOGGED_IN === 1
  );
  let CURRENT_SAVED_ID = "";

  function safeJson(res) {
    return res.json().catch(() => ({}));
  }

  function clean(v) {
    return String(v || "").trim();
  }

  function basenameFromPath(v) {
    v = clean(v).replaceAll("\\", "/");
    return v ? v.split("/").pop() : "";
  }

  function normalizeRelPath(src) {
    const s = clean(src);
    if (!s) return "";
    if (/^https?:\/\//i.test(s)) return s;
    return s.replace(/^\//, "");
  }

  function pathForAsset(dir, fileOrPath) {
    const f = basenameFromPath(fileOrPath);
    return f ? `${dir}/${f}` : "";
  }

  function getValue(id) {
    return clean(document.getElementById(id)?.value || "");
  }

  function setValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = value || "";
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function setHidden(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value || "";
  }

  function boolHidden(id) {
    return getValue(id) === "1" ? 1 : 0;
  }

  function numHidden(id, fallback) {
    const n = parseFloat(getValue(id));
    return Number.isFinite(n) && n > 0 ? n : fallback;
  }

  function getValueAny(ids) {
    ids = Array.isArray(ids) ? ids : [ids];
    for (const id of ids) {
      const value = getValue(id);
      if (value !== "") return value;
    }
    return "";
  }

  function boolHiddenAny(ids) {
    return getValueAny(ids) === "1" ? 1 : 0;
  }

  function numHiddenAny(ids, fallback) {
    const n = parseFloat(getValueAny(ids));
    return Number.isFinite(n) && n >= 0 ? n : fallback;
  }

  function textHiddenAny(ids, fallback) {
    const value = getValueAny(ids);
    return value !== "" ? value : fallback;
  }

  function formatReceivedYear(value) {
    const raw = clean(value);
    if (!raw) return "";

    const iso = raw.match(/^(\d{4})-\d{2}-\d{2}$/);
    if (iso) return iso[1];

    const year = raw.match(/\b(\d{4})\b/);
    return year ? year[1] : raw.replace(/\D/g, "").slice(0, 4);
  }

  function getReceivedYearValue() {
    return formatReceivedYear(getValue("received"));
  }

  function validateReceivedJesusYear() {
    const input = $("#received");
    if (!input) return true;

    const value = clean(input.value);
    const ok = value === "" || /^\d{4}$/.test(value);
    input.setAttribute("aria-invalid", ok ? "false" : "true");
    input.classList.toggle("is-invalid-year", !ok);

    if (!ok) {
      alert("Received Jesus must be exactly 4 digits, e.g. 1992.");
      input.focus();
      return false;
    }

    return true;
  }

  function getShopifyCfg() {
    const cfg = (window.CB_SHOPIFY || {});
    return {
      domain: clean(cfg.domain),
      token: clean(cfg.storefrontAccessToken),
      apiVersion: clean(cfg.apiVersion) || "2026-01",
      productIdOrGid: clean(cfg.productId || cfg.productGid)
    };
  }

  function toProductGid(productIdOrGid) {
    const v = clean(productIdOrGid);
    if (!v) return "";
    if (v.startsWith("gid://")) return v;
    if (/^\d+$/.test(v)) return `gid://shopify/Product/${v}`;
    return v;
  }

  async function shopifyGraphql(query, variables) {
    const { domain, token, apiVersion } = getShopifyCfg();
    if (!domain || !token) throw new Error("Missing Shopify checkout configuration.");

    const cleanDomain = domain.replace(/^https?:\/\//i, "");
    const endpoint = `https://${cleanDomain}/api/${apiVersion}/graphql.json`;

    const res = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Shopify-Storefront-Access-Token": token
      },
      body: JSON.stringify({ query, variables: variables || {} })
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(`Shopify API HTTP ${res.status}`);
    if (json.errors?.length) throw new Error(json.errors.map(e => e.message).join("; "));
    return json.data;
  }

  async function getFirstVariantGidForProduct(productIdOrGid) {
    const productGid = toProductGid(productIdOrGid);
    if (!productGid) throw new Error("Missing Shopify product id.");

    const cacheKey = "cb_variant_gid_" + productGid;
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) return cached;
    } catch (_) {}

    const q = `
      query GetFirstVariant($id: ID!) {
        product(id: $id) {
          variants(first: 1) { edges { node { id availableForSale } } }
        }
      }
    `;
    const data = await shopifyGraphql(q, { id: productGid });
    const variantGid = data?.product?.variants?.edges?.[0]?.node?.id || "";
    if (!variantGid) throw new Error("Could not find a product variant for checkout.");

    try { sessionStorage.setItem(cacheKey, String(variantGid)); } catch (_) {}
    return String(variantGid);
  }

  async function createShopifyCartAndGetCheckoutUrl(designIdOrCode) {
    const { productIdOrGid } = getShopifyCfg();
    const variantGid = await getFirstVariantGidForProduct(productIdOrGid);

    const m = `
      mutation CreateCart($lines: [CartLineInput!]!) {
        cartCreate(input: { lines: $lines }) {
          cart { checkoutUrl }
          userErrors { message }
        }
      }
    `;

    const lines = [{
      quantity: 1,
      merchandiseId: variantGid,
      attributes: [{ key: "design_id", value: String(designIdOrCode) }]
    }];

    const data = await shopifyGraphql(m, { lines });
    const errs = data?.cartCreate?.userErrors || [];
    if (errs.length) throw new Error(errs.map(e => e.message).join("; "));

    const url = data?.cartCreate?.cart?.checkoutUrl || "";
    if (!url) throw new Error("Shopify did not return a checkout URL.");
    return String(url);
  }

  function syncCurrentDesignIdGlobal(id) {
    const v = clean(id);
    CURRENT_SAVED_ID = v;
    try {
      if (window.CB_setCurrentDesignId) window.CB_setCurrentDesignId(v);
      else window.CB_CURRENT_DESIGN_ID = v || null;
    } catch (_) {}
  }

  function getLetterIntentFull() {
    if (typeof window.CB_getLetterOfIntentText === "function") {
      return clean(window.CB_getLetterOfIntentText());
    }
    const name = getValue("line1") || "Enter Full Name";
    const body = getValue("letterEditor");
    return clean(body ? `I ${name} ${body}` : `I ${name}`);
  }

  function extractLetterBody(fullText, fullName) {
    let s = clean(fullText);
    const prefix = clean(`I ${fullName}`);
    if (prefix && s.toLowerCase().startsWith(prefix.toLowerCase())) {
      s = clean(s.slice(prefix.length));
    }
    return s;
  }

  function buildDesignPayload() {
    const foregroundFile = basenameFromPath(getValue("foregroundFileName") || getValue("foregroundPath"));
    const frontFile = basenameFromPath(getValue("frontThemeFileName") || getValue("cardFrontPath"));
    const backFile = basenameFromPath(getValue("backThemeFileName") || getValue("cardBackPath"));
    const verseText = getValue("previewVerseText") || clean(document.getElementById("scriptureDisplay")?.textContent || "");

    return {
      saved_id: CURRENT_SAVED_ID || "",
      design_title: getValue("designTitle") || "My Heavenly ID",
      full_name: getValue("line1"),
      iam_status: getValue("status") || getValue("statusVisible"),
      spiritual_gifts: getValue("gifts"),
      received_jesus_date: getReceivedYearValue(),
      favorite_verse_ref: getValue("verseRef"),
      verse_text: verseText,
      letter_of_intent: getLetterIntentFull(),
      name_font_resized: boolHiddenAny(["name_font_resized", "nameFontResized"]),
      name_font_size_px: numHiddenAny(["name_font_size_px", "nameFontSizePx"], 82),
      name_layout_left_px: numHiddenAny(["name_layout_left_px"], 405),
      name_layout_top_px: numHiddenAny(["name_layout_top_px"], 252),
      name_layout_width_px: numHiddenAny(["name_layout_width_px"], 473),
      name_layout_height_px: numHiddenAny(["name_layout_height_px"], 112),
      name_safe_right_px: numHiddenAny(["name_safe_right_px"], 896),
      name_available_width_px: numHiddenAny(["name_available_width_px"], 473),
      name_text_align: textHiddenAny(["name_text_align"], "left"),
      name_padding_left_px: numHiddenAny(["name_padding_left_px"], 0),
      letter_font_resized: boolHidden("letterFontResized"),
      letter_font_size_px: numHidden("letterFontSizePx", 18),
      front_theme_file: frontFile,
      back_theme_file: backFile,
      front_theme_style: getValue("frontThemeStyleKey"),
      foreground_file: foregroundFile,
      ts: Date.now()
    };
  }


  function setDesignTitleError(message) {
    const input = $("#designTitle");
    const panel = $("#designTitlePanel");
    const error = $("#designTitleError");
    if (!input) return;

    input.classList.add("is-invalid");
    input.setAttribute("aria-invalid", "true");
    if (error) {
      error.textContent = message || "Please enter a design title so you can find this card later.";
      error.classList.add("is-visible");
    }
    if (panel) {
      panel.classList.remove("shake");
      void panel.offsetWidth;
      panel.classList.add("shake");
      try { panel.scrollIntoView({ behavior: "smooth", block: "center" }); } catch (_) {}
    } else {
      try { input.scrollIntoView({ behavior: "smooth", block: "center" }); } catch (_) {}
    }
    setTimeout(() => input.focus(), 80);
  }

  function clearDesignTitleError() {
    const input = $("#designTitle");
    const panel = $("#designTitlePanel");
    const error = $("#designTitleError");
    if (input) {
      input.classList.remove("is-invalid");
      input.removeAttribute("aria-invalid");
    }
    if (panel) panel.classList.remove("shake");
    if (error) error.classList.remove("is-visible");
  }

  function validateDesignTitle() {
    const input = $("#designTitle");
    if (!input) return true;
    if (clean(input.value) !== "") {
      clearDesignTitleError();
      return true;
    }
    setDesignTitleError("Please enter a design title before continuing.");
    return false;
  }

  function setActiveThemeByFile(frontFile) {
    const target = basenameFromPath(frontFile);
    $$(".theme-option").forEach(opt => {
      const optFile = basenameFromPath(opt.dataset.front || "");
      opt.classList.toggle("active", !!target && optFile === target);
    });
  }

  function applyDesignToUI(card) {
    if (!card) return;
    syncCurrentDesignIdGlobal(card.id || "");

    setValue("designTitle", card.design_title || "");
    setValue("line1", card.full_name || "");
    setValue("status", card.iam_status || "");
    setValue("statusVisible", card.iam_status || "");
    setValue("gifts", card.spiritual_gifts || "");
    setValue("verseRef", card.favorite_verse_ref || "");

    const received = formatReceivedYear(card.received_jesus_date || card.received_jesus || "");
    setValue("received", received);

    const fullLetter = card.letter_of_intent || "";
    setValue("letterEditor", extractLetterBody(fullLetter, card.full_name || ""));

    setHidden("name_font_resized", String(card.name_font_resized || 0));
    setHidden("name_font_size_px", String(card.name_font_size_px || 82));
    setHidden("name_layout_left_px", String(card.name_layout_left_px || 405));
    setHidden("name_layout_top_px", String(card.name_layout_top_px || 252));
    setHidden("name_layout_width_px", String(card.name_layout_width_px || 473));
    setHidden("name_layout_height_px", String(card.name_layout_height_px || 112));
    setHidden("name_safe_right_px", String(card.name_safe_right_px || 896));
    setHidden("name_available_width_px", String(card.name_available_width_px || 473));
    setHidden("name_text_align", String(card.name_text_align || "left"));
    setHidden("name_padding_left_px", String(card.name_padding_left_px || 0));

    // Legacy camelCase hidden IDs retained for older cached markup.
    setHidden("nameFontResized", String(card.name_font_resized || 0));
    setHidden("nameFontSizePx", String(card.name_font_size_px || 82));
    setHidden("letterFontResized", String(card.letter_font_resized || 0));
    setHidden("letterFontSizePx", String(card.letter_font_size_px || 18));

    const frontFile = basenameFromPath(card.front_theme_file || card.front_theme_path || "");
    const backFile = basenameFromPath(card.back_theme_file || card.back_theme_path || "");
    const foregroundFile = basenameFromPath(card.foreground_file || card.foreground_path || "");

    setHidden("frontThemeFileName", frontFile);
    setHidden("backThemeFileName", backFile);
    setHidden("foregroundFileName", foregroundFile);
    setHidden("frontThemeStyleKey", card.front_theme_style || "");
    setHidden("cardFrontPath", pathForAsset("newcrdbg", frontFile));
    setHidden("cardBackPath", pathForAsset("backcrdbg", backFile));
    setHidden("foregroundPath", pathForAsset("foreground", foregroundFile));

    const cardFront = $(".card-front");
    if (cardFront && frontFile) {
      cardFront.style.backgroundImage = `url('${pathForAsset("newcrdbg", frontFile)}')`;
      cardFront.style.backgroundRepeat = "no-repeat";
      cardFront.style.backgroundPosition = "center center";
      cardFront.style.backgroundSize = "100% 100%";
    }
    const backImg = $("#cardBackImage");
    if (backImg && backFile) backImg.src = pathForAsset("backcrdbg", backFile);

    const fgImg = $("#foregroundImg");
    const fgSection = $("#foregroundCarousel");
    if (fgImg) {
      if (foregroundFile) {
        fgImg.src = pathForAsset("foreground", foregroundFile);
        fgImg.style.display = "block";
        if (fgSection) fgSection.classList.remove("is-empty");
      } else {
        fgImg.removeAttribute("src");
        if (fgSection) fgSection.classList.add("is-empty");
      }
    }

    setActiveThemeByFile(frontFile);

    const verseTextEl = $("#previewVerseText") || $("#scriptureDisplay");
    if (verseTextEl) verseTextEl.textContent = card.verse_text || card.favorite_verse_ref || "";

    ["line1","status","statusVisible","gifts","received","verseRef","letterEditor"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.dispatchEvent(new Event("input", { bubbles: true }));
    });
    if (typeof window.CB_refitCardText === "function") window.CB_refitCardText();
  }

  window.loadSavedCard = async function loadSavedCard(id) {
    const res = await fetch(`/load_saved_card.php?id=${encodeURIComponent(id)}`, { credentials: "same-origin" });
    const json = await safeJson(res);
    if (!json.success) throw new Error(json.error || "Load failed");
    applyDesignToUI(json.card || {});
    return json.card || {};
  };

  function stripIdsDeep(root) {
    root.querySelectorAll("[id]").forEach(el => el.removeAttribute("id"));
  }

  function lockClone(root) {
    /*
      Do not disable inputs in the preview clone.
      Disabled browser styling can change font weight/color/proportion.
      Keep fields visible exactly like the live card, but make them non-interactive.
    */
    root.querySelectorAll("input, textarea, select").forEach(el => {
      el.readOnly = true;
      el.tabIndex = -1;
      el.removeAttribute("placeholder");
      el.style.pointerEvents = "none";
    });
    root.querySelectorAll("button,a").forEach(el => { el.style.pointerEvents = "none"; });
  }

  function copyComputedTextStyle(fromEl, toEl) {
    if (!fromEl || !toEl) return;
    const cs = window.getComputedStyle(fromEl);
    [
      "fontFamily", "fontSize", "fontWeight", "fontStyle", "letterSpacing",
      "lineHeight", "textAlign", "color", "textTransform"
    ].forEach(prop => {
      try { toEl.style[prop] = cs[prop]; } catch (_) {}
    });
    try { toEl.style.webkitTextFillColor = cs.color; } catch (_) {}
    toEl.style.opacity = "1";
    toEl.style.visibility = "visible";
    toEl.style.background = "transparent";
  }

  function addPreviewTextOverlay(liveEl, cloneEl, cloneCard, value) {
    value = clean(value);
    if (!liveEl || !cloneEl || !cloneCard || !value) return;

    const liveCard = $("#card");
    if (!liveCard) return;

    const targetFace = cloneEl.closest(".card-face") || cloneCard;
    const liveRect = liveEl.getBoundingClientRect();
    const cardRect = liveCard.getBoundingClientRect();
    if (!liveRect.width || !liveRect.height || !cardRect.width || !cardRect.height) return;

    const left = ((liveRect.left - cardRect.left) / cardRect.width) * 1000;
    const top = ((liveRect.top - cardRect.top) / cardRect.height) * 667;
    const width = (liveRect.width / cardRect.width) * 1000;
    const height = (liveRect.height / cardRect.height) * 667;

    const cs = window.getComputedStyle(liveEl);
    const span = document.createElement("span");
    span.className = "cb-preview-live-value";
    span.textContent = value;

    span.style.position = "absolute";
    span.style.left = left + "px";
    span.style.top = top + "px";
    span.style.width = width + "px";
    span.style.height = height + "px";
    span.style.display = "flex";
    span.style.alignItems = "center";
    span.style.justifyContent = cs.textAlign === "center" ? "center" : (cs.textAlign === "right" ? "flex-end" : "flex-start");
    span.style.whiteSpace = liveEl.tagName && liveEl.tagName.toLowerCase() === "textarea" ? "pre-wrap" : "nowrap";
    span.style.overflow = "hidden";
    span.style.pointerEvents = "none";
    span.style.zIndex = "30";
    span.style.boxSizing = "border-box";
    span.style.paddingLeft = cs.paddingLeft;
    span.style.paddingRight = cs.paddingRight;
    span.style.paddingTop = cs.paddingTop;
    span.style.paddingBottom = cs.paddingBottom;
    span.style.fontFamily = cs.fontFamily;
    span.style.fontSize = cs.fontSize;
    span.style.fontWeight = cs.fontWeight;
    span.style.fontStyle = cs.fontStyle;
    span.style.letterSpacing = cs.letterSpacing;
    span.style.lineHeight = cs.lineHeight;
    span.style.textAlign = cs.textAlign;
    span.style.color = cs.color;
    span.style.webkitTextFillColor = cs.color;
    span.style.background = "transparent";

    /*
      Hide the cloned form-control paint and keep only the overlay value.
      This prevents textarea borders/boxes and stops front-face value overlays
      from appearing when the preview is flipped to the back.
    */
    cloneEl.style.color = "transparent";
    cloneEl.style.webkitTextFillColor = "transparent";
    cloneEl.style.caretColor = "transparent";
    cloneEl.style.textShadow = "none";
    cloneEl.style.border = "0";
    cloneEl.style.outline = "0";
    cloneEl.style.boxShadow = "none";
    cloneEl.style.background = "transparent";
    cloneEl.style.resize = "none";

    targetFace.appendChild(span);
  }

  function syncCloneFromLive(liveCard, cloneCard) {
    /*
      Preview values are rendered as absolute overlays based on the live input's
      exact bounding box and computed font styles. This avoids browser quirks
      where cloned input values appear blank inside modal clones.
    */
    cloneCard.querySelectorAll(".cb-preview-live-value").forEach(el => el.remove());

    const liveInputs  = liveCard.querySelectorAll("input.card-input, input.cb-name-input");
    const cloneInputs = cloneCard.querySelectorAll("input.card-input, input.cb-name-input");

    for (let i = 0; i < Math.min(liveInputs.length, cloneInputs.length); i++) {
      const val = clean(liveInputs[i].value || "");
      cloneInputs[i].value = val;
      cloneInputs[i].defaultValue = val;
      cloneInputs[i].setAttribute("value", val);
      cloneInputs[i].style.display = window.getComputedStyle(liveInputs[i]).display;
      cloneInputs[i].style.opacity = "1";
      cloneInputs[i].style.visibility = "visible";
      copyComputedTextStyle(liveInputs[i], cloneInputs[i]);

      const fitSize = liveInputs[i].style.getPropertyValue("--cb-name-fit-size");
      if (fitSize) cloneInputs[i].style.setProperty("--cb-name-fit-size", fitSize);

      addPreviewTextOverlay(liveInputs[i], cloneInputs[i], cloneCard, val);
    }

    const liveTextareas = liveCard.querySelectorAll("textarea");
    const cloneTextareas = cloneCard.querySelectorAll("textarea");
    for (let i = 0; i < Math.min(liveTextareas.length, cloneTextareas.length); i++) {
      const val = liveTextareas[i].value || "";
      cloneTextareas[i].value = val;
      cloneTextareas[i].defaultValue = val;
      cloneTextareas[i].textContent = val;
      copyComputedTextStyle(liveTextareas[i], cloneTextareas[i]);
      cloneTextareas[i].style.border = "0";
      cloneTextareas[i].style.outline = "0";
      cloneTextareas[i].style.boxShadow = "none";
      cloneTextareas[i].style.background = "transparent";
      cloneTextareas[i].style.resize = "none";
      addPreviewTextOverlay(liveTextareas[i], cloneTextareas[i], cloneCard, val);
    }

    const liveLetterShell = $("#letterEditorShell");
    const cloneLetterShell = cloneCard.querySelector("#letterEditorShell, .letter-editor-shell");
    if (liveLetterShell && cloneLetterShell) {
      const letterFit = liveLetterShell.style.getPropertyValue("--cb-letter-fit-size");
      if (letterFit) cloneLetterShell.style.setProperty("--cb-letter-fit-size", letterFit);
    }

    const liveForegroundSection = $("#foregroundCarousel");
    const cloneForegroundSection = cloneCard.querySelector("#foregroundCarousel, .foreground-section");
    const cloneForegroundWrap = cloneForegroundSection ? cloneForegroundSection.querySelector(".foreground-image-wrap") : null;
    const liveForeground = $("#foregroundImg");
    const cloneForeground = cloneCard.querySelector("#foregroundImg, .foreground-section img");

    if (cloneForegroundSection) {
      cloneForegroundSection.className = liveForegroundSection ? liveForegroundSection.className : cloneForegroundSection.className;
      cloneForegroundSection.setAttribute("style", "position:absolute;left:92px;top:196px;width:192px;aspect-ratio:708/1163;z-index:6;display:flex;align-items:center;justify-content:center;overflow:visible;background:transparent;");
    }

    if (cloneForegroundWrap) {
      cloneForegroundWrap.setAttribute("style", "position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;");
    }

    if (cloneForeground && liveForeground) {
      cloneForeground.src = liveForeground.getAttribute("src") || "";
      cloneForeground.setAttribute("style", "display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;object-position:center center;opacity:1;border:0;background:transparent;");
    }

    const liveBack = $("#cardBackImage");
    const cloneBack = cloneCard.querySelector("#cardBackImage, .card-back img");
    if (cloneBack && liveBack) cloneBack.src = liveBack.getAttribute("src") || "";

    cloneCard.classList.toggle("flipped", liveCard.classList.contains("flipped"));
  }

  async function buildPreview() {
    if (document.fonts && document.fonts.ready) {
      try { await document.fonts.ready; } catch (_) {}
    }
    const card = $("#card");
    const previewMount = $("#previewMount");
    if (!previewMount || !card) return null;

    previewMount.innerHTML = "";
    const scale = 333 / 1000;
    const scaler = document.createElement("div");
    scaler.style.width = "1000px";
    scaler.style.height = "667px";
    scaler.style.transform = `scale(${scale})`;
    scaler.style.transformOrigin = "top left";
    scaler.style.position = "absolute";
    scaler.style.left = "0";
    scaler.style.top = "0";

    const clone = card.cloneNode(true);
    clone.style.maxWidth = "none";
    clone.style.width = "1000px";
    clone.style.height = "667px";
    syncCloneFromLive(card, clone);
    stripIdsDeep(clone);
    lockClone(clone);
    scaler.appendChild(clone);
    previewMount.appendChild(scaler);
    return clone;
  }

  async function capturePreviews() {
    if (!window.html2canvas) throw new Error("Preview engine is not available.");
    const card = $("#card");
    if (!card) throw new Error("Card element not found.");

    const holder = document.createElement("div");
    holder.style.position = "fixed";
    holder.style.left = "-10000px";
    holder.style.top = "0";
    holder.style.width = "1000px";
    holder.style.height = "667px";
    holder.style.pointerEvents = "none";
    holder.style.opacity = "0";
    document.body.appendChild(holder);

    const clone = card.cloneNode(true);
    clone.style.maxWidth = "none";
    clone.style.width = "1000px";
    clone.style.height = "667px";
    syncCloneFromLive(card, clone);
    stripIdsDeep(clone);
    lockClone(clone);
    holder.appendChild(clone);

    async function shot(isBack) {
      clone.classList.toggle("flipped", !!isBack);
      await new Promise(r => requestAnimationFrame(r));
      await new Promise(r => requestAnimationFrame(r));
      const canvas = await html2canvas(clone, { backgroundColor: null, scale: 2, useCORS: true });
      return canvas.toDataURL("image/png");
    }

    try {
      return { front: await shot(false), back: await shot(true) };
    } finally {
      holder.remove();
    }
  }

  window.addEventListener("DOMContentLoaded", () => {
    const authModal = $("#authModal");
    const joinForm = $("#joinForm");
    const signinForm = $("#signinForm");
    const btnFlip = $("#btnFlip");
    const btnPreview = $("#btnPreview");
    const btnSave = $("#btnSave");
    const btnDownload = $("#btnDownload");
    const designTitle = $("#designTitle");
    const savedDesigns = $("#savedDesigns");

    function openAuthModal() {
      document.dispatchEvent(new Event("heavenly:open-auth"));
      if (authModal) authModal.style.display = "flex";
    }

    designTitle?.addEventListener("input", clearDesignTitleError);
    designTitle?.addEventListener("blur", () => {
      if (clean(designTitle.value) !== "") clearDesignTitleError();
    });
    const previewModal = $("#previewModal");
    const previewMount = $("#previewMount");
    const previewCloseBtn = $("#previewCloseBtn");
    let previewCardClone = null;

    if (joinForm) {
      joinForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(joinForm);
        fetch("register_user.php", { method: "POST", body: formData, credentials: "same-origin" })
          .then(r => r.json())
          .then(json => {
            if (!json.success) throw new Error(json.error || "Registration failed");
            if (authModal) authModal.style.display = "none";
            alert("Account created successfully!");
            location.reload();
          })
          .catch(err => alert(err.message || "Registration failed"));
      });
    }

    if (signinForm) {
      signinForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(signinForm);
        fetch("login_user.php", { method: "POST", body: formData, credentials: "same-origin" })
          .then(r => r.json())
          .then(json => {
            if (json.error || json.success === false) throw new Error(json.error || "Login failed");
            location.reload();
          })
          .catch(err => alert(err.message || "Login failed"));
      });
    }

    btnFlip?.addEventListener("click", (e) => {
      e.preventDefault();
      $("#card")?.classList.toggle("flipped");
    });

    btnPreview?.addEventListener("click", async (e) => {
      e.preventDefault();
      previewCardClone = await buildPreview();
      if (previewModal) {
        previewModal.style.display = "flex";
        document.body.classList.add("cb-modal-open");
      }
    });
    previewCloseBtn?.addEventListener("click", (e) => {
      e.preventDefault();
      if (previewModal) previewModal.style.display = "none";
      document.body.classList.remove("cb-modal-open");
    });
    previewModal?.addEventListener("click", (e) => {
      if (e.target === previewModal) {
        previewModal.style.display = "none";
        document.body.classList.remove("cb-modal-open");
      }
    });
    previewMount?.addEventListener("click", (e) => {
      e.preventDefault();
      if (previewCardClone) previewCardClone.classList.toggle("flipped");
    });

    async function loadSavedList(selectIdToKeep = "") {
      if (!LOGGED_IN || !savedDesigns) return;
      const res = await fetch("list_cards.php", { credentials: "same-origin" });
      const json = await safeJson(res);
      const cards = Array.isArray(json.cards) ? json.cards : [];

      savedDesigns.innerHTML = "";
      const ph = document.createElement("option");
      ph.value = "";
      ph.textContent = cards.length ? "Select a saved design…" : "No saved designs yet";
      savedDesigns.appendChild(ph);

      cards.forEach(c => {
        const opt = document.createElement("option");
        opt.value = String(c.id);
        opt.textContent = c.design_title || `Design #${c.id}`;
        savedDesigns.appendChild(opt);
      });

      const want = selectIdToKeep || CURRENT_SAVED_ID;
      if (want) savedDesigns.value = String(want);
    }

    savedDesigns?.addEventListener("change", async () => {
      const id = savedDesigns.value;
      if (!id) return;
      try { await window.loadSavedCard(id); }
      catch (e) { alert(e.message || "Could not load design."); }
    });

    btnSave?.addEventListener("click", async (e) => {
      e.preventDefault();

      if (!LOGGED_IN || btnSave.getAttribute("aria-disabled") === "true" || btnSave.dataset.requiresLogin === "1") {
        openAuthModal();
        return;
      }

      const payload = buildDesignPayload();
      if (!validateDesignTitle()) {
        return;
      }
      if (!payload.full_name) {
        alert("Please enter a Full Name before saving.");
        $("#line1")?.focus();
        return;
      }
      if (!validateReceivedJesusYear() || (typeof window.CB_validateReceivedJesusYear === "function" && !window.CB_validateReceivedJesusYear())) {
        return;
      }
      if (!payload.front_theme_file) {
        alert("Please select a front card graphic.");
        return;
      }

      const oldText = btnSave.textContent;
      btnSave.disabled = true;
      btnSave.textContent = "Saving...";

      try {
        const res = await fetch("/save_card.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload)
        });
        const json = await safeJson(res);
        if (!json.success) throw new Error(json.error || "Save failed");

        syncCurrentDesignIdGlobal(json.saved_id || "");
        alert("Saved! Card ID: " + CURRENT_SAVED_ID);
        await loadSavedList(CURRENT_SAVED_ID);
      } catch (err) {
        alert(err.message || "Save failed.");
      } finally {
        btnSave.disabled = false;
        btnSave.textContent = oldText;
      }
    });

    const GUEST_KEY_PAYLOAD = "cb_guest_checkout_payload_v1";
    const GUEST_KEY_FRONT   = "cb_guest_preview_front_v1";
    const GUEST_KEY_BACK    = "cb_guest_preview_back_v1";

    btnDownload?.addEventListener("click", async (e) => {
      e.preventDefault();

      if (!LOGGED_IN || btnDownload.getAttribute("aria-disabled") === "true" || btnDownload.dataset.requiresLogin === "1") {
        openAuthModal();
        return;
      }

      const savedId = (CURRENT_SAVED_ID || savedDesigns?.value || "").trim();
      const noteEl = $("#downloadNote");
      const setNote = (t) => { if (noteEl) noteEl.textContent = t || ""; };

      if (!savedId && !LOGGED_IN) {
        try {
          setNote("Preparing guest checkout…");
          const payload = buildDesignPayload();

          if (!validateDesignTitle()) {
            setNote("");
            return;
          }

          if (!payload.full_name) {
            setNote("");
            alert("Please enter a Full Name before checkout.");
            return;
          }
          if (!validateReceivedJesusYear() || (typeof window.CB_validateReceivedJesusYear === "function" && !window.CB_validateReceivedJesusYear())) {
            setNote("");
            return;
          }
          if (!payload.front_theme_file) {
            setNote("");
            alert("Please select a front card graphic before checkout.");
            return;
          }

          const previews = await capturePreviews();
          sessionStorage.setItem(GUEST_KEY_PAYLOAD, JSON.stringify(payload));
          sessionStorage.setItem(GUEST_KEY_FRONT, previews.front || "");
          sessionStorage.setItem(GUEST_KEY_BACK, previews.back || "");
          window.location.href = "/guest_checkout.php";
        } catch (err) {
          console.error(err);
          setNote("");
          alert(err.message || "Could not prepare guest checkout. Please try again.");
        }
        return;
      }

      if (!savedId) {
        alert("Please save or select a design before checkout.");
        return;
      }

      syncCurrentDesignIdGlobal(savedId);

      try {
        const cfg = getShopifyCfg();
        if (cfg.domain && cfg.token && cfg.productIdOrGid) {
          setNote("Redirecting to Shopify checkout…");
          const checkoutUrl = await createShopifyCartAndGetCheckoutUrl(savedId);
          window.location.href = checkoutUrl;
          return;
        }
      } catch (err) {
        console.warn("Shopify checkout flow failed; falling back to create_checkout.php:", err);
        setNote("");
      }

      const fd = new FormData();
      fd.append("saved_id", savedId);

      const res = await fetch("create_checkout.php", { method: "POST", body: fd, credentials: "same-origin" });
      const json = await safeJson(res);

      if (!json.success || !json.url) {
        alert(json.error || "Checkout failed.");
        return;
      }

      window.location.href = json.url;
    });

    if (LOGGED_IN) loadSavedList();

    const params = new URLSearchParams(window.location.search);
    const loadId = params.get("load_id");
    if (loadId && LOGGED_IN) {
      window.loadSavedCard(loadId).catch(err => alert(err.message || "Could not load design."));
    }
  });
})();
