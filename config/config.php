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

/*
 * Enable plugin's actions
 */
$config['enable'] = true;

/*
 * Fake fields for registration (each field will have the suffix, defined in 'fake_suffix' below)
 */
$config['fake_names'] = array(
    'name', 'first_name', 'last_name', 'phone', 'address', 'telephone', 'city', 'country', 'street', 'job', 'title',
);

/*
 * Cуффикс для ложных полей
 * Он не должен быть пустым!
 * Разрешены только буквы, цифры, знак подчеркивания и минус
 */
$config['fake_suffix'] = '_custom';

/*
 * Использовать javascript
 */
$config['js'] = true;

$config['logs']['file'] = 'antibot.log';
$config['logs']['enable'] = true;

/*
 * CSS-классы ложных полей (должны совпадать с теми, что прописаны в стилях для скрытия)
 */
$config['css_classes'] = array(
    'login-input-var1',
    'login-input-var2',
);

// Разрешены дополнительные логи
$config['logs']['enable'] = true;

// Логгирование успешного прохождения бот-проверки
$config['logs']['pass']['success'] = array(
    'enable' => false,
    'file'   => 'antibot.pass_success.log',
);

// Логгирование неуспешного прохождения бот-проверки
$config['logs']['pass']['fail'] = array(
    'enable' => true,
    'file'   => 'antibot.pass_fail.log',
);

// Логгирование успешной регистрации
$config['logs']['registration']['success'] = array(
    'enable' => false,
    'file'   => 'antibot.registration_success.log',
);

// Логгирование неуспешной регистрации
$config['logs']['registration']['fail'] = array(
    'enable' => false,
    'file'   => 'antibot.registration_fail.log',
);

// Логгирование успешной авторизации
$config['logs']['login']['success'] = array(
    'enable' => false,
    'file'   => 'antibot.login_success.log',
);

// Логгирование неуспешной авторизации
$config['logs']['login']['fail'] = array(
    'enable' => false,
    'file'   => 'antibot.login_fail.log',
);

return $config;

// EOF