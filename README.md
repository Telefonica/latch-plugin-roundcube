#LATCH INSTALLATION GUIDE FOR ROUNDCUBE


##PREREQUISITES
* RoundCube version 0.8.7 or later.

* Curl extensions active in PHP (uncomment **"extension=php_curl.dll"** or"** extension=curl.so"** in Windows or Linux php.ini respectively).

* To get the **"Application ID"** and **"Secret"**, (fundamental values for integrating Latch in any application), it’s necessary to register a developer account in [Latch's website]( https://latch.elevenpaths.com"https://latch.elevenpaths.com") On the upper right side, click on **"Developer area"**.


##DOWNLOADING THE ROUNDCUBE PLUGIN
 * When the account is activated, the user will be able to create applications with Latch and access to developer documentation, including existing SDKs and plugins. The user has to access again to [Developer area](https://latch.elevenpaths.com/www/developerArea"https://latch.elevenpaths.com/www/developerArea"), and browse his applications from **"My applications"** section in the side menu.

* When creating an application, two fundamental fields are shown: **"Application ID"** and **"Secret"**, keep these for later use. There are some additional parameters to be chosen, as the application icon (that will be shown in Latch) and whether the application will support OTP  (One Time Password) or not.

* From the side menu in developers area, the user can access the **"Documentation & SDKs"** section. Inside it, there is a **"SDKs and Plugins"** menu. Links to different SDKs in different programming languages and plugins developed so far, are shown.


##INSTALLING THE PLUGIN IN ROUNDCUBE
* Once the administrator has downloaded the plugin, it has to be added to the RoundCube plugins directory. Extract the **"latchRC"** folder from the ZIP file and copy it to **ROUNDCUBE_INSTALLATION_DIR/plugin**.

* To enable the plugin, the file **ROUNDCUBE_INSTALLATION_DIR/config/main.inc.php** has to be edited, adding the string **'latchRC'** to the **$rcmail_config['plugins']** variable.

* Once the plugin is enabled, it is necessary to configure the **"Application ID"** and **"Secret"** to contact with the Latch API. To configure this, edit the file located at **ROUNDCUBE_INSTALLATION_DIR/plugins/latchRC/config.inc.php**, in the **$rcmail_config['latch_appId']** and **$rcmail_config['latch_appSecret']** configuration variables.


##UNINSTALLING THE PLUGIN IN ROUNDCUBE
* To uninstall Latch, just comment out the line added above, this way: **//$rcmail_config['plugins'] = array('latchRC')**;