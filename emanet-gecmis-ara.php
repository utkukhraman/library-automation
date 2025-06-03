<?php

require_once 'veritabani.php';
include 'menu.php';

$search = "";
$results = [];

$whereConditions = ["e.durum = 1"]; // durum 1 olanları çek

if (isset($_POST["search"])) {
    $search = trim($_POST["search"]);
    $search_esc = mysqli_real_escape_string($conn, $search);

    $whereConditions[] = "(k.kitap_adi LIKE '%$search_esc%' 
            OR o.ogrenci_no LIKE '%$search_esc%' 
            OR o.adi_soyadi LIKE '%$search_esc%')";
}


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
        e.durum,
        e.iade_tarihi
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
    <title>Geçmiş Emanetler</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Geçmiş Emanetler</h2>

    <form method="POST" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap adı, öğrenci no veya öğrenci adı..." value="<?= htmlspecialchars($search) ?>">
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
                    <th>İade Tarihi</th>
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
                    <td><?= htmlspecialchars($row['emanet_baslangic']) ?></td>
                    <td><?= $row['emanet_bitis'] ? htmlspecialchars($row['emanet_bitis']) : '-' ?></td>
                    <td><?= $row['iade_tarihi'] ? htmlspecialchars($row['iade_tarihi']) : '-' ?></td>
                    <td>
                        <span class="badge bg-success">
                            İade Edildi
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-warning text-center">
            <?= $search !== "" ? "Eşleşen sonuç bulunamadı." : "Geçmişte emanet edilmiş kitap bulunamadı." ?>
        </p>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
