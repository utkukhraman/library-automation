<?php
session_start();
require 'veritabani.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT ad, soyad, email, telefon, sifre FROM kullanicilar WHERE id = ?");
$stmt->execute([$user_id]);
$kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

$modalMessage = '';
$modalType = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eski_sifre = md5($_POST['eski_sifre']);
    $yeni_sifre = $_POST['yeni_sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    if ($eski_sifre !== $kullanici['sifre']) {
        $modalMessage = 'Eski şifre hatalı.';
        $modalType = 'danger';
    } elseif ($yeni_sifre !== $sifre_tekrar) {
        $modalMessage = 'Yeni şifreler uyuşmuyor.';
        $modalType = 'danger';
    } else {
        $yeni_sifre_md5 = md5($yeni_sifre);
        $update = $pdo->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
        $update->execute([$yeni_sifre_md5, $user_id]);
        $modalMessage = 'Şifre başarıyla güncellendi.';
        $modalType = 'success';
        
        $stmt->execute([$user_id]);
        $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<?php include 'menu.php'; ?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profil Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">Profil Bilgileriniz</h2>

    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Ad:</strong> <?= htmlspecialchars($kullanici['ad']) ?></p>
            <p><strong>Soyad:</strong> <?= htmlspecialchars($kullanici['soyad']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($kullanici['email']) ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars($kullanici['telefon']) ?></p>
        </div>
    </div>

    <h4>Şifre Değiştir</h4>
    <form method="post" class="row g-3">
        <div class="col-md-4">
            <label for="eski_sifre" class="form-label">Eski Şifre</label>
            <input type="password" name="eski_sifre" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label for="yeni_sifre" class="form-label">Yeni Şifre</label>
            <input type="password" name="yeni_sifre" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label for="sifre_tekrar" class="form-label">Yeni Şifre (Tekrar)</label>
            <input type="password" name="sifre_tekrar" class="form-control" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
        </div>
    </form>
</div>

<div class="modal fade" id="messageModal" tabindex="-1" aria-labellepdoy="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-<?= $modalType ?: 'primary' ?>">
      <div class="modal-header bg-<?= $modalType ?: 'primary' ?> text-white">
        <h5 class="modal-title" id="messageModalLabel"><?= $modalType === 'success' ? 'Başarılı' : 'Hata' ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        <?= htmlspecialchars($modalMessage) ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($modalMessage): ?>
<script>
    var myModal = new bootstrap.Modal(document.getElementById('messageModal'));
    myModal.show();
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
</body>
</html>
