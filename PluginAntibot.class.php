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
    public $aDelegates = array();

    // Объявление переопределений (модули, мапперы и сущности)
    protected $aInherits = array();

    // Активация плагина
    public function Activate() {
        return true;
    }

    // Деактивация плагина
    public function Deactivate() {
    }


    // Инициализация плагина
    public function Init() {

        //$this->Viewer_AppendScript(Plugin::GetTemplateUrl(__CLASS__) . 'assets/js/script.js');
    }
}

// EOF
