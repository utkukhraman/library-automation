<?php
session_start();
require 'veritabani.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
       
        .dropdown:hover > .dropdown-menu {
            display: block;
            margin-top: 0;
        }
    </style>
    <link rel="icon" href="assets/images/system/icon.png" type="image/png">
</head>
<body>
<div class="main-content">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary panel-navbar">
    <div class="container">
        <a class="navbar-brand" href="panel.php">Yönetim Paneli</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">

             
                 <li class="nav-item"><a class="nav-link panel-nav-link" href="ogrenci-ara.php">Öğrenci Ara</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle panel-nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Emanet Yönetimi
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="kitap-ara-admin.php">Kitap Emanet Et</a></li>
                        <li><a class="dropdown-item" href="emanet-ara.php">Kitap Emanet Al</a></li>
                        <li><a class="dropdown-item" href="emanet-gecmis-ara.php">Geçmiş Emanet Ara</a></li>
                    </ul>

                </li>
                
               
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle panel-nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Kitap Yönetimi
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="kitap-ekle.php">Kitap Ekle</a></li>
                        <li><a class="dropdown-item" href="kitap-ara-duzenle.php">Kitap Düzenle</a></li>
                        <li><a class="dropdown-item" href="kategori-yonetimi.php">Kategori Yönetimi</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle panel-nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Hesap
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php">Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="cikis.php">Çıkış Yap</a></li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
