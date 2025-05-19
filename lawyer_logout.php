<?php
session_start();
session_destroy();
header("Location: lawyer_main.html");
exit();
?>