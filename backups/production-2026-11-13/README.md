# Heavenly ID — production-code backup (2026-11-13)

This directory contains **complete updated source files** backed up from the most recent working packages in the Heavenly ID project. It is intentionally isolated from the older application files at the repository root, which have not been synchronized with production.

## Files

- `cardbuilder.php`: updated card information field width, mobile font fitting, "Where did you receive Jesus" free text, pink-only floral cross compatibility, and theme/back behavior.
- `card.js`: merged **free-text** Received Jesus behavior with **customer preview** 1000×655 sizing fixes and synchronized foreground when loading a design. The old four-digit validator is not included.
- `dashboard/design_view.php`: staff preview text rows matched to the 525px info group and 261px value area.
- `guest_checkout.php`: guest checkout page with site-matching typography, header, and background.
- `card_designs_v2_lib.php`: accepts free-text Received Jesus value.
- `shopify_paid_webhook.php`: post-payment printer details, including the received-Jesus free-text answer. The webhook signing secret has been removed from source and must be supplied as `SHOPIFY_WEBHOOK_SECRET` in the server environment before using this backup version.
- `shopify_checkout_config.php`: Dev/Live store configuration loader.
- `shopify_checkout_config.example.json`: **template only**; storefront access tokens deliberately blank.

## SQL

Apply `migrations/2026-11-13-received-jesus-free-text.sql` (at repository root) before deploying application code. Back up your database first. The `received_jesus_date` column remains named the same and becomes `VARCHAR(255)`.

## Security

The production JSON configuration with the currently used Storefront token was **not** committed. The production webhook signing secret was **not** committed. Do not put live secrets into source control. Rotate the prior webhook signing secret because it was hard-coded in a prior local production package.

## Integration warning

GitHub's default-branch root source predates the live site's later changes. These files were backed up as a **snapshot** rather than overwriting older root files and risking unrelated regressions. The production site was **not deployed or modified** as part of the backup.

Some dependencies, including `guest_save_unregistered.php`, are **not included** in this recent-changes snapshot: obtain the deployed production versions when making a fully deployable repository synchronization.

PHP/JS syntax checks passed locally; browser, Shopify payment, and SQL integration testing were not performed.
