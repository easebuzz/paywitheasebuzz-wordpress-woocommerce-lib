woocommerce integration plugin for pay with easebuzz pay.easebuzz.in

steps: 
1. Download paywitheasebuzz-wordpress-woocommerce-lib-master from below link.
2. Extract woocommerce-easebuzz-gateway.zip from master folder.
3. Login to admin.
4. Install woocommerce-easebuzz-gateway.zip in the plugins.
5. Go to installed plugins.
6. Enable Easebuzz.
7. Add Key, Salt and Environment. (Keep SURL & FURL "Checkout" page).


Kindly follow the steps below to configure the webhook URL to auto-sync the order status.

1. set the salt value in the update_webhook.php file.
2. copy the current path of the same file like: https://easebuzzshop.in/wp-content/plugins/woocommerce_easebuzz_plugin/update_webhook.php
3. To configure the URL in the EaseBuzz dashboard, follow the path: Login to EaseBuzz Payment Gateway ----> Account Settings ---> Webhook ---> Transaction Webhook.