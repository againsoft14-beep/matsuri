-- Run after importing the full production database into the local `matsuri` database.
-- Updates OpenCart store URLs from production to local development.

UPDATE ac_setting
SET value = 'http://localhost/matsuri/'
WHERE `key` IN ('config_url', 'config_ssl');

UPDATE ac_store
SET url = 'http://localhost/matsuri/',
    `ssl` = 'http://localhost/matsuri/';
