<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Plugin Name: AntiBot
 * @Plugin Id: antibot
 * @Plugin URI:
 * @Description: AntiBot Plugin for Alto CMS
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 */

class PluginAntibot_ActionLogin extends PluginAntibot_Inherits_ActionLogin {

    protected $sError;

    protected function EventAjaxLogin() {

        if (Config::Get('plugin.antibot.logs.enable')) {
            $this->Hook_AddExecFunction('module_message_adderrorsingle_before', array($this, '_hookError'));
        }
        $xResult = parent::EventAjaxLogin();

        if ($this->sError) {
            $this->Antibot_LogOutput('auth.fail', 'Fail of authorisation. Error: ' . $this->sError);
        } else {
            $this->Antibot_LogOutput('auth.success', 'Success of authorisation');
        }
        return $xResult;
    }

    public function _hookError($aArgs) {

        if ($aArgs) {
            if (is_array($aArgs)) {
                $this->sError = array_shift($aArgs);
            } else {
                $this->sError = $aArgs;
            }
        }
    }
}

// EOF