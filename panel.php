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

<!-- Üst Menü -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary panel-navbar">
    <div class="container">
        <a class="navbar-brand" href="panel.php">Yönetim Paneli</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-ekle.php">Kitap Ekle</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-duzenle.php">Kitap Düzenle / Sil</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-ara-admin.php">Kitap Ara</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="ogrenci-ara.php">Öğrenci Ara</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="emanet-sorgula.php">Emanet Sorgula</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="emanet.php">Emanet Al / Ver</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link panel-nav-link-cikis" href="cikis.php">Çıkış Yap</a></li>
            </ul>
        </div>
    </div>
</nav>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
