# WooCommerce Easebuzz Payment Gateway Plugin

WooCommerce integration plugin for pay with Easebuzz (pay.easebuzz.in)

**Current Plugin Version:** 5.8.0

## Installation Steps

1. Get `woocommerce_easebuzz_plugin.zip`:
   - **If you downloaded the repo as a ZIP from GitHub:** unzip it. Inside, you'll find `woocommerce_easebuzz_plugin.zip` — this is the actual plugin file you need to upload.
   - **If you cloned the repo:** you'll find `woocommerce_easebuzz_plugin.zip` directly at the repository root — no extra unzipping needed.
2. Login to your WordPress admin panel.
3. Go to **Plugins → Add New → Upload Plugin**.
4. Upload `woocommerce_easebuzz_plugin.zip` directly and click **Install Now**.
5. Go to **Plugins → Installed Plugins**, find **Easebuzz Gateway**, and click **Settings** underneath it.
6. Configure the plugin settings:
   - Add your **Key**, **Salt**, and select the **Mode** (Test/Production).
   - If you are using the **iframe** integration, enable the **iframe** option in the plugin settings.
7. Go to **WooCommerce → Settings → Payments** and make sure **Easebuzz Gateway** shows as **Active** (toggle it on if it isn't). The payment method won't appear at checkout until this is enabled.
8. Before making a test transaction, go to **Pages** in the WordPress admin, find the **Checkout** page, and click **Edit**. On that page, remove all existing blocks and add the `[woocommerce_checkout]` shortcode instead.

## Webhook Configuration

Kindly follow the steps below to configure the webhook URL to auto-sync the order status.

1. Copy the webhook file path like: `https://yourdomain.com/wp-content/plugins/woocommerce_easebuzz_plugin/update_webhook.php`
2. To configure the URL in the Easebuzz dashboard, follow the path and enable the same: **Login to Easebuzz Payment Gateway → Product Settings → Webhook → Transaction Webhook**.

## Requirements

- WordPress 6.5+
- WooCommerce 8.2+ (required for High-Performance Order Storage support)
- PHP 7.4+

## Features

- Standard redirect payment flow
- Easecheckout (iframe) payment flow
- WooCommerce Cart/Checkout Blocks support
- High-Performance Order Storage (HPOS) compatible
- Secure reverse-hash (SHA-512) verification on all payment callbacks (redirect, iframe, and webhook)
