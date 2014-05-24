<?php
session_start();
$tmpFile = $_SESSION['tmpFile'];
if (!isset($_SESSION['tmpFile']) || filesize($tmpFile) == 0) die('no file.' . $_SESSION['tmpFile']);
$name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['name']);
header("Content-type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . $name . '.zip"'); //name of zipped file
header("Content-Transfer-Encoding: binary");
header('Content-Length: '.filesize($tmpFile));
readfile($tmpFile);
//delete local temp zip file
unlink($tmpFile);
?>
