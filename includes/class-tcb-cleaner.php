<?php
if (!defined('ABSPATH')) exit;

/**
 * Класс, отвечающий за логику резервного копирования и удаления миниатюр
 */
class TCB_Cleaner {
    private $backup_dir;
    private $zip_helper;

    public function __construct($zip_helper) {
        $this->zip_helper = $zip_helper;
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/tcb_backups';
        if (!file_exists($this->backup_dir)) wp_mkdir_p($this->backup_dir);

        add_action('admin_post_tcb_clean_thumbnails', [$this, 'process']);
    }

    /**
     * Обрабатывает форму: предпросмотр или очистка
     */
    public function process() {
        if (!current_user_can('manage_options')) wp_die('Недостаточно прав.');

        $type = sanitize_text_field($_POST['post_type'] ?? '');
        $preview = isset($_POST['preview_only']);

        if (!$type) wp_die('Тип записи не выбран.');

        $posts = get_posts(['post_type' => $type, 'numberposts' => -1, 'fields' => 'ids']);
        if (empty($posts)) wp_die('Нет записей для обработки.');

        $found_files = [];
        $temp_dir = sys_get_temp_dir() . '/tcb_' . uniqid();
        wp_mkdir_p($temp_dir);

        foreach ($posts as $post_id) {
            $thumb_id = get_post_thumbnail_id($post_id);
            if (!$thumb_id) continue;
            $file = get_attached_file($thumb_id);
            if ($file && file_exists($file)) {
                $title = sanitize_title(get_the_title($post_id));
                $filename = basename($file);
                $found_files[] = ['title' => get_the_title($post_id), 'file' => $filename];

                if (!$preview) {
                    copy($file, "$temp_dir/{$title}__{$filename}");
                    wp_delete_attachment($thumb_id, true);
                }
            }
        }

        // Если предпросмотр — просто показываем список
        if ($preview) {
            $encoded = base64_encode(json_encode($found_files));
            wp_redirect(admin_url("tools.php?page=thumbnail-cleaner-backup&preview_list=" . urlencode($encoded)));
            exit;
        }

        // Архивируем и удаляем
        $backup_file = $this->backup_dir . '/backup_' . $type . '_' . date('Y-m-d_H-i-s') . '.zip';
        $ok = $this->zip_helper->create_zip($temp_dir, $backup_file);
        $this->delete_dir($temp_dir);

        wp_redirect(admin_url('tools.php?page=thumbnail-cleaner-backup&' . ($ok ? 'success=1&file=' . urlencode($backup_file) : 'error=1')));
        exit;
    }

    /** Удаляет временные директории */
    private function delete_dir($dir) {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->delete_dir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
