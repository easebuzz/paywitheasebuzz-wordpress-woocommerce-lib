# WooCommerce Easebuzz Payment Gateway Plugin

WooCommerce integration plugin for pay with Easebuzz (pay.easebuzz.in)

## Installation Steps

1. Download this repository as a ZIP from the GitHub repo link provided.
2. Inside the repository, you will find `woocommerce_easebuzz_plugin.zip`.
3. Login to your WordPress admin panel.
4. Go to **Plugins → Add New → Upload Plugin**.
5. Upload `woocommerce_easebuzz_plugin.zip` directly and click **Install Now**.
6. Go to **Installed Plugins** and enable **Easebuzz**.
7. Configure the plugin settings:
   - Add your **Key**, **Salt**, and select the **Mode** (Test/Production).
   - If you are using the **iframe** integration, enable the **iframe** option in the plugin settings.

## Webhook Configuration

Kindly follow the steps below to configure the webhook URL to auto-sync the order status.

1. Copy the webhook file path like: `https://yourdomain.com/wp-content/plugins/woocommerce_easebuzz_plugin/update_webhook.php`
2. To configure the URL in the Easebuzz dashboard, follow the path and enable the same: **Login to Easebuzz Payment Gateway → Product Settings → Webhook → Transaction Webhook**.
