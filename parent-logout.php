<?php
// parent-logout.php
require_once 'config.php';
unset($_SESSION['parent_id']);
unset($_SESSION['parent_name']);
unset($_SESSION['parent_email']);
session_destroy();
header("Location: index.html");
exit();
?>