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

$config['enable'] = true;

/*
 * Hазвания ложных полей
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
 * Классы ложных полей (должны совпадать с теми, что прописаны в стилях для скрытия)
 */
$config['css_classes'] = array(
    'login-input-var1',
    'login-input-var2',
);

return $config;

// EOF