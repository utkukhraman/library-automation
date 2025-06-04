<?php
include 'menu.php';

$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$search_input = "";
$filtre = isset($_GET['filtre']) ? trim($_GET['filtre']) : '';
$results = [];
$total_items = 0;
$total_pages = 0;
$query_execute = false;

if (isset($_POST["search"])) {
    $query_execute = true;
    $search_input = trim($_POST["search"]);
    $current_page = 1;
} elseif (isset($_GET["search"])) {
    $query_execute = true;
    $search_input = trim($_GET["search"]);
} elseif (!empty($filtre)) {
    $query_execute = true;
    if(isset($_GET["search"])){ $search_input = trim($_GET["search"]); } else { $search_input = ""; }
} else {
    $query_execute = true;
    $search_input = "";
    $filtre = "";
}

$offset = ($current_page - 1) * $items_per_page;

$search_sql = "";
if (!empty($search_input)) {
    if (!isset($conn)) {
        die("Veritabanı bağlantısı bulunamadı!");
    }
    $search_sql = mysqli_real_escape_string($conn, $search_input);
}

if ($query_execute) {
    $base_query_from = "FROM kitaplar 
                        LEFT JOIN yazarlar ON kitaplar.yazar_id = yazarlar.yazar_id
                        LEFT JOIN kitap_kategori ON kitaplar.kategori_id = kitap_kategori.kategori_id
                        LEFT JOIN dil ON kitaplar.dil_id = dil.dil_id";
    
    $where_conditions_list = ["kitaplar.silindi_mi = 0"];

    if ($filtre === 'mevcut') {
        $where_conditions_list[] = "kitaplar.emanet = 0";
    }

    if (!empty($search_sql)) {
        $search_specific_condition = "(kitaplar.kitap_adi LIKE '%$search_sql%' 
                                  OR yazarlar.yazar_ad LIKE '%$search_sql%' 
                                  OR kitap_kategori.kategori_adi LIKE '%$search_sql%' 
                                  OR dil.dil_adi LIKE '%$search_sql%')";
        $where_conditions_list[] = $search_specific_condition;
    }

    $final_where_clause = "WHERE " . implode(" AND ", $where_conditions_list);

    $count_query_sql = "SELECT COUNT(*) as total_count $base_query_from $final_where_clause";

    $count_result = mysqli_query($conn, $count_query_sql);
    if (!$count_result) {
        die("SQL Hatası (Count): " . mysqli_error($conn));
    }
    $total_items_row = mysqli_fetch_assoc($count_result);
    $total_items = (int)$total_items_row['total_count'];

    if ($total_items > 0) {
        $total_pages = ceil($total_items / $items_per_page);

        if ($current_page > $total_pages && $total_pages > 0 ) {
            $current_page = $total_pages;
            $offset = ($current_page - 1) * $items_per_page;
        } elseif ( $current_page <= 0 && $total_pages > 0) {
             $current_page = 1;
             $offset = 0;
        }


        $main_query_select = "SELECT kitaplar.kitap_id, kitaplar.kitap_adi, kitaplar.barkod_no, kitaplar.basim_tarihi, 
            kitaplar.sayfa_sayisi, dil.dil_adi, yazarlar.yazar_ad, kitap_kategori.kategori_adi, kitaplar.emanet";
        $main_query_order_limit = "ORDER BY kitaplar.kitap_id ASC LIMIT $items_per_page OFFSET $offset";
        
        $query = "$main_query_select $base_query_from $final_where_clause $main_query_order_limit";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("SQL Hatası (Data): " . mysqli_error($conn));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    } else {
        $results = [];
        $total_pages = 0;
    }
}

$base_link_params = [];
if (!empty($search_input)) {
    $base_link_params['search'] = $search_input;
}

$pagination_params = $base_link_params;
if (!empty($filtre)) {
    $pagination_params['filtre'] = $filtre;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kütüphane Otomasyonu - Kitap Emanet Ver</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Kitap Emanet Verme</h2>

    <form method="POST" action="?filtre=<?= htmlspecialchars($filtre) ?>" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap, yazar veya kategori adı girin..." value="<?= htmlspecialchars($search_input) ?>" />
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>


    <?php if (!empty($results)): ?>
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>Kitap Adı</th>
                    <th>Yazar</th>
                    <th>Barkod No</th>
                    <th>Basım Yılı</th>
                    <th>Sayfa Sayısı</th>
                    <th>Kategori</th>
                    <th>Dil</th>
                    <th>Durum</th>
                    <th>Emanet Ver</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $kitap): ?>
                <tr>
                    <td><?= htmlspecialchars($kitap["kitap_adi"]) ?></td>
                    <td><?= htmlspecialchars($kitap["yazar_ad"] ?? "Bilinmiyor") ?></td>
                    <td><?= htmlspecialchars($kitap["barkod_no"]) ?></td>
                    <td><?= $kitap["basim_tarihi"] ?></td>
                    <td><?= $kitap["sayfa_sayisi"] ?></td>
                    <td><?= htmlspecialchars($kitap["kategori_adi"] ?? "Bilinmiyor") ?></td>
                    <td><?= htmlspecialchars($kitap["dil_adi"] ?? "Bilinmiyor") ?></td>
                    <td>
                        <span class="badge <?= $kitap['emanet'] ? 'bg-danger' : 'bg-success' ?>">
                            <?= $kitap['emanet'] ? 'Emanette' : 'Kütüphanede' ?>
                        </span>
                    </td>
                    <td>
                        <a href="emanet-ver.php?id=<?= $kitap['kitap_id'] ?>" 
                           class="btn btn-sm btn-primary <?= $kitap['emanet'] ? 'disabled' : '' ?>"
                           <?= $kitap['emanet'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                           Emanet Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($query_execute && empty($results)): ?>
        <p class="alert alert-warning text-center">
            <?php
            if (!empty($search_input) && !empty($filtre)) {
                echo "\"" . htmlspecialchars($search_input) . "\" araması için '" . htmlspecialchars(ucfirst($filtre)) . "' filtresiyle eşleşen sonuç bulunamadı.";
            } elseif (!empty($search_input)) {
                echo "\"" . htmlspecialchars($search_input) . "\" için sonuç bulunamadı.";
            } elseif (!empty($filtre)) {
                echo "'" . htmlspecialchars(ucfirst($filtre)) . "' filtresi için sonuç bulunamadı.";
            } else {
                echo "Listelenecek kitap bulunmamaktadır.";
            }
            ?>
        </p>
    <?php endif; ?>


    <?php if ($query_execute && !empty($results) && $total_pages > 1): ?>
    <nav aria-label="Sayfa navigasyonu" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>&amp;<?= http_build_query($pagination_params) ?>" aria-label="Önceki">
                        <span aria-hidden="true">&laquo;</span>
                        <span class="visually-hidden">Önceki</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            <?php endif; ?>

            <?php
            $num_links_around_current = 2; 
            $start_page_loop = max(1, $current_page - $num_links_around_current);
            $end_page_loop = min($total_pages, $current_page + $num_links_around_current);

            if ($start_page_loop > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&amp;' . http_build_query($pagination_params) . '">1</a></li>';
                if ($start_page_loop > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $start_page_loop; $i <= $end_page_loop; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&amp;<?= http_build_query($pagination_params) ?>"><?= $i ?></a>
                </li>
            <?php endfor;

            if ($end_page_loop < $total_pages) {
                if ($end_page_loop < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&amp;' . http_build_query($pagination_params) . '">' . $total_pages . '</a></li>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>&amp;<?= http_build_query($pagination_params) ?>" aria-label="Sonraki">
                        <span aria-hidden="true">&raquo;</span>
                        <span class="visually-hidden">Sonraki</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>