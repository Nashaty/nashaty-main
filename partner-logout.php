<?php
require_once 'config.php';
unset($_SESSION['partner_id']);
unset($_SESSION['partner_name']);
unset($_SESSION['partner_email']);
session_destroy();
header("Location: index.html");
exit();
?>
