<?php

if (isset($_POST["search"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]);
    $query = "SELECT kitaplar.kitap_id, kitaplar.kitap_adi, kitaplar.barkod_no, kitaplar.basim_tarihi, 
    kitaplar.sayfa_sayisi, dil.dil_adi, yazarlar.yazar_ad, kitap_kategori.kategori_adi
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
        die("SQL Hatası: " . mysqli_error($conn)); // Hata mesajını göster
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap ve Yazar Arama - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        input[type="text"] { padding: 8px; width: 300px; }
        button { padding: 8px 15px; cursor: pointer; }
    </style>
</head>
<body>

    <h2 class="text-center">Kitap ve Yazar Arama</h2>

    <form method="POST">
        <input type="text" name="search" placeholder="Kitap, yazar veya kategori adı girin..." class="form-control w-50" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Ara</button>
    </form>


    <?php if (!empty($results)): ?>
        <table>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($search !== ""): ?>
        <p>Sonuç bulunamadı.</p>
    <?php endif; ?>

</body>
</html>

