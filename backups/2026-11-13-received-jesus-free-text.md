# 2026-11-13 — Received Jesus free-text update (production-package change log)

## Production package

Archive: `heavenly-id-received-jesus-free-text-1.zip` (provided in Heavenly ID ChatGPT project). This is based on newer production files than this repository's September main branch.

**Do not deploy the older main-branch `cardbuilder.php` or `card.js` over production.** The repository is not yet a complete mirror of production.

## Updated production files in that package

- `cardbuilder.php`: Change Received Jesus input from four-digit numeric year to 255-character free text. Placeholder: **Where did you receive Jesus**. Keep mobile text responsive, remove numeric-only validation and mobile year sanitizing, fit the display in the existing card value area.
- `card.js`: Preserve the full free-text `received_jesus_date` value during save and load instead of converting the response to a year. Existing guest checkout and Shopify Dev config are unchanged.
- `card_designs_v2_lib.php`: Normalize the field as bounded free text instead of a date.
- `shopify_paid_webhook.php`: Printer-email label updated for the free-text answer.
- `received-jesus-database-migration.sql`: Convert `card_designs_v2.received_jesus_date` to `VARCHAR(255) NULL DEFAULT NULL` (available at `migrations/2026-11-13-received-jesus-free-text.sql`).

## Prior behavior retained in production package

- `foreground2.png` allowed only for `heavenly_id_pink_front.png`. Beige/blue permit only `foreground1.png`.
- Keep matching front/back theme selection.
- Preserve guest checkout, saved design JSON, and existing Shopify development-store destination.

## Deployment order and validation

1. Back up DB, apply SQL migration before code.
2. Use **full production-package versions**, not the older main branch versions.
3. Test custom free text in desktop/mobile, save/reload, guest checkout, Shopify cart metadata, and printer webhook.
4. Confirm PHP and JavaScript syntax and that customer designs remain unchanged.

This file is a safe changelog: it does not claim to be a code backup or record of production deployment.
