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

class PluginAntibot_HookAntibot extends Hook {

    /**
     * Hooks registration
     */
    public function RegisterHook() {

        if ($this->_isEnable()) {
            if (Config::Get('plugin.antibot.methods.fake') || Config::Get('plugin.antibot.methods.js')) {
                $this->AddHook('template_layout_head_begin', 'TplHtmlHeadBegin');
                $this->AddHook('template_layout_head_end', 'TplHtmlHeadEnd');

                // LS-compatibility
                $this->AddHook('template_html_head_begin', 'TplHtmlHeadBegin');
                $this->AddHook('template_html_head_end', 'TplHtmlHeadEnd');

                $this->AddHook('template_form_login_popup_begin', 'TplFormLoginBegin');
                $this->AddHook('template_form_login_popup_end', 'TplFormLoginEnd');
                $this->AddHook('template_form_registration_begin', 'TplFormLoginBegin');
                $this->AddHook('template_form_registration_end', 'TplFormLoginEnd');
            }

            $this->AddHook('init_action', 'InitAction', __CLASS__);
        }
    }

    protected function _isEnable() {

        $bEnable = Config::Get('plugin.antibot.enable');
        if ($bEnable) {
            $aExcluded = Config::Get('plugin.antibot.off');
            if ($aExcluded) {
                if(!is_array($aExcluded)) {
                    $aExcluded = array($aExcluded);
                }
                $bEnable = !in_array(Router::GetAction() . '/' . Router::GetActionEvent(), $aExcluded);
            }
        }
        return $bEnable;
    }

    /**
     * Init procedure
     */
    protected function _init() {

        if (Config::Get('plugin.antibot.methods.fake')) {
            $aFields = array();
            $sSuffix = Config::Get('plugin.antibot.fake_suffix');
            $aClasses = Config::Get('plugin.antibot.css_classes');
            foreach (Config::Get('plugin.antibot.fake_names') as $sName) {
                if ($sName != 'login') {
                    $sClass = $aClasses[array_rand($aClasses)];
                    $aFields[] = '<p class="' . $sClass . '">'
                        . '<input type="text" name="' . $sName . $sSuffix . '" '
                        . 'id="popup-' . $sName . $sSuffix . '" '
                        . 'placeholder="' . $sName . $sSuffix . '" '
                        . 'class="input-text input-width-full"></p>';
                }
            }

            shuffle($aFields);
            $nRand = rand(round(sizeof($aFields) / 2), sizeof($aFields));
            $a = array('before' => array(), 'after' => array());
            for ($i = 0; $i < $nRand; $i++) {
                if ($i % 2) {
                    $a['before'][] = $aFields[$i];
                } else {
                    $a['after'][] = $aFields[$i];
                }
            }
            $this->Session_Set('plugin.antibot.fake_fields', serialize($a));
            $this->Session_Set('plugin.antibot.fake_suffix', $sSuffix);
        }

        if (Config::Get('plugin.antibot.methods.js')) {
            $aInputSets = array(
                'cnt'   => $nCnt = rand(4, 6),
                'num'   => rand(0, $nCnt),
                'style' => 'x' . substr(uniqid(), 0, 7),
            );
            $this->Session_Set('plugin.antibot.js_login', serialize($aInputSets));
        }
    }

    public function TplHtmlHeadBegin() {

        $this->_init();
    }

    public function TplHtmlHeadEnd() {

        if (Config::Get('plugin.antibot.enable') && Config::Get('plugin.antibot.methods.js')) {
            if ($sSessData = $this->Session_Get('plugin.antibot.js_login')) {
                if (is_array($aInputSets = unserialize($sSessData))) {
                    $aAttributes = array(
                        'position:relative',
                        'top:0',
                        'left:0',
                    );
                    $sStyles = '';
                    for ($i = 0; $i <= $aInputSets['cnt']; $i++) {
                        $aParams = $aAttributes;
                        if ($i != $aInputSets['num']) {
                            // style attributes for fake fields
                            $aParams[] = 'display:none!important;width:1px;height:1px;opacity:0;';
                        }
                        shuffle($aParams);
                        $sStyles .= 'input.' . $aInputSets['style']
                            . Config::Get('plugin.antibot.fake_suffix') . '-' . $i
                            . '{' . implode(';', $aParams) . '} ' . "\n";
                    }

                    $sScript = 'var login_input_real={';
                    $sScript .= 'style:"' . $aInputSets['style'] . '",';
                    $sScript .= 'cnt:"' . $aInputSets['cnt'] . '",';
                    $sScript .= 'suf:"' . Config::Get('plugin.antibot.fake_suffix') . '",';

                    $sFile = Plugin::GetTemplateDir(__CLASS__) . 'assets/css/style.css';
                    if (is_file($sFile) && ($sData = file_get_contents($sFile))) {
                        $sStyles .= ' ' . $sData;
                    }
                    $bEncode = true;
                    if ($bEncode) {
                        $sScript .= 'st_hash:"' . base64_encode($sStyles) . '"}';
                        $sScript = '<script type="text/javascript">' . $sScript . '</script>';
                        $sResult = $sScript;
                    } else {
                        $sScript = '<script type="text/javascript">' . $sScript . '</script>';
                        $sStyles = '<style type="text/css">' . $sStyles . '</style>';
                        $sResult = $sStyles . $sScript;
                    }

                    $sResult .= '<script type="text/javascript" src="'
                        . Plugin::GetTemplateUrl(__CLASS__) . 'assets/js/script.js" /></script>';
                    return $sResult;
                }
            }
        }
        return '';
    }

    public function TplFormLoginBegin() {

        if ($s = $this->Session_Get('plugin.antibot.fake_fields')) {
            if (is_array($a = unserialize($s)) && isset($a['before'])) {
                return implode("\n", $a['before']);
            }
        }
        return '';
    }

    public function TplFormLoginEnd() {

        $s = $this->Session_Get('plugin.antibot.fake_fields');
        if ($s) {
            $a = unserialize($s);
            if (is_array($a) && isset($a['after'])) {
                return implode("\n", $a['after']);
            }
        }
        return '';
    }

    public function InitAction() {

        if ($this->_isEnable() && $this->PluginAntibot_Antibot_NeedCheck()) {
            if (!$this->PluginAntibot_Antibot_BotFree()) {
                pluginAntibotExit(F::GetUserIp(), $this->PluginAntibot_Antibot_GetReason());
                exit;
            }
        }
    }

}

// EOF
