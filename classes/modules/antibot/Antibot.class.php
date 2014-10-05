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

    const BOT_SYMPTOM_01 = '#1 (js: data are missed in session)';
    const BOT_SYMPTOM_02 = '#2 (fake: fake fields filled)';
    const BOT_SYMPTOM_03 = '#3 (fake: fake data are missed in session)';
    const BOT_SYMPTOM_04 = '#4 (js: real login field is missed in POST)';
    const BOT_SYMPTOM_05 = '#5 (js: field "login" is passed in POST)';
    const BOT_SYMPTOM_06 = '#6 (js: js fields are missed in session)';
    const BOT_SYMPTOM_09 = '#9 (sfs: data appears in stopforumspam.org)';

    protected $aMethods = array();
    protected $sReason;

    /**
     * Returns all POST fields in lowercase
     *
     * @return array
     */
    protected function _postFields() {

        $aResult = array();
        if (isset($_SERVER['REQUEST_METHOD'])
            && ($_SERVER['REQUEST_METHOD'] == 'POST')
            && isset($_POST)
        ) {
            $aResult = array_map('strtolower', array_keys($_POST));
        }
        return $aResult;
    }

    /**
     * Initialization
     */
    public function Init() {

        if (Config::Get('plugin.antibot.enable')) {
            $aMethods = (array)Config::Get('plugin.antibot.methods');
            foreach ($aMethods as $sMethod => $aOptions) {
                // Check actions
                if (isset($aOptions['actions'])) {
                    if (is_array($aOptions['actions'])) {
                        foreach($aOptions['actions'] as $sAction) {
                            if (strcasecmp($sAction, Router::GetAction()) === 0) {
                                $this->aMethods[$sMethod]['actions'] = $aOptions;
                            }
                        }
                    } else {
                        $aOptions['actions'] = (string)$aOptions['actions'];
                        if ($aOptions['actions'] == '*') {
                            $this->aMethods[$sMethod]['actions'] = $aOptions;
                        } elseif (strcasecmp($aOptions['actions'], Router::GetAction()) === 0) {
                            $this->aMethods[$sMethod]['actions'] = $aOptions;
                        }
                    }
                }
                // Check POST fiels
                if (isset($aOptions['post']) && ($aPostFields = $this->_postFields())) {
                    if (!is_array($aOptions['post'])) {
                        $aOptions['post'] = array($aOptions['post']);
                    }
                    $aOptions['post'] = array_map('strtolower', $aOptions['post']);
                    $aOptions['post'] = array_intersect($aOptions['post'], $aPostFields);
                    if ($aOptions['post']) {
                        $this->aMethods[$sMethod]['post'] = $aOptions['post'];
                    }
                }
            }
        }
    }

    public function NeedCheck() {

        return $this->aMethods;
    }

    /**
     * @param string $sIp
     */
    protected function _saveBlockIp($sIp = null) {

        $aList = pluginAntibotGetList();
        $aList = pluginAntibotAddIp($aList, $sIp);
        pluginAntibotPutList($aList);
    }

    /**
     * Returns log filename for requested mode ('auth.fail', 'auth.success', 'reg.fail', 'reg.success')
     *
     * @param string $sMode
     *
     * @return string|null
     */
    protected function _getLogFilename($sMode) {

        $sFile = null;
        if (Config::Get('plugin.antibot.logs.enable') && Config::Get('plugin.antibot.logs.' . $sMode . '.enable')) {
            $sFile = Config::Get('plugin.antibot.logs.' . $sMode . '.file');
            if (!$sFile) {
                $sFile = 'antibot.' . str_replace('.', '_', $sMode) . '.log';
            }
        }
        return $sFile;
    }

    /**
     * Logs message
     *
     * @param string $sMode
     * @param string $sMessage
     */
    public function LogOutput($sMode, $sMessage) {

        $sFile = $this->_getLogFilename($sMode);
        if ($sFile) {
            $sMessage = 'ANTIBOT: ' . $sMessage;

            $aKeys = array('_GET', '_POST', '_COOKIE', '_SERVER');
            $aData = array();
            foreach ($GLOBALS as $sKey => $aVal) {
                if (in_array($sKey, $aKeys)) {
                    $aData[$sKey] = $aVal;
                }
            }
            $aSession = $this->Session_Get();
            foreach ($aSession as $sKey => $xVal) {
                $aData['_SESSION'][$sKey] = $xVal;
            }

            if ($aData) {
                $sMessage .= "\n" . print_r($aData, true);
            }
            $this->Logger_Dump($sFile, $sMessage, 'NOTICE');
        }
    }

    /**
     * Проверка на наличие ботов
     *
     * @return bool
     */
    public function BotFree() {

        if (!$this->aMethods) {
            return true;
        }

        $bOk = true;
        $this->sReason = '';
        $sLoginField = '';
        if (isset($this->aMethods['js'])) {
            if (($s = $this->Session_Get('plugin.antibot.js_login')) && is_array($aInputSets = unserialize($s))) {
                $sLoginField = 'login-' . $aInputSets['num'];
            } else {
                $bOk = false;
                $this->sReason = self::BOT_SYMPTOM_01;
            }
        }

        $bOk = ($bOk && $this->_checkFakeFields($sLoginField) && $this->_checkLogin($sLoginField));
        if ($bOk && isset($this->aMethods['sfs'])) {
            $bOk = $this->_checkSfsBase();
        }

        if (!$bOk) {
            $sMsg = strtoupper(Router::GetAction()) . ' - Bot detected ';
            if ($this->sReason) {
                $sMsg .= ' (reason ' . $this->sReason . ')';
            }
            $this->LogOutput('pass.fail', $sMsg);
            if (Config::Get('plugin.antibot.block_ip.enable')) {
                $this->_saveBlockIp();
            }
        } else {
            $this->LogOutput('pass.success', strtoupper(Router::GetAction()) . ' - Bot not detected');
        }

        return $bOk;
    }

    /**
     * @param string $sLoginField
     *
     * @return bool
     */
    protected function _checkFakeFields($sLoginField) {

        $bResult = true;
        if (isset($this->aMethods['fake']) && isset($_POST)) {
            if ($this->Session_Get('plugin.antibot.fake_fields')
                && $this->Session_Get('plugin.antibot.fake_suffix') == Config::Get('plugin.antibot.fake_suffix')) {

                // все, что заканчивается фейковым суффиксом - обманка
                $nLen = strlen($sSuffix = Config::Get('plugin.antibot.fake_suffix'));
                foreach ($_POST as $sKey => $sVal) {
                    if (($sKey != $sLoginField) && substr($sKey, -$nLen) == $sSuffix && $sVal) {
                        // если хоть что-то заполнено - это бот
                        $bResult = false;
                        $this->sReason = self::BOT_SYMPTOM_02;
                        break;
                    }
                }
            } else {
                $bResult = false;
                $this->sReason = self::BOT_SYMPTOM_02;
            }
        }
        return $bResult;
    }

    /**
     * @param string $sLoginField
     *
     * @return bool
     */
    protected function _checkLogin($sLoginField) {

        $bResult = true;
        if (isset($this->aMethods['js']) && isset($_POST)) {
            if (($s = $this->Session_Get('plugin.antibot.js_login')) && is_array($aInputSets = unserialize($s))) {
                if (isset($_POST['fields'])) {
                    foreach ($_POST['fields'] as $nKey => $aData) {
                        if (isset($aData['field']) && $aData['field'] == $sLoginField) {
                            $_POST['fields'][$nKey]['field'] = 'login';
                            if (isset($_REQUEST['fields']) && isset($_REQUEST['fields'][$nKey])
                                && isset($_REQUEST['fields'][$nKey]['field'])
                            ) {
                                $_REQUEST['fields'][$nKey]['field'] = 'login';
                            }
                        }
                    }
                } else {
                    if (!isset($_POST[$sLoginField]) || !isset($_POST['login'])) {
                        $bResult = false;
                        $this->sReason = self::BOT_SYMPTOM_04;
                    } elseif ($_POST['login']) {
                        $bResult = false;
                        $this->sReason = self::BOT_SYMPTOM_05;
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
                $this->sReason = self::BOT_SYMPTOM_06;
            }
        }
        return $bResult;
    }

    protected function _checkSfsBase() {

        $bResult = true;

        $sIp = F::GetUserIp();
        $sUrl = 'http://api.stopforumspam.org/api?ip=' . $sIp . '&f=json';
        $sResult = file_get_contents($sUrl);
        if ($sResult) {
            $aResult = json_decode($sResult, true);
            if (isset($aResult['success']) && $aResult['success'] == 1) {
                if (isset($aResult['ip']['appears']) && $aResult['ip']['appears'] > 0) {
                    $bResult = false;
                    $this->sReason = self::BOT_SYMPTOM_09 . ', ip:' . $sIp;
                }
            }
        }
        return $bResult;
    }

}

// EOF
