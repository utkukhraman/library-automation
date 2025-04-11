<?php
session_start();
require 'veritabani.php';
$ekleyen_kullanici_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kitap_adi = mb_convert_case($_POST['kitap_adi'], MB_CASE_TITLE, "UTF-8"); 
    $yazar_ad = mb_convert_case($_POST['yazar_ad'], MB_CASE_TITLE, "UTF-8"); 
    $kategori_id = $_POST['kategori_id'];
    $barkod_no = $_POST['barkod_no'];
    $basim_tarihi = $_POST['basim_tarihi']; 
    $sayfa_sayisi = $_POST['sayfa_sayisi'];
    $dil_adi = mb_convert_case($_POST['dil'], MB_CASE_TITLE, "UTF-8"); // Dil adı input

    try {
        $stmt = $pdo->prepare("SELECT yazar_id FROM yazarlar WHERE yazar_ad = ?");
        $stmt->execute([$yazar_ad]);
        $yazar = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$yazar) {
            $stmt = $pdo->prepare("INSERT INTO yazarlar (yazar_ad) VALUES (?)");
            $stmt->execute([$yazar_ad]);
            $yazar_id = $pdo->lastInsertId();
        } else {
            $yazar_id = $yazar['yazar_id'];
        }

        $stmt = $pdo->prepare("SELECT dil_id FROM dil WHERE dil_adi = ?");
        $stmt->execute([$dil_adi]);
        $dil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dil) {
            $stmt = $pdo->prepare("INSERT INTO dil (dil_adi) VALUES (?)");
            $stmt->execute([$dil_adi]);
            $dil_id = $pdo->lastInsertId();
        } else {
            $dil_id = $dil['dil_id'];
        }

        $stmt = $pdo->prepare("INSERT INTO kitaplar (kitap_adi, yazar_id, kategori_id, barkod_no, basim_tarihi, sayfa_sayisi, dil_id, ekleyen_kullanici_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kitap_adi, $yazar_id, $kategori_id, $barkod_no, $basim_tarihi, $sayfa_sayisi, $dil_id, $ekleyen_kullanici_id]);

        echo "Kitap başarıyla eklendi!";
    } catch (PDOException $e) {
        echo "Veritabanı hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Ekle</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stil.css"> 
</head>
<body>
<?php include 'menu.php'; ?>  

    <div class="container mt-5">
        <div class="kitap-ekle-adm-container card shadow p-4">
            <h1 class="text-center mb-4">Kitap Ekle</h1>
            <form method="POST">
                <div class="mb-3">
                    <label for="kitap_adi" class="kitap-ekle-adm-label form-label">Kitap Adı:</label>
                    <input type="text" id="kitap_adi" name="kitap_adi" class="kitap-ekle-adm-input form-control" required>
                </div>

                <div class="mb-3">
                    <label for="yazar_ad" class="kitap-ekle-adm-label form-label">Yazar Adı:</label>
                    <input type="text" id="yazar_ad" name="yazar_ad" class="kitap-ekle-adm-input form-control" required>
                </div>

                <div class="mb-3">
                    <label for="kategori_id" class="kitap-ekle-adm-label form-label">Kategori Seç:</label>
                    <select name="kategori_id" id="kategori_id" class="kitap-ekle-adm-select form-select" required>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM kitap_kategori");
                        while ($kategori = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$kategori['kategori_id']}'>{$kategori['kategori_adi']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="barkod_no" class="kitap-ekle-adm-label form-label">Barkod No:</label>
                    <input type="text" id="barkod_no" name="barkod_no" class="kitap-ekle-adm-input form-control" required>
                </div>

                <div class="mb-3">
                    <label for="basim_tarihi" class="kitap-ekle-adm-label form-label">Basım Yılı:</label>
                    <select name="basim_tarihi" id="basim_tarihi" class="kitap-ekle-adm-select form-select" required>
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= 1900; $i--) {
                            echo "<option value='$i'>$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="sayfa_sayisi" class="kitap-ekle-adm-label form-label">Sayfa Sayısı:</label>
                    <input type="number" id="sayfa_sayisi" name="sayfa_sayisi" class="kitap-ekle-adm-input form-control" required>
                </div>

                <div class="mb-3">
                    <label for="dil" class="kitap-ekle-adm-label form-label">Dil:</label>
                    <input type="text" id="dil" name="dil" class="kitap-ekle-adm-input form-control" required>
                </div>

                <button type="submit" class="kitap-ekle-adm-submit btn btn-primary w-100">Kitap Ekle</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
