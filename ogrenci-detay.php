<?php
include 'veritabani.php';
session_start();

$ogrenci_no = $_GET['ogrenci_no'] ?? '';

if (!$ogrenci_no) {
    die("Geçersiz öğrenci no.");
}


$ogrenci_no_esc = mysqli_real_escape_string($conn, $ogrenci_no);
$query = "SELECT * FROM ogrenciler WHERE ogrenci_no = '$ogrenci_no_esc' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Öğrenci bulunamadı.");
}

$ogrenci = mysqli_fetch_assoc($result);

// ad soyad ayır
if (!empty($ogrenci['adi_soyadi'])) {
    $isim_parcala = explode(' ', $ogrenci['adi_soyadi'], 2);
    $ogrenci['ad'] = $isim_parcala[0] ?? '';
    $ogrenci['soyad'] = $isim_parcala[1] ?? '';
}

// tarih biçim
$dogum_tarihi_formatted = 'Belirtilmemiş';
if (!empty($ogrenci['dogum_tarihi']) && $ogrenci['dogum_tarihi'] != '0000-00-00') {
    $dt = DateTime::createFromFormat('Y-m-d', $ogrenci['dogum_tarihi']);
    if ($dt) {
        $dogum_tarihi_formatted = $dt->format('d/m/Y');
    }
}

$engelli_mi = $ogrenci['engelli'] ?? 0;
$engelleyen_id = $ogrenci['engelleyen_id'] ?? null;
$engelleyen_adi = '';

if ($engelleyen_id) {
    $engelleyen_id = (int)$engelleyen_id;
    $q = "SELECT ad, soyad FROM kullanicilar WHERE id = $engelleyen_id LIMIT 1";
    $res = mysqli_query($conn, $q);
    if ($res && mysqli_num_rows($res) > 0) {
        $engelleyen = mysqli_fetch_assoc($res);
        $engelleyen_adi = $engelleyen['ad'] . ' ' . $engelleyen['soyad'];
    }
}

$kitaplar_query = "
    SELECT k.kitap_adi, e.emanet_baslangic, e.emanet_bitis, e.iade_tarihi, e.durum
    FROM emanet e
    INNER JOIN kitaplar k ON e.kitap_id = k.kitap_id
    WHERE e.ogrenci_id = " . (int)$ogrenci['id'] . "
    ORDER BY e.emanet_baslangic DESC
";
$kitaplar_result = mysqli_query($conn, $kitaplar_query);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Öğrenci Detay - <?= htmlspecialchars(trim($ogrenci['ad'] . ' ' . $ogrenci['soyad'])) ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container mt-5">
    
    <h2>Öğrenci Detayları</h2>

    <div class="card mb-4">
        <div class="card-header"><strong>Temel Bilgiler</strong></div>
        <div class="card-body">
            <p><strong>Öğrenci No:</strong> <?= htmlspecialchars($ogrenci['ogrenci_no']) ?></p>
            <p><strong>Ad:</strong> <?= htmlspecialchars($ogrenci['ad']) ?></p>
            <p><strong>Soyad:</strong> <?= htmlspecialchars($ogrenci['soyad']) ?></p>
            <p><strong>Doğum Tarihi:</strong> <?= htmlspecialchars($dogum_tarihi_formatted) ?></p>
            <p><strong>Yasaklı mı?:</strong> <?= $engelli_mi ? '<span class="badge bg-danger">Evet</span>' : '<span class="badge bg-success">Hayır</span>' ?></p>
            <?php if ($engelli_mi && $engelleyen_adi): ?>
                <p><strong>Engelleyen:</strong> <?= htmlspecialchars($engelleyen_adi) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <h4>Alınan Kitaplar</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Kitap Adı</th>
                <th>Emanet Başlangıç</th>
                <th>Emanet Bitiş</th>
                <th>İade Tarihi</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($kitaplar_result && mysqli_num_rows($kitaplar_result) > 0):
                while ($kitap = mysqli_fetch_assoc($kitaplar_result)):
            ?>
            <tr>
                <td><?= htmlspecialchars($kitap['kitap_adi']) ?></td>
                <td><?= htmlspecialchars($kitap['emanet_baslangic']) ?></td>
                
                <td><?= htmlspecialchars($kitap['emanet_bitis'] ?? '-') ?></td>
                <td><?= htmlspecialchars($kitap['iade_tarihi'] ?? '-') ?></td>

                <td>
                    <?php
                    if ($kitap['durum'] == 0) {
                        echo '<span class="badge bg-warning text-dark">Emanette</span>';
                    } else {
                        echo '<span class="badge bg-success">Teslim Edildi</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="4" class="text-center">Öğrenci henüz kitap kiralamamış.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="ogrenci-ara.php" class="btn btn-secondary mt-3">Geri Dön</a>
</div>
<br>
<?php include 'footer.php'; ?>
</body>
</html>
