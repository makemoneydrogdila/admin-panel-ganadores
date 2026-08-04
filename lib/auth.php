<?php
header('Access-Control-Allow-Origin: *');
setlocale(LC_TIME, "spanish");
define('TABLA_PUTO', 'datos');
date_default_timezone_set('America/Bogota');
require_once('db.php');
require_once('core.php');
$ip = $_SERVER['REMOTE_ADDR'];

$con = Database::getConnection();
$stmt = $con->prepare("SELECT 1 FROM ban_ip WHERE ip = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$ban_result = $stmt->get_result();
if ($ban_result->num_rows > 0) {
	error_log("Acceso denegado para la IP: $ip");
	http_response_code(403);
	die("Acceso denegado.");
}
global $owner, $token;

$owner = '';
$token = '';
if (isset($_GET['tok'])) {
	$token = $_GET['tok'];
} else if (isset($_POST['tok'])) {
	$token = $_POST['tok'];
} else {
	http_response_code(404);
	die();
}
$result = mysqli_query($con, "SELECT * FROM users WHERE cod_unico =  '" . $token . "'");
if ($result && ($datos = mysqli_fetch_assoc($result))) {
	$owner = $datos['id'];
}
if (!$owner) {
	http_response_code(404);
	die();
}
