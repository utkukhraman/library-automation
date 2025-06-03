<?php

require_once 'veritabani.php';
include 'menu.php';

$search = "";
$results = [];

$whereConditions = ["e.durum = 0"]; 

if (isset($_GET['filtre']) && $_GET['filtre'] === 'geciken') {
    $whereConditions[] = "e.emanet_bitis < NOW() AND e.iade_tarihi IS NULL";
}

if (isset($_GET['filtre']) && $_GET['filtre'] === 'yaklasan') { 
    $whereConditions[] = "e.emanet_bitis >= NOW() AND e.emanet_bitis <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND e.iade_tarihi IS NULL";
}

// İade 
if (isset($_POST["return_id"])) {
    $return_id = (int) $_POST["return_id"];

    $kitapSorgu = mysqli_query($conn, "SELECT kitap_id FROM emanet WHERE id = $return_id LIMIT 1");

    if ($kitapRow = mysqli_fetch_assoc($kitapSorgu)) {
        $kitap_id = (int) $kitapRow['kitap_id'];
        $bugun = date('Y-m-d H:i:s');

        mysqli_query($conn, "UPDATE emanet SET durum = 1, iade_tarihi = '$bugun' WHERE id = $return_id");

        mysqli_query($conn, "UPDATE kitaplar SET emanet = 0 WHERE kitap_id = $kitap_id");
    }

    $search = isset($_POST["last_search"]) ? trim($_POST["last_search"]) : "";
}

if (isset($_POST["search"]) || (isset($_POST["return_id"]) && !empty($search))) {
    if (isset($_POST["search"])) {
        $search = trim($_POST["search"]);
    }
    $search_esc = mysqli_real_escape_string($conn, $search);

    $whereConditions[] = "(k.kitap_adi LIKE '%$search_esc%' 
            OR k.barkod_no LIKE '%$search_esc%' 
            OR o.adi_soyadi LIKE '%$search_esc%'
            OR o.ogrenci_no LIKE '%$search_esc%')";
}

// Sorgu 
$whereSql = implode(" AND ", $whereConditions);

$query = "
    SELECT 
        e.id,
        k.kitap_adi,
        k.barkod_no,
        o.ogrenci_no,
        o.adi_soyadi AS ogrenci_adi,
        e.emanet_baslangic,
        e.emanet_bitis,
        e.durum
    FROM emanet e
    JOIN kitaplar k ON e.kitap_id = k.kitap_id
    JOIN ogrenciler o ON e.ogrenci_id = o.id
    WHERE $whereSql
    ORDER BY e.emanet_baslangic DESC
    LIMIT 100
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL Hatası: " . mysqli_error($conn));
}

$results = [];
while ($row = mysqli_fetch_assoc($result)) {
    $results[] = $row;
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
    <h2 class="text-center">Emanet Arama</h2>

    <form method="POST" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap adı, barkod no, öğrenci adı veya öğrenci no..." value="<?= htmlspecialchars($search) ?>">
        <input type="hidden" name="last_search" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if (!empty($results)): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Kitap Adı</th>
                    <th>Barkod No</th>
                    <th>Öğrenci No</th>
                    <th>Öğrenci</th>
                    <th>Veriliş Tarihi</th>
                    <th>En Geç İade Tarihi</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kitap_adi']) ?></td>
                    <td><?= htmlspecialchars($row['barkod_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_no']) ?></td>
                    <td><?= htmlspecialchars($row['ogrenci_adi']) ?></td>
                    <td><?= htmlspecialchars($row['emanet_baslangic']) ?></td>
                    <td><?= $row['emanet_bitis'] ? htmlspecialchars($row['emanet_bitis']) : '-' ?></td>
                    <td>
                        <span class="badge <?= $row['durum'] == 0 ? 'bg-danger' : 'bg-success' ?>">
                            <?= $row['durum'] == 0 ? 'Emanette' : 'İade Edildi' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['durum'] == 0): ?>
                            <!-- Modal butonu -->
                            <button 
                                class="btn btn-sm btn-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#returnModal" 
                                data-id="<?= $row['id'] ?>" 
                                data-kitap="<?= htmlspecialchars($row['kitap_adi']) ?>" 
                                data-ogrenci="<?= htmlspecialchars($row['ogrenci_adi']) ?>">
                                İade Al
                            </button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-warning text-center">
            <?= $search !== "" ? "Eşleşen sonuç bulunamadı." : "Emanette kitap yok." ?>
        </p>
    <?php endif; ?>
</div>

<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="returnForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="returnModalLabel">Kitap İadesi Onayı</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
          </div>
          <div class="modal-body">
            <p>Kitap: <strong id="modalKitapAdi"></strong></p>
            <p>Öğrenci: <strong id="modalOgrenciAdi"></strong></p>
            <p>Bu kitabı iade almak istediğinize emin misiniz?</p>
            <input type="hidden" name="return_id" id="return_id" value="">
            <input type="hidden" name="last_search" value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-primary">İade Al</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    var returnModal = document.getElementById('returnModal')
    returnModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget
        var id = button.getAttribute('data-id')
        var kitap = button.getAttribute('data-kitap')
        var ogrenci = button.getAttribute('data-ogrenci')

        document.getElementById('return_id').value = id
        document.getElementById('modalKitapAdi').textContent = kitap
        document.getElementById('modalOgrenciAdi').textContent = ogrenci
    })
</script>
<?php include 'footer.php'; ?>
</body>
</html>
