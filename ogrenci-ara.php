<?php
session_start();
require 'veritabani.php';

$search = "";
$results = [];

$stmt = $pdo->prepare("SELECT * FROM ogrenciler LIMIT 30");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST["search"])) {
    $search = trim($_POST["search"]);
    
    $stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE ogrenci_no LIKE ? OR adi_soyadi LIKE ?");
    $searchParam = "%$search%";
    $stmt->execute([$searchParam, $searchParam]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST["ogrenci_no"])) {
    $ogrenciNo = $_POST["ogrenci_no"];

    $stmt = $pdo->prepare("SELECT yasakli_mi FROM ogrenciler WHERE ogrenci_no = ?");
    $stmt->execute([$ogrenciNo]);
    $ogrenci = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ogrenci) {
        $yeniDurum = $ogrenci["yasakli_mi"] ? 0 : 1;
        $update = $pdo->prepare("UPDATE ogrenciler SET yasakli_mi = ? WHERE ogrenci_no = ?");
        $update->execute([$yeniDurum, $ogrenciNo]);
        echo json_encode(["success" => true, "yeniDurum" => $yeniDurum]);
        exit;
    }
    
    echo json_encode(["success" => false]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Ara - Kütüphane Otomasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'menu.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Öğrenci Arama</h2>

    <form method="POST" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Öğrenci No veya Ad Soyad girin..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if (!empty($results)): ?>
        <table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Öğrenci No</th>
            <th>Adı Soyadı</th>
            <th>Durum</th>
            <th>Detay</th> 
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $ogrenci): ?>
            <tr>
                <td><?= $ogrenci["id"] ?></td>
                <td><?= htmlspecialchars($ogrenci["ogrenci_no"]) ?></td>
                <td><?= htmlspecialchars($ogrenci["adi_soyadi"]) ?></td>
                <td>
                    <span class="badge durum-badge <?= $ogrenci["yasakli_mi"] ? 'bg-danger' : 'bg-success' ?>"
                          data-ogrenci-no="<?= htmlspecialchars($ogrenci["ogrenci_no"]) ?>"
                          data-ad="<?= htmlspecialchars($ogrenci["adi_soyadi"]) ?>"
                          data-yasakli="<?= $ogrenci["yasakli_mi"] ?>"
                          data-bs-toggle="modal"
                          data-bs-target="#confirmModal">
                        <?= $ogrenci["yasakli_mi"] ? 'Yasaklı' : 'Aktif' ?>
                    </span>
                </td>
                <td>
                    <a href="ogrenci-detay.php?ogrenci_no=<?= urlencode($ogrenci["ogrenci_no"]) ?>" class="btn btn-info btn-sm">Detay</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <?php elseif ($search !== ""): ?>
        <p class="text-center text-danger">Sonuç bulunamadı.</p>
    <?php endif; ?>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Onay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p id="confirmText"></p>
                <input type="hidden" id="selectedOgrenciNo">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Evet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Başarılı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p>Öğrenci durumu başarıyla güncellendi.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let selectedOgrenciNo = "";
    
    document.querySelectorAll(".durum-badge").forEach(function (badge) {
        badge.addEventListener("click", function () {
            selectedOgrenciNo = this.getAttribute("data-ogrenci-no");
            let ogrenciAd = this.getAttribute("data-ad");
            let yasakliMi = this.getAttribute("data-yasakli") === "1";

            let mesaj = yasakliMi 
                ? `${selectedOgrenciNo} nolu ${ogrenciAd} isimli öğrencinin yasağını kaldırmak istediğinize emin misiniz?` 
                : `${selectedOgrenciNo} nolu ${ogrenciAd} isimli öğrenciyi yasaklamak istediğinize emin misiniz?`;

            document.getElementById("confirmText").innerText = mesaj;
            document.getElementById("selectedOgrenciNo").value = selectedOgrenciNo;
        });
    });

    document.getElementById("confirmAction").addEventListener("click", function () {
        let ogrenciNo = document.getElementById("selectedOgrenciNo").value;

        fetch("ogrenci-ara.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "ogrenci_no=" + encodeURIComponent(ogrenciNo)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let modal = new bootstrap.Modal(document.getElementById("successModal"));
                modal.show();
                setTimeout(() => location.reload(), 1500);
            }
        });
    });
});
</script>
<?php include 'footer.php'; ?>

</body>
</html>
