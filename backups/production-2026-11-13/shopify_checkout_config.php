<?php
/** Shopify Storefront checkout configuration, shared by signed-in and guest checkout.
 * Only public Storefront credentials belong in shopify_checkout_config.json.
 * Never add Shopify Admin API tokens or webhook HMAC secrets here.
 */
function hid_shopify_checkout_config(): array {
    $configPath = __DIR__ . '/shopify_checkout_config.json';
    $raw = @file_get_contents($configPath);
    if ($raw === false) {
        error_log('Heavenly ID checkout: configuration JSON missing');
        return [];
    }
    $all = json_decode($raw, true);
    if (!is_array($all) || !isset($all['stores']) || !is_array($all['stores'])) {
        error_log('Heavenly ID checkout: malformed Shopify config JSON');
        return [];
    }
    $active = $all['active_store'] ?? '';
    if (!in_array($active, ['dev', 'live'], true)) {
        error_log('Heavenly ID checkout: invalid active_store');
        return [];
    }
    $store = $all['stores'][$active] ?? null;
    if (!is_array($store)) return [];
    $domain = trim((string)($store['domain'] ?? ''));
    $token = trim((string)($store['storefrontAccessToken'] ?? ''));
    $version = trim((string)($store['apiVersion'] ?? ''));
    $product = trim((string)($store['productId'] ?? ''));
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*\.myshopify\.com$/', $domain)
        || $token === '' || !preg_match('/^\d{4}-\d{2}$/', $version)
        || !preg_match('/^(\d+|gid:\/\/shopify\/Product\/\d+)$/', $product)) {
        error_log('Heavenly ID checkout: incomplete or invalid Shopify configuration for ' . $active);
        return [];
    }
    return [
        'domain' => $domain,
        'storefrontAccessToken' => $token,
        'apiVersion' => $version,
        'productId' => $product,
    ];
}