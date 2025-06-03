<?php
session_start();
include 'baglanti.php';
include 'menu.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Kullanıcı kimliği bulunamadı. Lütfen tekrar giriş yapın.</div>";
    exit;
}

$kullanici_id = $_SESSION['user_id'];
$kitap_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT * FROM kitaplar WHERE kitap_id = $kitap_id AND silindi_mi = 0";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-warning'>Kitap bulunamadı.</div>";
    exit;
}

$kitap = mysqli_fetch_assoc($result);

$popup_message = '';
$popup_type = ''; 

//güncelle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["guncelle"])) {
    $kitap_adi = mysqli_real_escape_string($conn, $_POST["kitap_adi"]);
    $barkod_no = mysqli_real_escape_string($conn, $_POST["barkod_no"]);
    $sayfa_sayisi = intval($_POST["sayfa_sayisi"]);

    $sql_guncelle = "UPDATE kitaplar 
                     SET kitap_adi='$kitap_adi', barkod_no='$barkod_no', sayfa_sayisi=$sayfa_sayisi 
                     WHERE kitap_id=$kitap_id";

    if (mysqli_query($conn, $sql_guncelle)) {
        $popup_message = "Kitap bilgileri başarıyla güncellendi.";
        $popup_type = "success";
    } else {
        $popup_message = "Güncelleme sırasında hata oluştu.";
        $popup_type = "danger";
    }

    $result = mysqli_query($conn, "SELECT * FROM kitaplar WHERE kitap_id = $kitap_id");
    $kitap = mysqli_fetch_assoc($result);
}

// sil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["sil"])) {
    if ($kitap['emanet'] == 1) {
        $popup_message = "Bu kitap şu anda emanet durumda, silinemez.";
        $popup_type = "warning";
    } else {
        $sql_sil = "UPDATE kitaplar 
                    SET silindi_mi = 1, silen_kullanici_id = $kullanici_id 
                    WHERE kitap_id = $kitap_id";

        if (mysqli_query($conn, $sql_sil)) {
            $popup_message = "Kitap başarıyla silindi.";
            $popup_type = "success";
        } else {
            $popup_message = "Silme sırasında hata oluştu.";
            $popup_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kitap Düzenle</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Kitap Düzenle</h3>

    <?php if ($popup_message): ?>
        <div class="alert alert-<?= $popup_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($popup_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Kitap Adı</label>
            <input type="text" name="kitap_adi" class="form-control" value="<?= htmlspecialchars($kitap['kitap_adi']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Barkod No</label>
            <input type="text" name="barkod_no" class="form-control" value="<?= htmlspecialchars($kitap['barkod_no']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sayfa Sayısı</label>
            <input type="number" name="sayfa_sayisi" class="form-control" value="<?= htmlspecialchars($kitap['sayfa_sayisi']) ?>" required>
        </div>
        <button type="submit" name="guncelle" class="btn btn-primary">Güncelle</button>

        <?php if ($kitap['emanet'] == 1): ?>
            <button class="btn btn-secondary ms-2" disabled>Emanette - Silinemez</button>
        <?php else: ?>
            <button type="submit" name="sil" class="btn btn-danger ms-2" onclick="return confirm('Bu kitabı silmek istediğinize emin misiniz?')">Kitabı Sil</button>
        <?php endif; ?>

        <a href="kitap-ara-admin.php" class="btn btn-outline-dark ms-2">Kitap Listesine Dön</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
