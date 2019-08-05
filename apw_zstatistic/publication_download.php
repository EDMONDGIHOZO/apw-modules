<?php

define('DRUPAL_ROOT', '/extdisks/devworld/nerdlab/php-labs/apw/');
include_once(DRUPAL_ROOT . '/includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$filename = htmlspecialchars(urldecode($_GET['file']));
$path = $_SERVER['DOCUMENT_ROOT'] . "/"; //path of this file
$fullPath = $path . $filename; //path to download file 
$file = htmlspecialchars(urldecode(basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING'])));

$filetypes = array("rar", "zip", "pdf", "doc", "ocx");

if (!in_array(substr($filename, -3), $filetypes)) {
    echo "Invalid download type.";
    exit;
}

if ($fd = fopen($fullPath, "r")) {
    //add download stat
    _log_publication_download($filename, $file);

    //outputs the file
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);

    header("Content-type: application/octet-stream");
    header('Content-Disposition: filename="' . $path_parts["basename"] . '"');
    header("Content-length: $fsize");
    header("Cache-control: private"); //use this to open files directly
    while (!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }
} else {
    drupal_goto($path = 'node/1351');
    drupal_exit();
}

fclose($fd);
exit;
?>
