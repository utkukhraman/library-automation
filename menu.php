<?php
session_start();
require 'veritabani.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}

?>

<html>
    <body>
    <div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary panel-navbar">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <div class="container">
        <a class="navbar-brand" href="panel.php">Yönetim Paneli</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-ekle.php">Kitap Ekle</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-duzenle.php">Kitap Yönetimi</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kategori-yonetimi.php">Kategori Yönetimi</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="kitap-ara-admin.php">Kitap Ara</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="ogrenci-ara.php">Öğrenci Ara</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="emanet-sorgula.php">Emanet Sorgula</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link" href="emanet.php">Emanet Al / Ver</a></li>
                <li class="nav-item"><a class="nav-link panel-nav-link panel-nav-link-cikis" href="cikis.php">Çıkış Yap</a></li>
            </ul>
        </div>
    </div>
</nav>
    </body>
</html>