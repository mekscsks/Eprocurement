<?php
session_start();
include 'config/localdb.php';
include 'functions/presence.php';

if (!empty($_SESSION['auth_user']['account_id'])) {
    markUserPresence($con, (int)$_SESSION['auth_user']['account_id'], false);
}

session_destroy();
header("Location: ../index.php");
exit;