<?php
require_once 'config.php';
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
session_destroy();
header("Location: index.html");
exit();
?>