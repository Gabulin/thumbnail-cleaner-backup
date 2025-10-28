<?php
/**
 * Plugin Name: Thumbnail Cleaner & Backup
 * Plugin URI:  https://github.com/Gabulin/thumbnail-cleaner-backup
 * Description: Удаляет все миниатюры (featured images) выбранного типа записи, создавая ZIP-резервную копию перед удалением. Есть режим предпросмотра.
 * Version:     1.0
 * Author:      Gabulin
 * Author URI:  https://github.com/Gabulin
 * License:     GPLv2 or later
 * Text Domain: thumbnail-cleaner-backup
 */

if (!defined('ABSPATH')) exit;

// Подключаем основные классы
require_once __DIR__ . '/includes/class-tcb-zip-helper.php';
require_once __DIR__ . '/includes/class-tcb-cleaner.php';
require_once __DIR__ . '/includes/class-tcb-admin-page.php';

// Инициализация плагина
add_action('plugins_loaded', function() {
    new TCB_Admin_Page(new TCB_Cleaner(new TCB_Zip_Helper()));
});
