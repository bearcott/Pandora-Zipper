<?php
session_start();
//check profile of user
$username = $_SESSION['username'];
$password = $_SESSION['password'];

require_once 'Pandora.php';
use php_pandora_api\Pandora;

$p = new Pandora('android', 'json');

if (!$p->login($username,$password)) {
    die(sprintf("Error: %s\nReq: %s\n Resp: %s", $p->last_error, $p->last_request_data, $p->last_response_data));
}
if (!$response = $p->makeRequest('user.getStationList')) {
    die(sprintf("Error: %s\nReq: %s\n Resp: %s", $p->last_error, $p->last_request_data, $p->last_response_data));
}
echo "<h1>profile of <span style='color: red'>" . $username . "</span></h1>";
echo "<pre>";
print_r($response);
echo "</pre>";
?>
