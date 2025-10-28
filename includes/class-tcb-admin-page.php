<?php
if (!defined('ABSPATH')) exit;

/**
 * Класс для отображения интерфейса в админке
 */
class TCB_Admin_Page {
    private $cleaner;

    public function __construct($cleaner) {
        $this->cleaner = $cleaner;
        add_action('admin_menu', [$this, 'menu']);
    }

    /** Добавляем пункт в раздел «Инструменты» */
    public function menu() {
        add_management_page(
            'Thumbnail Cleaner & Backup',
            'Thumbnail Cleaner',
            'manage_options',
            'thumbnail-cleaner-backup',
            [$this, 'render']
        );
    }

    /** Отрисовка страницы */
    public function render() {
        if (!current_user_can('manage_options')) return;

        $post_types = get_post_types(['public' => true], 'objects');
        $upload_url = wp_upload_dir()['baseurl'] . '/tcb_backups/';
        ?>

        <div class="wrap">
            <h1>Thumbnail Cleaner & Backup</h1>

            <?php
            // Успешное удаление
            if (isset($_GET['success']) && !empty($_GET['file'])):
                $path = esc_html($_GET['file']);
                $file = basename($path); ?>
                <div class="notice notice-success">
                    <p><strong>Очистка выполнена успешно.</strong></p>
                    <p>Создан архив: <a href="<?php echo esc_url($upload_url . $file); ?>" target="_blank"><?php echo esc_html($file); ?></a></p>
                    <a class="button button-primary" href="<?php echo esc_url($upload_url . $file); ?>">Скачать архив</a>
                </div>

            <?php
            // Предпросмотр списка
            elseif (isset($_GET['preview_list'])):
                $list = json_decode(base64_decode($_GET['preview_list']), true); ?>
                <div class="notice notice-info">
                    <p><strong>Найдено миниатюр: <?php echo count($list); ?></strong></p>
                    <?php if (!empty($list)): ?>
                        <table class="widefat striped">
                            <thead><tr><th>Запись</th><th>Файл</th></tr></thead>
                            <tbody>
                                <?php foreach ($list as $item): ?>
                                    <tr>
                                        <td><?php echo esc_html($item['title']); ?></td>
                                        <td><?php echo esc_html($item['file']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Миниатюры не найдены.</p>
                    <?php endif; ?>
                </div>

            <?php
            // Ошибка
            elseif (isset($_GET['error'])): ?>
                <div class="notice notice-error"><p>Ошибка при создании архива.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="tcb_clean_thumbnails">

                <p><label><strong>Выберите тип записи:</strong></label></p>
                <select name="post_type" required>
                    <?php foreach ($post_types as $slug => $obj): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($obj->labels->singular_name); ?></option>
                    <?php endforeach; ?>
                </select>

                <p>
                    <label>
                        <input type="checkbox" name="preview_only" value="1">
                        Только показать список миниатюр без удаления
                    </label>
                </p>

                <?php submit_button('Начать проверку / очистку'); ?>
            </form>
        </div>
        <?php
    }
}
