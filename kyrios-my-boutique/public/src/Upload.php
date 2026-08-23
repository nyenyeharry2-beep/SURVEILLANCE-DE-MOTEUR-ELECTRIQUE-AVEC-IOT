<?php

namespace Kyrios;

class Upload
{
    private static $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private static $maxSize = 5242880; // 5 Mo

    public static function productImage($file, $userId)
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Aucune image reçue.'];
        }

        if ($file['size'] > self::$maxSize) {
            return ['success' => false, 'error' => 'Image trop grande (max 5 Mo).'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::$allowed, true)) {
            return ['success' => false, 'error' => 'Format non autorisé (JPG, PNG, WEBP).'];
        }

        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime];
        $dir = dirname(__DIR__) . '/uploads/products';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'p' . (int) $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Échec upload. Vérifiez les permissions du dossier uploads/.'];
        }

        return ['success' => true, 'url' => '/uploads/products/' . $filename];
    }

    public static function productImageNamed($file, $targetFilename)
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Aucune image reçue.'];
        }

        if ($file['size'] > self::$maxSize) {
            return ['success' => false, 'error' => 'Image trop grande (max 5 Mo).'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::$allowed, true)) {
            return ['success' => false, 'error' => 'Format non autorisé (JPG, PNG, WEBP).'];
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($targetFilename));
        if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $safeName)) {
            $safeName .= '.jpg';
        }

        $dir = dirname(__DIR__) . '/uploads/products';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Échec upload. Vérifiez les permissions du dossier uploads/.'];
        }

        return ['success' => true, 'url' => '/uploads/products/' . $safeName, 'filename' => $safeName];
    }
}
