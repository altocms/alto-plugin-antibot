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

class PluginAntibot_ModuleAntibot extends Module {

    public function Init() {
    }

    /**
     * Проверка на наличие ботов
     *
     * @return bool
     */
    public function BotFree() {

        if (!Config::Get('plugin.antibot.enable')) {
            return true;
        }

        $bResult = true;
        if (Config::Get('plugin.antibot.js')) {
            if (($s = $this->Session_Get('plugin.antibot.fake_login')) && is_array($aInputSets = unserialize($s))) {
                $sLoginField = 'login-' . $aInputSets['num'];
            } else {
                $bResult = false;
            }
        }

        $bResult = ($bResult && $this->_checkFakeFields($sLoginField) && $this->_checkLogin($sLoginField));

        return $bResult;
    }

    protected function _checkFakeFields($sLoginField) {

        $bResult = true;
        if (Config::Get('plugin.antibot.enable')) {
            if (
                $this->Session_Get('plugin.antibot.fake_fields')
                && $this->Session_Get('plugin.antibot.fake_suffix') == Config::Get('plugin.antibot.fake_suffix')
            ) {
                // все, что заканчивается фейковым суффиксом - обманка
                $nLen = strlen($sSuffix = Config::Get('plugin.antibot.fake_suffix'));
                foreach ($_POST as $sKey => $sVal) {
                    if (($sKey != $sLoginField) && substr($sKey, -$nLen) == $sSuffix && $sVal) {
                        // если хоть что-то заполнено - это бот
                        $bResult = false;
                        break;
                    }
                }
            } else {
                $bResult = false;
            }
        }
        return $bResult;
    }

    protected function _checkLogin($sLoginField) {

        $bResult = true;
        if (Config::Get('plugin.antibot.enable') && Config::Get('plugin.antibot.js')) {
            if (($s = $this->Session_Get('plugin.antibot.fake_login')) && is_array($aInputSets = unserialize($s))) {
                if (isset($_POST['fields'])) {
                    foreach ($_POST['fields'] as $nKey => $aData) {
                        if (isset($aData['field']) && $aData['field'] == $sLoginField) {
                            $_POST['fields'][$nKey]['field'] = 'login';
                            if (isset($_REQUEST['fields']) AND
                                isset($_REQUEST['fields'][$nKey]) && isset($_REQUEST['fields'][$nKey]['field'])
                            ) {
                                $_REQUEST['fields'][$nKey]['field'] = 'login';
                            }
                        }
                    }
                } else {
                    if (!isset($_POST[$sLoginField]) || !isset($_POST['login'])) {
                        $bResult = false;
                        //} elseif (!$_POST[$sLoginField] && $_POST['login']) {
                        //    $bResult = false;
                    } elseif ($_POST['login']) {
                        $bResult = false;
                    } else {
                        if ($_POST[$sLoginField] && !$_POST['login']) {
                            $_POST['login'] = $_POST[$sLoginField];
                            if (isset($_REQUEST['login'])) {
                                $_REQUEST['login'] = $_POST[$sLoginField];
                            }
                        }
                    }
                }
            } else {
                $bResult = false;
            }
        }
        return $bResult;
    }

}

// EOF
