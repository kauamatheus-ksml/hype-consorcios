<?php
/**
 * Cache Buster - Sistema de Versionamento Automático
 * Resolve o problema de cache para visitantes automaticamente
 */

function getCacheVersion($filePath) {
    if (file_exists($filePath)) {
        return filemtime($filePath);
    }
    return time(); // Fallback caso o arquivo não exista
}

function getVersionedAsset($assetPath) {
    $version = getCacheVersion($assetPath);
    return $assetPath . '?v=' . $version;
}

// Funções para facilitar o uso nos templates
function cssVersion($cssFile = 'assets/css/style.css') {
    return getVersionedAsset($cssFile);
}

function jsVersion($jsFile = 'assets/js/script.js') {
    return getVersionedAsset($jsFile);
}
?>