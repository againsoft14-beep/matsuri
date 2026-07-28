<?php
$_['heading_title'] = 'Pathao Courier';

$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified Pathao Courier settings!';
$_['text_edit'] = 'Edit Pathao Courier';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_sandbox'] = 'Sandbox';
$_['text_live'] = 'Live';

$_['entry_status'] = 'Status';
$_['entry_sandbox'] = 'Environment';
$_['entry_logging'] = 'Logging';

$_['entry_client_id'] = 'Client ID';
$_['entry_client_secret'] = 'Client Secret';
$_['entry_username'] = 'Username';
$_['entry_password'] = 'Password';

$_['help_client_id'] = 'From Pathao Courier (Aladdin) API — Client ID.';
$_['help_client_secret'] = 'From Pathao Courier (Aladdin) API — Client Secret. Leave blank on save to keep the current secret.';
$_['help_username'] = 'Merchant login (email/phone) for the courier account.';
$_['help_password'] = 'Merchant password. Leave blank on save to keep the current password.';
$_['help_test_connection'] = 'Uses the values in this form (you do not need to Save first).';

$_['entry_default_store'] = 'Default Store';

$_['entry_cron_token'] = 'Cron Token';
$_['help_cron_token'] = 'Set a secret token to protect cron URL. Leave blank to allow access without token. Use this URL in a scheduled task (e.g. wget/curl):';
$_['entry_webhook_secret'] = 'Webhook HMAC / query key';
$_['help_webhook_secret'] = 'Optional shared secret. If set, webhooks must send HMAC-SHA256 of the raw body in X-Pathao-Signature, or pass the same value as the query parameter key (e.g. &key=...). Leave blank to accept unsigned callbacks (less secure). Pathao should POST to:';

$_['button_test_connection'] = 'Test Connection';
$_['button_sync_stores'] = 'Sync Stores';

$_['help_env_override'] = 'You can override environment by defining PATHAO_COURIER_ENV as sandbox/live in index.php.';

$_['error_permission'] = 'Warning: You do not have permission to modify Pathao Courier!';

