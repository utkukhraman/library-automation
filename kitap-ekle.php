<?php
session_start();
require 'veritabani.php';

$ekleyen_kullanici_id = $_SESSION['user_id'] ?? null;
$kitap_eklendi = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kitap_adi = mb_convert_case($_POST['kitap_adi'], MB_CASE_TITLE, "UTF-8");
    $yazar_ad = mb_convert_case($_POST['yazar_ad'], MB_CASE_TITLE, "UTF-8");
    $kategori_id = $_POST['kategori_id'];
    $barkod_no = $_POST['barkod_no'];
    $basim_tarihi = $_POST['basim_tarihi'];
    $sayfa_sayisi = $_POST['sayfa_sayisi'];
    $dil_adi = mb_convert_case($_POST['dil'], MB_CASE_TITLE, "UTF-8");

    try {
        // Yazar kontrol
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

        // Dil kontrol
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

        // Kitap ekleme
        $stmt = $pdo->prepare("INSERT INTO kitaplar (kitap_adi, yazar_id, kategori_id, barkod_no, basim_tarihi, sayfa_sayisi, dil_id, ekleyen_kullanici_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kitap_adi, $yazar_id, $kategori_id, $barkod_no, $basim_tarihi, $sayfa_sayisi, $dil_id, $ekleyen_kullanici_id]);

        $kitap_eklendi = true;
    } catch (PDOException $e) {
        echo "Veritabanı hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kitap Ekle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stil.css">
</head>
<body>
<?php include 'menu.php'; ?>

<div class="container mt-5">
    <div class="card shadow p-4" style="max-width: 500px; margin: 0 auto;">
        <h1 class="text-center mb-4">Kitap Ekle</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="kitap_adi" class="form-label">Kitap Adı:</label>
                <input type="text" id="kitap_adi" name="kitap_adi" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="yazar_ad" class="form-label">Yazar Adı:</label>
                <input type="text" id="yazar_ad" name="yazar_ad" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="kategori_id" class="form-label">Kategori Seç:</label>
                <select name="kategori_id" id="kategori_id" class="form-select" required>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM kitap_kategori");
                    while ($kategori = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$kategori['kategori_id']}'>{$kategori['kategori_adi']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="barkod_no" class="form-label">Barkod No:</label>
                <input type="text" id="barkod_no" name="barkod_no" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="basim_tarihi" class="form-label">Basım Yılı:</label>
                <select name="basim_tarihi" id="basim_tarihi" class="form-select" required>
                    <?php
                    $currentYear = date('Y');
                    for ($i = $currentYear; $i >= 1900; $i--) {
                        echo "<option value='$i'>$i</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="sayfa_sayisi" class="form-label">Sayfa Sayısı:</label>
                <input type="number" id="sayfa_sayisi" name="sayfa_sayisi" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="dil" class="form-label">Dil:</label>
                <input type="text" id="dil" name="dil" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Kitap Ekle</button>
        </form>
    </div>
</div>

<div class="modal fade" id="kitapEklendiModal" tabindex="-1" aria-labelledby="kitapEklendiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kitapEklendiModalLabel">Başarılı!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        Kitap başarıyla eklendi!
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Tamam</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($kitap_eklendi): ?>
<script>
    const modal = new bootstrap.Modal(document.getElementById('kitapEklendiModal'));
    window.addEventListener('load', () => {
        modal.show();
    });
</script>
<?php endif; ?>
<br><br>
<?php include 'footer.php'; ?>
</body>
</html>
