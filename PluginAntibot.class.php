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

class PluginAntibot extends Plugin {

    // Объявление делегирований (нужны для того, чтобы назначить свои экшны и шаблоны)
    public $aDelegates = array(
        'module' => array(
            'ModuleAntibot',
        ),
    );

    // Объявление переопределений (модули, мапперы и сущности)
    protected $aInherits = array(
        'action' => array(
            'ActionRegistration',
            'ActionLogin',
        ),
    );

    // Активация плагина
    public function Activate() {
        return true;
    }

    // Деактивация плагина
    public function Deactivate() {
        return true;
    }


    // Инициализация плагина
    public function Init() {

        if (Config::Get('plugin.antibot.enable') && (Config::Get('plugin.antibot.methods.js') || Config::Get('plugin.antibot.methods.fake'))) {
            $sFile = Plugin::GetTemplateDir(__CLASS__) . 'assets/css/style.css';
            $this->Viewer_AppendStyle($sFile);
        }
    }

}

// EOF
