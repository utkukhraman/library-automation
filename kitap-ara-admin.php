<?php
include 'menu.php'; 


$search = "";
$results = [];
$kitaplar = [];

if (isset($_POST["search"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]);
    
    $query = "SELECT kitaplar.kitap_id, kitaplar.kitap_adi, kitaplar.barkod_no, kitaplar.basim_tarihi, 
                     kitaplar.sayfa_sayisi, dil.dil_adi, yazarlar.yazar_ad, kitap_kategori.kategori_adi, kitaplar.emanet
              FROM kitaplar 
              LEFT JOIN yazarlar ON kitaplar.yazar_id = yazarlar.yazar_id
              LEFT JOIN kitap_kategori ON kitaplar.kategori_id = kitap_kategori.kategori_id
              LEFT JOIN dil ON kitaplar.dil_id = dil.dil_id
              WHERE kitaplar.kitap_adi LIKE '%$search%' 
                 OR yazarlar.yazar_ad LIKE '%$search%' 
                 OR kitap_kategori.kategori_adi LIKE '%$search%' 
                 OR dil.dil_adi LIKE '%$search%'";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("SQL Hatası: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}

$kitap_sorgu = "SELECT kitap_id, kitap_adi, yazar_id, barkod_no, emanet FROM kitaplar";
$kitap_sonuc = mysqli_query($conn, $kitap_sorgu);

if (!$kitap_sonuc) {
    die("Veritabanı hatası: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($kitap_sonuc)) {
    $kitaplar[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Yönetimi</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">

    <h2 class="text-center">Kitap Arama</h2>

    <form method="POST" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Kitap, yazar veya kategori adı girin..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if (!empty($results)): ?>
        <!--h3>Arama Sonuçları</h3-->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kitap Adı</th>
                    <th>Yazar</th>
                    <th>Barkod No</th>
                    <th>Basım Yılı</th>
                    <th>Sayfa Sayısı</th>
                    <th>Kategori</th>
                    <th>Dil</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $kitap): ?>
                    <tr>
                        <td><?= $kitap["kitap_id"] ?></td>
                        <td><?= htmlspecialchars($kitap["kitap_adi"]) ?></td>
                        <td><?= htmlspecialchars($kitap["yazar_ad"] ?? "Bilinmiyor") ?></td>
                        <td><?= htmlspecialchars($kitap["barkod_no"]) ?></td>
                        <td><?= $kitap["basim_tarihi"] ?></td>
                        <td><?= $kitap["sayfa_sayisi"] ?></td>
                        <td><?= htmlspecialchars($kitap["kategori_adi"] ?? "Bilinmiyor") ?></td>
                        <td><?= htmlspecialchars($kitap["dil_adi"] ?? "Bilinmiyor") ?></td>
                        <td>
                            <span class="badge <?= $kitap['emanet'] ? 'bg-danger' : 'bg-success' ?>">
                                <?= $kitap['emanet'] ? 'Emanet' : 'Kütüphane' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($search !== ""): ?>
        <p class="alert alert-warning">Sonuç bulunamadı.</p>
    <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>
