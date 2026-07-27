# Heavenly ID workflow file tracker

Uploaded/commit batch date: 2026-07-27 15:49 PT

This tracker records which sanitized workflow files were committed through ChatGPT's GitHub connector and which files still need manual upload because they are too large for the connector-safe inline text workflow or require binary/manual handling.

## Committed through connector

- `.env.example` - safe deployment variable template for mail, printer notifications, and Shopify runtime config.
- `.gitignore` - excludes local secrets, DB credentials, logs, dependencies, and generated customer/card files.
- `bible_verse_search.php` - Bible verse reference autocomplete endpoint; DB first, JSON fallback.
- `contact_send.php` - sanitized PHPMailer contact endpoint using environment variables instead of hardcoded SMTP credentials.
- `create_checkout.php` - creates a checkout token for a signed-in user's saved `card_designs_v2` design.
- `footer.php` - shared Heavenly ID footer include.
- `list_cards.php` - returns saved designs for the signed-in user.
- `load_card.php` - loads a saved card with normalized asset paths and verse text.
- `load_saved_card.php` - alternate saved-card reload endpoint for the builder.
- `login_user.php` - sanitized DB-backed login endpoint; hardcoded virtual test user removed.
- `logout.php` - clears the main site and phpBB sessions.
- `save_card.php` - saves or updates card builder designs in `card_designs_v2`.
- `schema/card_designs_v2.schema.sql` - sanitized schema only; no customer rows.
- `schema/users.schema.sql` - sanitized schema only; no customer rows.

## Still needs manual upload

- `cardbuilder.php` - main builder page; sanitized Shopify values, but too large for safe inline connector commit.
- `card.js` - main builder JavaScript; large enough that manual upload is safer.
- `card_designs_v2_lib.php` - required helper library; medium-large and should be manually uploaded from the sanitized package.
- `header.php` - shared responsive header; medium-large and should be manually uploaded from the sanitized package.
- `my_designs.php` - logged-in My Designs page; medium-large and should be manually uploaded from the sanitized package.
- `bible_verse_references.json` - large JSON fallback data for verse autocomplete.

## Do not upload to the public repo

- `protected/pdo.php` - production DB credentials; keep server-only.
- real `.env` files - production credentials; keep server-only.
- live logs such as `*.log`.
- generated customer/card output folders.
- raw font/image asset folders unless manually reviewed for licensing and size.

## Known omitted/legacy files

- `check_out.php`, `create_payment.php`, `payment_success.php` - older/alternate `created_cards` payment flow, not the current verified `card_designs_v2` workflow.
- `foreground_modal.php` - usable modal component, but not directly included by the current builder source.
- `genesis.json` - empty file, no active workflow purpose found.
