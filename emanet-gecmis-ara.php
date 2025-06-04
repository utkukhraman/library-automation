<?php

require_once 'veritabani.php'; // $conn (mysqli bağlantısı) burada tanımlanmış olmalı
include 'menu.php';

// --- Sayfalama Ayarları ---
$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;

// --- Değişkenler ---
$search_input = ""; // Kullanıcının girdiği ham arama terimi
$results = [];
$total_items = 0;
$total_pages = 0;

// --- Arama Terimini Belirle ---
if (isset($_POST["search"])) {
    $search_input = trim($_POST["search"]);
    $current_page = 1; // Yeni arama için sayfayı 1'e sıfırla
    $offset = 0;       // Offset'i yeniden hesapla
} elseif (isset($_GET["search"])) { // URL'de arama parametresi varsa (sayfalamada)
    $search_input = trim($_GET["search"]);
    // $current_page zaten $_GET['page'] veya varsayılan olarak ayarlanmıştır
    // $offset zaten $current_page'e göre hesaplanmıştır
}

// --- SQL Koşullarını Hazırla ---
$base_query_from = "FROM emanet e
                    JOIN kitaplar k ON e.kitap_id = k.kitap_id
                    JOIN ogrenciler o ON e.ogrenci_id = o.id";

// Temel WHERE koşulu (sadece iade edilmişler)
$where_conditions = ["e.durum = 1"];
$search_esc = ""; // SQL için güvenli hale getirilmiş arama terimi

if (!empty($search_input)) {
    if (!$conn) {
        die("Veritabanı bağlantısı mevcut değil."); // $conn kontrolü
    }
    $search_esc = mysqli_real_escape_string($conn, $search_input);
    $where_conditions[] = "(k.kitap_adi LIKE '%$search_esc%' 
                        OR o.ogrenci_no LIKE '%$search_esc%' 
                        OR o.adi_soyadi LIKE '%$search_esc%')";
}
$final_where_sql = "WHERE " . implode(" AND ", $where_conditions);

// --- Toplam Öğe Sayısı Sorgusu ---
$sql_count = "SELECT COUNT(e.id) as total_count $base_query_from $final_where_sql";
$count_result = mysqli_query($conn, $sql_count);

if (!$count_result) {
    die("SQL Hatası (Count): " . mysqli_error($conn));
}
$total_items_row = mysqli_fetch_assoc($count_result);
$total_items = (int)$total_items_row['total_count'];

if ($total_items > 0) {
    $total_pages = ceil($total_items / $items_per_page);

    // Geçerli sayfanın sınırlar içinde olduğundan emin ol
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $items_per_page;
    } elseif ($current_page <= 0) {
         $current_page = 1;
         $offset = 0;
    }

    // --- Veri Çekme Sorgusu ---
    $sql_data = "SELECT 
                    e.id,
                    k.kitap_adi,
                    k.barkod_no,
                    o.ogrenci_no,
                    o.adi_soyadi AS ogrenci_adi,
                    e.emanet_baslangic,
                    e.emanet_bitis,
                    e.durum,
                    e.iade_tarihi
                $base_query_from
                $final_where_sql
                ORDER BY e.emanet_baslangic DESC
                LIMIT $items_per_page OFFSET $offset";

    $data_result = mysqli_query($conn, $sql_data);

    if (!$data_result) {
        die("SQL Hatası (Data): " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($data_result)) {
        $results[] = $row;
    }
} else {
    $results = []; // Sonuç bulunamadı
    $total_pages = 0; // Sayfa yok
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Geçmiş Emanetler</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Geçmiş Emanetler</h2>

    <form method="POST" action="" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap adı, öğrenci no veya öğrenci adı..." value="<?= htmlspecialchars($search_input) ?>">
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if (!empty($results)): ?>
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>Kitap Adı</th>
                    <th>Barkod No</th>
                    <th>Öğrenci No</th>
                    <th>Öğrenci Adı</th>
                    <th>Veriliş Tarihi</th>
                    <th>En Geç İade Tarihi</th>
                    <th>İade Edildiği Tarih</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kitap_adi']) ?></td>
                    <td><?= htmlspecialchars($row['barkod_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_adi']) ?></td>
                    <td><?= date("d.m.Y", strtotime($row['emanet_baslangic'])) ?></td>
                    <td><?= $row['emanet_bitis'] ? date("d.m.Y", strtotime($row['emanet_bitis'])) : '-' ?></td>
                    <td><?= $row['iade_tarihi'] ? date("d.m.Y H:i", strtotime($row['iade_tarihi'])) : '-' ?></td>
                    <td>
                        <span class="badge bg-success">
                            İade Edildi
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: // Hiç sonuç yoksa (arama sonucu veya genel) ?>
        <p class="alert alert-warning text-center">
            <?= !empty($search_input) ? "Aradığınız kriterlere uygun geçmiş emanet kaydı bulunamadı." : "Sistemde geçmiş emanet kaydı bulunmamaktadır." ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($results) && $total_pages > 1): ?>
    <nav aria-label="Sayfa navigasyonu" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_input) ?>">Önceki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Önceki</span></li>
            <?php endif; ?>

            <?php
            $num_links_around_current = 2;
            $start_loop = max(1, $current_page - $num_links_around_current);
            $end_loop = min($total_pages, $current_page + $num_links_around_current);

            if ($start_loop > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search_input) . '">1</a></li>';
                if ($start_loop > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_input) ?>"><?= $i ?></a></li>
            <?php endfor;

            if ($end_loop < $total_pages) {
                if ($end_loop < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search_input) . '">' . $total_pages . '</a></li>';
            }
            ?>
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_input) ?>">Sonraki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Sonraki</span></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>