<?php
require_once 'db_config.php';

$_SESSION = array();
session_destroy();
header("Location: main.php");
exit();
?>