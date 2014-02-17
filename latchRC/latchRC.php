<?php

/*
  Latch Roundcube plugin - Integrates Latch into the Roundcube authentication process.
  Copyright (C) 2013 Eleven Paths

  This library is free software; you can redistribute it and/or
  modify it under the terms of the GNU Lesser General Public
  License as published by the Free Software Foundation; either
  version 2.1 of the License, or (at your option) any later version.

  This library is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public
  License along with this library; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once 'sdk/Error.php';
require_once 'sdk/LatchResponse.php';
require_once 'sdk/Latch.php';

class latchRC extends rcube_plugin {
    
    private static $PAIRING_ERROR_MESSAGE = "The account has not been paired succesfully";

    /*
     * Instance of the Roundcube Application object
     */
    private $rc;

    /*
     * Initialize the plugin: load the configuration and register hooks.
     */
    function init() {
        $this->load_config(); // Load the AppId and AppSecret from the plugin configuration file
        $this->rc = rcmail::get_instance();

        // Register hooks for login and settings menu
        $this->add_hook('authenticate', array($this, 'hookLatchLogin'));
        $this->add_hook('preferences_sections_list', array($this, 'addLatchSection'));
        $this->add_hook('preferences_list', array($this, 'addLatchPreferences'));
        $this->add_hook('preferences_save', array($this, 'saveLatchPreferences'));
    }

    /*
     * Authenticate hook callback. Integrates Latch into the authentication process.
     */
    function hookLatchLogin($args) {
        $loginOk = $this->rc->login($args['user'], $args['pass'], $args['host'], $args['cookiecheck']);
        if (!$loginOk) {
            $this->makeLoginFail($args);
            return $args;
        }
        if (isset($_POST['latch_twoFactor'])) {
            $this->checkSecondFactor($args);
        } elseif ($loginOk && $this->isAccountBlockedByLatch()) {
            $this->makeLoginFail($args);
        }
        return $args;
    }

    private function checkSecondFactor(&$args) {
        $storedToken = $this->rc->config->get("latch_twoFactor");
        $this->rc->user->save_prefs(array("latch_twoFactor" => NULL));
        if ($storedToken != $_POST['latch_twoFactor']) {
            $this->makeLoginFail($args);
        }
    }

    private function makeLoginFail(&$args) {
        $this->rc->kill_session();
        $args['abort'] = true;
    }

    function isAccountBlockedByLatch() {
        $latchId = $this->rc->config->get('latchId');
        if (!empty($latchId)) {
            $status = $this->getLatchStatus($latchId);
            if (isset($status["twoFactor"])) { // Second authentication factor required
                $this->requireSecondFactor($status['twoFactor']);
            }
            return $status['accountBlocked'];
        }
        return false;
    }

    private function getLatchStatus($latchId) {
        $api = $this->getAPIConnection();
        $statusResponse = $api->status($latchId);
        return $this->processStatusResponse($statusResponse);
    }

    /*
     * Returns an associative array with a boolean value that tells if the
     * account is blocked by Latch and the second factor password if it is
     * required.
     */
    private function processStatusResponse($statusResponse) {
        $responseData = $statusResponse->getData();
        $responseError = $statusResponse->getError();
        if (!empty($responseError) && $responseError->getCode() == 201) {
            // If the account is externally unpaired, apply the changes in the database						
            $this->rc->user->save_prefs(array('latchId' => NULL));
        }
        if (!empty($responseData)) {
            $status = array("accountBlocked" => $this->getLatchStatusFromResponse($responseData) == "off");
            $oneTimePassword = $this->getSecondFactorOTP($responseData);
            if (!empty($oneTimePassword)) {
                $status["twoFactor"] = $oneTimePassword;
            }
            return $status;
        }
        // If the server is down or there is a problem with the response structure
        // Allow the user to log in to prevent DoS
        return array("accountBlocked" => false);
    }

    /*
     * Saves the one-time password to check it later, deletes the session, and
     * loads a form asking for the second authentication factor.
     */
    private function requireSecondFactor($secondFactorToken) {
        // Save user preferences to database
        $this->rc->user->save_prefs(array("latch_twoFactor" => $secondFactorToken));
        $this->rc->kill_session();  // The user cannot be authenticated yet
        $this->loadSecondFactorForm();
    }

    private function loadSecondFactorForm() {
        $csrf_token = $this->rc->get_request_token();
        include 'twoFactor.php';
        die();
    }

    /*
     * Verifies the response structure and returns the status of the Latch account.
     */
    private function getLatchStatusFromResponse($responseData) {
        $appId = $this->rc->config->get('latch_appId');
        if (property_exists($responseData, "operations") &&
                property_exists($responseData->{"operations"}, $appId) &&
                property_exists($responseData->{"operations"}->{$appId}, "status")) {

            return $responseData->{"operations"}->{$appId}->{"status"};
        } else {
            return "off"; // Prevent blocking user because of an unexpected response structure
        }
    }

    private function getSecondFactorOTP($responseData) {
        $appId = $this->rc->config->get('latch_appId');
        if (property_exists($responseData->{"operations"}->{$appId}, "two_factor")) {
            return $responseData->{"operations"}->{$appId}->{"two_factor"}->{"token"};
        } else {
            return NULL;
        }
    }

    /*
     * Hook that adds a section in the Settings page.
     */
    function addLatchSection($args) {
        $args['list']['latch'] = array('id' => 'latch', 'section' => 'Latch settings');
        $this->include_stylesheet("latch.css");
        return $args;
    }

    /*
     * Hook that creates the Latch pairing/unpairing form in the Settings page.
     */
    function addLatchPreferences($args) {
        if ($args['section'] === 'latch') {
            $latchId = $this->rc->config->get('latchId');
            if (empty($latchId)) {
                // Build pairing form
                $pairingTokenInput = new html_inputfield(array(
                    'name' => 'pairingToken',
                    'id' => 'pairingToken'
                ));
                $args['blocks']['latch']['options']['pairingToken'] = array(
                    'title' => html::label('pairingToken', Q('Type your pairing token:')),
                    'content' => $pairingTokenInput->show()
                );
            } else {
                // Build unpairing form
                $unpairingChecBox = new html_checkbox(array(
                    'name' => 'unpairAccount',
                    'id' => 'unpairAccount',
                    'value' => 0
                ));
                $args['blocks']['latch']['options']['unpairAccount'] = array(
                    'title' => html::label('unpairAccount', Q('Unpair your Latch account:')),
                    'content' => $unpairingChecBox->show()
                );
            }
        }
        return $args;
    }

    /*
     * Hook invoked when the Latch settings are saved.
     */
    function saveLatchPreferences($args) {
        if (isset($_POST['pairingToken'])) {
            return $this->performPairing($args);
        } elseif (isset($_POST['unpairAccount'])) {
            return $this->performUnpairing($args);
        }
    }

    private function performPairing($args) {
        $accountId = $this->rc->config->get('latchId');
        if (empty($accountId)) {
            // Avoid pairing a user twice
            $pairingToken = $_POST['pairingToken'];
            $accountId = $this->pairAccount($pairingToken);
            if (empty($accountId)) {
                $args['abort'] = true;
                $args['message'] = self::$PAIRING_ERROR_MESSAGE;
            } else {
                $args['prefs']['latchId'] = $accountId;
            }
        }
        return $args;
    }

    private function pairAccount($pairingToken) {
        $api = $this->getAPIConnection();
        if ($api != NULL) {
            $apiResponse = $api->pair($pairingToken);
            $data = $apiResponse->getData();
            if (!empty($data) && property_exists($data, "accountId")) {
                return $data->{"accountId"};
            }
        }
    }

    private function performUnpairing($args) {
        $accountId = $this->rc->config->get('latchId');
        if (!empty($accountId)) {
            $this->unpairAccount($accountId);
            $args['prefs']['latchId'] = NULL;
        }
        return $args;
    }

    private function unpairAccount($accountId) {
        $api = $this->getAPIConnection();
        if ($api != NULL) {
            $api->unpair($accountId);
        }
    }

    private function getAPIConnection() {
        $appId = $this->rc->config->get('latch_appId');
        $appSecret = $this->rc->config->get('latch_appSecret');
        $host = $this->rc->config->get('latch_host');
        if (!empty($host)) {
            Latch::setHost(rtrim($host, '/'));
        }
        if (!empty($appId) && !empty($appSecret)) {
            return new Latch($appId, $appSecret);
        }
        return NULL;
    }
}
