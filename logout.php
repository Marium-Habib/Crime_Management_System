<?php
session_start();
session_unset();
session_destroy();
header("Location: police_main.html");
exit();
?>