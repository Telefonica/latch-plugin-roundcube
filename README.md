### LATCH ROUNDCUBE PLUGIN -- INSTALLATION GUIDE ###

#### PREREQUISITES ####

* RoundCube version 0.8.7 or later.

* Curl extensions active in PHP (uncomment "extension=php_curl.dll" or" extension=curl.so" in Windows or Linux php.ini respectively.

* To get the "Application ID" and "Secret", (fundamental values for integrating Latch in any application), it’s necessary to register a developer account in Latch's website: https://latch.elevenpaths.com. On the upper right side, click on "Developer area".

#### INSTALLING THE MODULE IN ROUNDCUBE ####

1. Once the administrator has downloaded the plugin, it has to be added to the RoundCube plugins directory. Extract the 'latchRC' folder from the ZIP file and copy it to ROUNDCUBE_INSTALLATION_DIR/plugins.

2. To enable the plugin, the file ROUNDCUBE_INSTALLATION_DIR/config/main.inc.php has to be edited, adding the string 'latchRC' to the $rcmail_config['plugins] variable.

3. Once the plugin is enabled, it is necessary to configure the "Application ID" and "Secret" to connect with the Latch API. To configure this, edit the file located at ROUNDCUBE_INSTALLATION_DIR/plugins/latchRC/config.inc.php, and set the $rcmail_config['latch_appId'] and $rcmail_config['latch_appSecret'] configuration variables.
