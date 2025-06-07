<?php
function add_folder_to_zip($folder, $zip, $base='') {
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = "$folder/$file";
        $localPath = $base === '' ? $file : "$base/$file";
        if (is_dir($fullPath)) {
            $zip->addEmptyDir($localPath);
            add_folder_to_zip($fullPath, $zip, $localPath);
        } else {
            $zip->addFile($fullPath, $localPath);
        }
    }
}
function create_zip($folders, $zip_path) {
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE)!==TRUE) return false;
    foreach ($folders as $folder => $localname) {
        add_folder_to_zip($folder, $zip, $localname);
    }
    $zip->close();
    return true;
} 