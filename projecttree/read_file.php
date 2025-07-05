<?php
if (isset($_POST['file'])) {
    $file = $_POST['file'];
    if (strpos($file, '..') !== false) {
        http_response_code(400);
        exit("Invalid path");
    }

    $fullpath = __DIR__ . '/' . ltrim($file, './');
    if (file_exists($fullpath)) {
        echo file_get_contents($fullpath);
    } else {
        http_response_code(404);
        echo "Not found";
    }
}
?>
