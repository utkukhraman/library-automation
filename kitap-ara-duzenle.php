<?php
include 'menu.php'; 

$search = "";
$results = [];

if (isset($_POST["search"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]);
    
    $query = "SELECT kitaplar.kitap_id, kitaplar.kitap_adi, kitaplar.barkod_no, kitaplar.basim_tarihi, 
    kitaplar.sayfa_sayisi, dil.dil_adi, yazarlar.yazar_ad, kitap_kategori.kategori_adi, kitaplar.emanet
FROM kitaplar 
LEFT JOIN yazarlar ON kitaplar.yazar_id = yazarlar.yazar_id
LEFT JOIN kitap_kategori ON kitaplar.kategori_id = kitap_kategori.kategori_id
LEFT JOIN dil ON kitaplar.dil_id = dil.dil_id
WHERE (kitaplar.kitap_adi LIKE '%$search%' 
OR yazarlar.yazar_ad LIKE '%$search%' 
OR kitap_kategori.kategori_adi LIKE '%$search%' 
OR dil.dil_adi LIKE '%$search%')
AND kitaplar.silindi_mi = 0
ORDER BY kitaplar.kitap_id ASC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("SQL Hatası: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kitap Arama - Düzenle</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Kitap Düzenle</h2>

    <form method="POST" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap, yazar veya kategori adı girin..." value="<?= htmlspecialchars($search) ?>" />
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if (!empty($results)): ?>
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-light">
                <tr>
                    <!--th>ID</th-->
                    <th>Kitap Adı</th>
                    <th>Yazar</th>
                    <th>Barkod No</th>
                    <th>Basım Yılı</th>
                    <th>Sayfa Sayısı</th>
                    <th>Kategori</th>
                    <th>Dil</th>
                    <!--th>Durum</th-->
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $kitap): ?>
                <tr>
                    <!--td><?= $kitap["kitap_id"] ?></td-->
                    <td><?= htmlspecialchars($kitap["kitap_adi"]) ?></td>
                    <td><?= htmlspecialchars($kitap["yazar_ad"] ?? "Bilinmiyor") ?></td>
                    <td><?= htmlspecialchars($kitap["barkod_no"]) ?></td>
                    <td><?= $kitap["basim_tarihi"] ?></td>
                    <td><?= $kitap["sayfa_sayisi"] ?></td>
                    <td><?= htmlspecialchars($kitap["kategori_adi"] ?? "Bilinmiyor") ?></td>
                    <td><?= htmlspecialchars($kitap["dil_adi"] ?? "Bilinmiyor") ?></td>
                    <!--td>
                        <span class="badge <?= $kitap['emanet'] ? 'bg-danger' : 'bg-success' ?>">
                            <?= $kitap['emanet'] ? 'Emanet' : 'Kütüphane' ?>
                        </span>
                    </td-->
                    <td>
                        <a href="kitap-duzenle.php?id=<?= $kitap['kitap_id'] ?>" class="btn btn-warning btn-sm">Düzenle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($search !== ""): ?>
        <p class="alert alert-warning text-center">Sonuç bulunamadı.</p>
    <?php endif; ?>
</div>
    </br></br>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
