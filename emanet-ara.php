<?php

require_once 'veritabani.php';
include 'menu.php';

$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$search_input = "";
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : "";
$results = [];
$total_items = 0;
$total_pages = 0;

if (isset($_POST["return_id"])) {
    $return_id = (int) $_POST["return_id"];

    $kitapSorgu = mysqli_query($conn, "SELECT kitap_id FROM emanet WHERE id = $return_id AND durum = 0 LIMIT 1");

    if ($kitapRow = mysqli_fetch_assoc($kitapSorgu)) {
        $kitap_id = (int) $kitapRow['kitap_id'];
        $bugun = date('Y-m-d H:i:s');

        mysqli_query($conn, "UPDATE emanet SET durum = 1, iade_tarihi = '$bugun' WHERE id = $return_id");

        mysqli_query($conn, "UPDATE kitaplar SET emanet = 0 WHERE kitap_id = $kitap_id");
    }

    if (isset($_POST["last_search"])) {
        $search_input = trim($_POST["last_search"]);
    }
    $current_page = 1;
}

if (empty($search_input)) {
    if (isset($_POST["search"])) {
        $search_input = trim($_POST["search"]);
        $current_page = 1;
    } elseif (isset($_GET["search"])) {
        $search_input = trim($_GET["search"]);
    }
}
$offset = ($current_page - 1) * $items_per_page;

$base_query_from_joins = "FROM emanet e
                          JOIN kitaplar k ON e.kitap_id = k.kitap_id
                          JOIN ogrenciler o ON e.ogrenci_id = o.id";

$where_conditions = ["e.durum = 0"];

if ($filtre === 'geciken') {
    $where_conditions[] = "e.emanet_bitis < CURDATE() AND e.iade_tarihi IS NULL";
} elseif ($filtre === 'yaklasan') {
    $where_conditions[] = "e.emanet_bitis >= CURDATE() AND e.emanet_bitis <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND e.iade_tarihi IS NULL";
}

$search_esc = "";
if (!empty($search_input)) {
    if (!$conn) { die("Veritabanı bağlantısı hatası!"); }
    $search_esc = mysqli_real_escape_string($conn, $search_input);
    $where_conditions[] = "(k.kitap_adi LIKE '%$search_esc%' 
                        OR k.barkod_no LIKE '%$search_esc%' 
                        OR o.adi_soyadi LIKE '%$search_esc%'
                        OR o.ogrenci_no LIKE '%$search_esc%')";
}
$final_where_sql = "WHERE " . implode(" AND ", $where_conditions);

$order_by_sql = "ORDER BY e.emanet_bitis ASC";

$sql_count = "SELECT COUNT(e.id) as total_count $base_query_from_joins $final_where_sql";
$count_result = mysqli_query($conn, $sql_count);

if (!$count_result) {
    die("SQL Hatası (Count): " . mysqli_error($conn));
}
$total_items_row = mysqli_fetch_assoc($count_result);
$total_items = (int)$total_items_row['total_count'];

if ($total_items > 0) {
    $total_pages = ceil($total_items / $items_per_page);

    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $items_per_page;
    } elseif ($current_page <= 0) {
        $current_page = 1;
        $offset = 0;
    }

    $sql_data = "SELECT e.id, k.kitap_adi, k.barkod_no, o.ogrenci_no, o.adi_soyadi AS ogrenci_adi, 
                        DATE_FORMAT(e.emanet_baslangic, '%d.%m.%Y') AS emanet_baslangic_formatted, 
                        DATE_FORMAT(e.emanet_bitis, '%d.%m.%Y') AS emanet_bitis_formatted, 
                        e.durum
                 $base_query_from_joins
                 $final_where_sql
                 $order_by_sql
                 LIMIT $items_per_page OFFSET $offset";

    $data_result = mysqli_query($conn, $sql_data);

    if (!$data_result) {
        die("SQL Hatası (Data): " . mysqli_error($conn));
    }
    while ($row = mysqli_fetch_assoc($data_result)) {
        $results[] = $row;
    }
} else {
    $results = [];
    $total_pages = 0;
}

$query_string_params = [];
if (!empty($search_input)) {
    $query_string_params['search'] = $search_input;
}
if (!empty($filtre)) {
    $query_string_params['filtre'] = $filtre;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Emanet Arama</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Aktif Emanetler</h2>

    <form method="POST" action="?<?= http_build_query(['filtre' => $filtre]) ?>" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap adı, barkod no, öğrenci adı veya öğrenci no..." value="<?= htmlspecialchars($search_input) ?>">
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
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <?php
                    $is_geciken = false;
                    $emanet_bitis_timestamp = strtotime(str_replace('.', '-', $row['emanet_bitis_formatted']));
                    $bugun_timestamp = strtotime(date("Y-m-d"));
                    if ($emanet_bitis_timestamp < $bugun_timestamp) {
                        $is_geciken = true;
                    }
                ?>
                <tr class="<?= $is_geciken ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($row['kitap_adi']) ?></td>
                    <td><?= htmlspecialchars($row['barkod_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_adi']) ?></td>
                    <td><?= $row['emanet_baslangic_formatted'] ?></td>
                    <td><?= $row['emanet_bitis_formatted'] ?: '-' ?></td>
                    <td>
                        <button 
                            class="btn btn-sm btn-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#returnModal" 
                            data-id="<?= $row['id'] ?>" 
                            data-kitap="<?= htmlspecialchars($row['kitap_adi']) ?>" 
                            data-ogrenci="<?= htmlspecialchars($row['ogrenci_adi']) ?>">
                            İade Al
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-warning text-center">
            <?php
            if (!empty($search_input)) {
                echo "Aradığınız kriterlere uygun emanet kaydı bulunamadı.";
            } elseif (!empty($filtre)) {
                echo "Seçili filtreye uygun emanet kaydı bulunamadı.";
            } else {
                echo "Sistemde aktif emanet kaydı bulunmamaktadır.";
            }
            ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($results) && $total_pages > 1): ?>
    <nav aria-label="Sayfa navigasyonu" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?>&amp;<?= http_build_query($query_string_params) ?>">Önceki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Önceki</span></li>
            <?php endif; ?>

            <?php
            $num_links_around_current = 2;
            $start_loop = max(1, $current_page - $num_links_around_current);
            $end_loop = min($total_pages, $current_page + $num_links_around_current);

            if ($start_loop > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&amp;' . http_build_query($query_string_params) . '">1</a></li>';
                if ($start_loop > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&amp;<?= http_build_query($query_string_params) ?>"><?= $i ?></a></li>
            <?php endfor;

            if ($end_loop < $total_pages) {
                if ($end_loop < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&amp;' . http_build_query($query_string_params) . '">' . $total_pages . '</a></li>';
            }
            ?>
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?>&amp;<?= http_build_query($query_string_params) ?>">Sonraki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Sonraki</span></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="returnForm" action="?<?= http_build_query(array_merge($query_string_params, ['page' => $current_page])) ?>">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="returnModalLabel">Kitap İadesi Onayı</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
          </div>
          <div class="modal-body">
            <p>Kitap: <strong id="modalKitapAdi"></strong></p>
            <p>Öğrenci: <strong id="modalOgrenciAdi"></strong></p>
            <p>Bu kitabı iade almak istediğinize emin misiniz?</p>
            <input type="hidden" name="return_id" id="return_id_modal_input" value="">
            <input type="hidden" name="last_search" value="<?= htmlspecialchars($search_input) ?>">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-primary">Evet, İade Al</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    var returnModalEl = document.getElementById('returnModal');
    if (returnModalEl) {
        returnModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var kitap = button.getAttribute('data-kitap');
            var ogrenci = button.getAttribute('data-ogrenci');

            var modalReturnIdInput = returnModalEl.querySelector('#return_id_modal_input');
            var modalKitapAdiEl = returnModalEl.querySelector('#modalKitapAdi');
            var modalOgrenciAdiEl = returnModalEl.querySelector('#modalOgrenciAdi');

            if (modalReturnIdInput) modalReturnIdInput.value = id;
            if (modalKitapAdiEl) modalKitapAdiEl.textContent = kitap;
            if (modalOgrenciAdiEl) modalOgrenciAdiEl.textContent = ogrenci;
        });
    }
</script>
<?php include 'footer.php'; ?>
</body>
</html>