<?php
// filepath: app/includes/upload.php

/**
 * Enregistre une image envoyée par formulaire dans public/uploads/<dossier>/.
 * Mêmes règles que l'ajout de restaurant : facultative, 5 Mo max, JPG / PNG / WebP
 * (type vérifié sur le contenu réel du fichier, pas sur son extension), nom aléatoire.
 *
 * Retourne le chemin public (« /uploads/categories/ab12….png »), ou null si aucun fichier
 * n'a été envoyé ou en cas d'erreur (le message est alors ajouté à $errors).
 */
function save_uploaded_picture(string $field, string $folder, array &$errors): ?string
{
    $file = $_FILES[$field] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // L'image est facultative.
    }

    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Le téléversement de l’image a échoué.';
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'L’image ne doit pas dépasser 5 Mo.';
        return null;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        $errors[] = 'Formats acceptés : JPG, PNG ou WebP.';
        return null;
    }

    $directory = dirname(__DIR__, 2) . '/public/uploads/' . $folder;

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        $errors[] = 'Le dossier de stockage des images est indisponible.';
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];

    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        $errors[] = 'Impossible d’enregistrer l’image.';
        return null;
    }

    return '/uploads/' . $folder . '/' . $filename;
}

/** Supprime une image enregistrée par save_uploaded_picture() (ex. si l'insertion en base échoue). */
function delete_uploaded_picture(?string $publicPath): void
{
    if ($publicPath !== null && str_starts_with($publicPath, '/uploads/')) {
        @unlink(dirname(__DIR__, 2) . '/public' . $publicPath);
    }
}
