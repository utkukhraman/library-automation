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

if (!empty($ogrenci['adi_soyadi'])) {
    $isim_parcala = explode(' ', $ogrenci['adi_soyadi'], 2);
    $ogrenci['ad'] = $isim_parcala[0] ?? '';
    $ogrenci['soyad'] = $isim_parcala[1] ?? '';
}

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
    $q_engelleyen = "SELECT ad, soyad FROM kullanicilar WHERE id = $engelleyen_id LIMIT 1";
    $res_engelleyen = mysqli_query($conn, $q_engelleyen);
    if ($res_engelleyen && mysqli_num_rows($res_engelleyen) > 0) {
        $engelleyen = mysqli_fetch_assoc($res_engelleyen);
        $engelleyen_adi = $engelleyen['ad'] . ' ' . $engelleyen['soyad'];
    }
}

$items_per_page_kitaplar = 10;
$current_page_kitaplar = isset($_GET['sayfa_kitaplar']) && is_numeric($_GET['sayfa_kitaplar']) ? (int)$_GET['sayfa_kitaplar'] : 1;
if ($current_page_kitaplar < 1) {
    $current_page_kitaplar = 1;
}
$offset_kitaplar = ($current_page_kitaplar - 1) * $items_per_page_kitaplar;

$ogrenci_id_int = (int)$ogrenci['id'];

$count_kitaplar_query = "SELECT COUNT(*) as total FROM emanet WHERE ogrenci_id = $ogrenci_id_int";
$count_kitaplar_result = mysqli_query($conn, $count_kitaplar_query);
$total_kitaplar = 0;
if ($count_kitaplar_result) {
    $total_kitaplar_row = mysqli_fetch_assoc($count_kitaplar_result);
    $total_kitaplar = (int)$total_kitaplar_row['total'];
}
$total_pages_kitaplar = 0;
if ($total_kitaplar > 0) {
    $total_pages_kitaplar = ceil($total_kitaplar / $items_per_page_kitaplar);
}

if ($current_page_kitaplar > $total_pages_kitaplar && $total_pages_kitaplar > 0) {
    $current_page_kitaplar = $total_pages_kitaplar;
    $offset_kitaplar = ($current_page_kitaplar - 1) * $items_per_page_kitaplar;
}


$kitaplar_query = "
    SELECT k.kitap_adi, 
           DATE_FORMAT(e.emanet_baslangic, '%d.%m.%Y') AS emanet_baslangic_formatted, 
           DATE_FORMAT(e.emanet_bitis, '%d.%m.%Y') AS emanet_bitis_formatted, 
           IF(e.iade_tarihi IS NOT NULL, DATE_FORMAT(e.iade_tarihi, '%d.%m.%Y %H:%i'), NULL) AS iade_tarihi_formatted, 
           e.durum
    FROM emanet e
    INNER JOIN kitaplar k ON e.kitap_id = k.kitap_id
    WHERE e.ogrenci_id = $ogrenci_id_int
    ORDER BY e.emanet_baslangic DESC
    LIMIT $items_per_page_kitaplar OFFSET $offset_kitaplar
";
$kitaplar_result = mysqli_query($conn, $kitaplar_query);

$alinan_kitaplar_listesi = [];
if ($kitaplar_result) {
    while ($kitap_row = mysqli_fetch_assoc($kitaplar_result)) {
        $alinan_kitaplar_listesi[] = $kitap_row;
    }
}

$base_url_kitaplar_pagination = "?ogrenci_no=" . urlencode($ogrenci_no);

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
            <?php if ($engelli_mi && !empty($engelleyen_adi)): ?>
                <p><strong>Engelleyen:</strong> <?= htmlspecialchars($engelleyen_adi) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <h4>Alınan Kitaplar (Toplam: <?= $total_kitaplar ?>)</h4>
    <?php if (!empty($alinan_kitaplar_listesi)): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Kitap Adı</th>
                    <th>Emanet Başlangıç</th>
                    <th>Emanet Bitiş</th>
                    <th>İade Tarihi</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alinan_kitaplar_listesi as $kitap): ?>
                <tr>
                    <td><?= htmlspecialchars($kitap['kitap_adi']) ?></td>
                    <td><?= htmlspecialchars($kitap['emanet_baslangic_formatted']) ?></td>
                    <td><?= htmlspecialchars($kitap['emanet_bitis_formatted'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($kitap['iade_tarihi_formatted'] ?? '-') ?></td>
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
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages_kitaplar > 1): ?>
        <nav aria-label="Alınan Kitaplar Sayfalaması">
            <ul class="pagination justify-content-center">
                <?php if ($current_page_kitaplar > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= $base_url_kitaplar_pagination ?>&sayfa_kitaplar=<?= $current_page_kitaplar - 1 ?>">Önceki</a></li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Önceki</span></li>
                <?php endif; ?>

                <?php
                $num_links_around_current_kitaplar = 2;
                $start_loop_kitaplar = max(1, $current_page_kitaplar - $num_links_around_current_kitaplar);
                $end_loop_kitaplar = min($total_pages_kitaplar, $current_page_kitaplar + $num_links_around_current_kitaplar);

                if ($start_loop_kitaplar > 1) {
                    echo '<li class="page-item"><a class="page-link" href="' . $base_url_kitaplar_pagination . '&sayfa_kitaplar=1">1</a></li>';
                    if ($start_loop_kitaplar > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $start_loop_kitaplar; $i <= $end_loop_kitaplar; $i++): ?>
                    <li class="page-item <?= ($i == $current_page_kitaplar) ? 'active' : '' ?>"><a class="page-link" href="<?= $base_url_kitaplar_pagination ?>&sayfa_kitaplar=<?= $i ?>"><?= $i ?></a></li>
                <?php endfor;

                if ($end_loop_kitaplar < $total_pages_kitaplar) {
                    if ($end_loop_kitaplar < $total_pages_kitaplar - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="' . $base_url_kitaplar_pagination . '&sayfa_kitaplar=' . $total_pages_kitaplar . '">' . $total_pages_kitaplar . '</a></li>';
                }
                ?>

                <?php if ($current_page_kitaplar < $total_pages_kitaplar): ?>
                    <li class="page-item"><a class="page-link" href="<?= $base_url_kitaplar_pagination ?>&sayfa_kitaplar=<?= $current_page_kitaplar + 1 ?>">Sonraki</a></li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Sonraki</span></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    <?php else: ?>
        <p class="alert alert-info text-center">Öğrenci henüz kitap almamış.</p>
    <?php endif; ?>

    <a href="ogrenci-ara.php" class="btn btn-secondary mt-3">Geri Dön</a>
</div>
<br>
<?php include 'footer.php'; ?>
</body>
</html>