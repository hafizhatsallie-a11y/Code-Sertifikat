<?php
session_start();
session_destroy();
header("Location: login_admin_sertifikat.php");
exit;
?>