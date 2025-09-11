<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    <title>Teste Cache Buster</title>
</head>
<body>
    <h1>Teste do Sistema de Cache Buster</h1>
    
    <h2>URLs Versionadas:</h2>
    <p><strong>CSS:</strong> assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?></p>
    <p><strong>JS:</strong> assets/js/script.js?v=<?php echo filemtime('assets/js/script.js'); ?></p>
    
    <h2>Status dos Arquivos:</h2>
    <p>CSS existe: <?php echo file_exists('assets/css/style.css') ? 'SIM' : 'NÃO'; ?></p>
    <p>JS existe: <?php echo file_exists('assets/js/script.js') ? 'SIM' : 'NÃO'; ?></p>
    
    <h2>Como funciona:</h2>
    <ul>
        <li>Toda vez que você modificar o CSS, o timestamp muda automaticamente</li>
        <li>Os navegadores dos visitantes baixam a nova versão instantaneamente</li>
        <li>Não há necessidade de intervenção manual</li>
        <li>Funciona para todos os visitantes automaticamente</li>
    </ul>
    
    <p><strong>Exemplo prático:</strong> Modifique qualquer CSS e atualize esta página - verá que a versão mudou!</p>
</body>
</html>