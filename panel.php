<?php

session_start(); // menu.php'den kaldırıldığı için burada kalmalı
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}
include("veritabani.php"); // $conn değişkeni burada tanımlanmalı

// --- TEMEL İSTATİSTİKLER ---
$kitapSayisiSorgu = mysqli_query($conn, "SELECT kitap_id FROM kitaplar WHERE silindi_mi = 0");
if (!$kitapSayisiSorgu) { die("Sorgu hatası (Toplam Kitap): " . mysqli_error($conn)); }
$kitapSayisi = mysqli_num_rows($kitapSayisiSorgu);

$ogrenciSayisiSorgu = mysqli_query($conn, "SELECT id FROM ogrenciler");
if (!$ogrenciSayisiSorgu) { die("Sorgu hatası (Toplam Öğrenci): " . mysqli_error($conn)); }
$ogrenciSayisi = mysqli_num_rows($ogrenciSayisiSorgu);

$aktifEmanetSayisiSorgu = mysqli_query($conn, "SELECT id FROM emanet WHERE iade_tarihi IS NULL");
if (!$aktifEmanetSayisiSorgu) { die("Sorgu hatası (Aktif Emanet): " . mysqli_error($conn)); }
$aktifEmanetSayisi = mysqli_num_rows($aktifEmanetSayisiSorgu);

$gecikenEmanetSayisiSorgu = mysqli_query($conn, "SELECT id FROM emanet WHERE emanet_bitis < NOW() AND iade_tarihi IS NULL");
if (!$gecikenEmanetSayisiSorgu) { die("Sorgu hatası (Geciken Emanet): " . mysqli_error($conn)); }
$gecikenEmanetSayisi = mysqli_num_rows($gecikenEmanetSayisiSorgu);

$mevcutKitapSayisiSorgu = mysqli_query($conn, "SELECT kitap_id FROM kitaplar WHERE silindi_mi = 0 AND kitap_id NOT IN (SELECT DISTINCT kitap_id FROM emanet WHERE iade_tarihi IS NULL)");
if (!$mevcutKitapSayisiSorgu) { die("Sorgu hatası (Mevcut Kitap): " . mysqli_error($conn)); }
$mevcutKitapSayisi = mysqli_num_rows($mevcutKitapSayisiSorgu);

$tarih_siniri_yaklasan = date('Y-m-d H:i:s', strtotime('+3 days'));
$yaklasanEmanetSayisiSorgu = mysqli_query($conn, "SELECT id FROM emanet WHERE emanet_bitis <= '$tarih_siniri_yaklasan' AND emanet_bitis >= NOW() AND iade_tarihi IS NULL");
if (!$yaklasanEmanetSayisiSorgu) { die("Sorgu hatası (Yaklaşan Emanet Sayısı): " . mysqli_error($conn)); }
$yaklasanEmanetSayisi = mysqli_num_rows($yaklasanEmanetSayisiSorgu);


// son 5 kitap
$query_son_kitaplar = "SELECT k.kitap_adi, y.yazar_ad
                       FROM kitaplar k
                       INNER JOIN yazarlar y ON k.yazar_id = y.yazar_id
                       WHERE k.silindi_mi = 0
                       ORDER BY k.kitap_id DESC
                       LIMIT 5";
$result_son_kitaplar = mysqli_query($conn, $query_son_kitaplar);
if (!$result_son_kitaplar) { die("Sorgu hatası (Son Kitaplar): " . mysqli_error($conn)); }

// en çok 5 emanet kitağ
$query_top_kitaplar = "SELECT k.kitap_adi, COUNT(e.kitap_id) as odunc_sayisi
                       FROM emanet e
                       INNER JOIN kitaplar k ON e.kitap_id = k.kitap_id
                       GROUP BY e.kitap_id, k.kitap_adi
                       ORDER BY odunc_sayisi DESC
                       LIMIT 5";
$result_top_kitaplar = mysqli_query($conn, $query_top_kitaplar);
if (!$result_top_kitaplar) { die("Sorgu hatası (Top Kitaplar): " . mysqli_error($conn)); }

// en fazla kitap alan 5 öğrenci
$query_top_ogrenciler = "SELECT o.adi_soyadi, COUNT(e.ogrenci_id) as kiralama_sayisi
                         FROM emanet e
                         INNER JOIN ogrenciler o ON e.ogrenci_id = o.id
                         GROUP BY e.ogrenci_id, o.adi_soyadi
                         ORDER BY kiralama_sayisi DESC
                         LIMIT 5";
$result_top_ogrenciler = mysqli_query($conn, $query_top_ogrenciler);
if (!$result_top_ogrenciler) { die("Sorgu hatası (Top Öğrenciler): " . mysqli_error($conn)); }

// yaklaşan teslimler
$query_yaklasanlar_detay = "SELECT k.kitap_adi, o.adi_soyadi, e.emanet_bitis
                           FROM emanet e
                           INNER JOIN kitaplar k ON e.kitap_id = k.kitap_id
                           INNER JOIN ogrenciler o ON e.ogrenci_id = o.id
                           WHERE e.emanet_bitis <= '$tarih_siniri_yaklasan' AND e.emanet_bitis >= NOW() AND e.iade_tarihi IS NULL
                           ORDER BY e.emanet_bitis ASC
                           LIMIT 5"; // LIMIT 5 EKLENDİ
$result_yaklasanlar_detay = mysqli_query($conn, $query_yaklasanlar_detay);
if (!$result_yaklasanlar_detay) { die("Sorgu hatası (Yaklaşanlar Detay): " . mysqli_error($conn)); }

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetim Paneli - Kütüphane Otomasyonu</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stil.css">
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">📊 Yönetim Paneli</h2>

    <div class="row text-center">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="kitap-ara-admin.php" style="text-decoration:none;">
                <div class="card text-bg-primary h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">📚 Toplam Kitap</h5>
                        <p class="card-text fs-3 mb-0"><?= $kitapSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="kitap-ara-admin.php?filtre=mevcut" style="text-decoration:none;">
                <div class="card text-bg-info h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">📖 Mevcut Kitap</h5>
                        <p class="card-text fs-3 mb-0"><?= $mevcutKitapSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="ogrenci-ara.php" style="text-decoration:none;">
                <div class="card text-bg-success h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">👤 Toplam Öğrenci</h5>
                        <p class="card-text fs-3 mb-0"><?= $ogrenciSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="emanet-ara.php" style="text-decoration:none;">
                <div class="card text-bg-warning h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">🔄 Aktif Emanet</h5>
                        <p class="card-text fs-3 mb-0"><?= $aktifEmanetSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="emanet-ara.php?filtre=geciken" style="text-decoration:none;">
                <div class="card text-bg-danger h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">⏰ Geciken Teslim</h5>
                        <p class="card-text fs-3 mb-0"><?= $gecikenEmanetSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="emanet-ara.php?filtre=yaklasan" style="text-decoration:none;">
                <div class="card text-bg-secondary h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">⏳ Yaklaşan Teslim</h5>
                        <p class="card-text fs-3 mb-0"><?= $yaklasanEmanetSayisi ?></p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <hr class="my-4">

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    🚀 Son Eklenen Kitaplar (Top 5)
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    if (mysqli_num_rows($result_son_kitaplar) > 0) {
                        while ($kitap = mysqli_fetch_assoc($result_son_kitaplar)) {
                            echo "<li class='list-group-item'>" . htmlspecialchars($kitap['kitap_adi']) . " - <small class='text-muted'>" . htmlspecialchars($kitap['yazar_ad']) . "</small></li>";
                        }
                    } else {
                        echo "<li class='list-group-item'>Henüz kitap eklenmemiş.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    🌟 En Çok Ödünç Alınanlar (Top 5)
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    if (mysqli_num_rows($result_top_kitaplar) > 0) {
                        while ($kitap = mysqli_fetch_assoc($result_top_kitaplar)) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>" .
                                 htmlspecialchars($kitap['kitap_adi']) .
                                 "<span class='badge bg-primary rounded-pill'>" . $kitap['odunc_sayisi'] . " kez</span>" .
                                 "</li>";
                        }
                    } else {
                        echo "<li class='list-group-item'>Veri bulunamadı.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-0 mt-md-0">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    🧑‍🎓 En Aktif Öğrenciler (Top 5)
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    if (mysqli_num_rows($result_top_ogrenciler) > 0) {
                        while ($ogrenci = mysqli_fetch_assoc($result_top_ogrenciler)) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>" .
                                 htmlspecialchars($ogrenci['adi_soyadi']) .
                                 "<span class='badge bg-success rounded-pill'>" . $ogrenci['kiralama_sayisi'] . " kitap</span>" .
                                 "</li>";
                        }
                    } else {
                        echo "<li class='list-group-item'>Veri bulunamadı.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    🔔 Yaklaşan Teslimler (Öncelikli 5) </div>
                <ul class="list-group list-group-flush">
                    <?php
                    if (mysqli_num_rows($result_yaklasanlar_detay) > 0) {
                        while ($emanet = mysqli_fetch_assoc($result_yaklasanlar_detay)) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>" .
                                 "<div>" .
                                 htmlspecialchars($emanet['kitap_adi']) .
                                 "<small class='text-muted d-block'>" . htmlspecialchars($emanet['adi_soyadi']) . "</small>" .
                                 "</div>" .
                                 "<span class='badge bg-warning text-dark rounded-pill'>" . date('d.m.Y', strtotime($emanet['emanet_bitis'])) . "</span>" .
                                 "</li>";
                        }
                    } else {
                        echo "<li class='list-group-item'>Yaklaşan teslim bulunmuyor.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<br>
<?php
include("footer.php"); 
?>

<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php

if (isset($conn)) {
    mysqli_close($conn);
}
?>