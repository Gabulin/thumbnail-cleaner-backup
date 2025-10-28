<?php
if (!defined('ABSPATH')) exit;

/**
 * Класс для создания ZIP-архивов с резервными копиями
 */
class TCB_Zip_Helper {

    /**
     * Создаёт архив. Если ZipArchive недоступен — использует PclZip.
     */
    public function create_zip($source, $destination) {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($source) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                return file_exists($destination);
            }
        }

        // Fallback через PclZip
        if (!class_exists('PclZip')) {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        }

        $archive = new PclZip($destination);
        $archive->create($source, PCLZIP_OPT_REMOVE_PATH, dirname($source));
        return file_exists($destination);
    }
}
