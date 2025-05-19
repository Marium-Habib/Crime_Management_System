<?php
require_once 'police_config.php';
session_unset();
session_destroy();
header('Location: police_main.php');
exit();
?>