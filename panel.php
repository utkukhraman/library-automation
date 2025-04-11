<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetim Paneli - Kütüphane Otomasyonu - ISU</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stil.css">
</head>
<body>

<?php include 'menu.php'; ?>  


<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
