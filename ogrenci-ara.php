<?php
session_start();
require 'veritabani.php'; // $pdo nesnesinin burada tanımlandığını varsayıyoruz

// --- Sayfalama Ayarları ---
$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;

// --- Değişkenler ---
$search_input = ""; // Kullanıcının girdiği ham arama terimi
$results = [];
$total_items = 0;
$total_pages = 0;
$query_active = false; // Ana sorguların çalışıp çalışmayacağını belirleyen bayrak

// --- AJAX ile Öğrenci Durum Güncelleme ---
// Bu blok, sayfa listeleme mantığından önce çalışmalı ve JSON cevabından sonra çıkmalıdır.
if (isset($_POST["ogrenci_no"]) && !isset($_POST["search"])) { // Sadece ogrenci_no varsa (arama değilse)
    $ogrenciNo = $_POST["ogrenci_no"];

    try {
        $stmt = $pdo->prepare("SELECT yasakli_mi FROM ogrenciler WHERE ogrenci_no = ?");
        $stmt->execute([$ogrenciNo]);
        $ogrenci = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ogrenci) {
            $yeniDurum = $ogrenci["yasakli_mi"] ? 0 : 1; // Durumu tersine çevir
            $update = $pdo->prepare("UPDATE ogrenciler SET yasakli_mi = ? WHERE ogrenci_no = ?");
            $update->execute([$yeniDurum, $ogrenciNo]);
            echo json_encode(["success" => true, "yeniDurum" => $yeniDurum]);
        } else {
            echo json_encode(["success" => false, "message" => "Öğrenci bulunamadı."]);
        }
    } catch (PDOException $e) {
        // Gerçek bir uygulamada burada hata loglanmalı
        echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
    }
    exit; // AJAX cevabından sonra PHP betiğinin çalışmasını durdur
}

// --- Listeleme/Arama Sorgusunun Aktif Olup Olmadığını Belirle ---
if (isset($_POST["search"])) { // Arama formu gönderildiyse
    $query_active = true;
    $search_input = trim($_POST["search"]);
    $current_page = 1; // Yeni arama için sayfayı 1'e sıfırla
    $offset = 0;       // Offset'i yeniden hesapla
} elseif (isset($_GET["search"])) { // URL'de arama parametresi varsa (sayfalamada)
    $query_active = true;
    $search_input = trim($_GET["search"]);
    // $current_page zaten $_GET['page'] veya varsayılan olarak ayarlanmıştır
    // $offset zaten $current_page'e göre hesaplanmıştır
} else { // Hiçbir arama parametresi yoksa (ilk sayfa yüklemesi)
    $query_active = true;
    $search_input = ""; // Arama terimi yok, tüm öğrenciler listelenecek
}

// --- Veritabanı Sorguları (Eğer $query_active ise) ---
if ($query_active) {
    $base_sql_from = "FROM ogrenciler";
    $sql_where_parts = [];
    $execute_params = [];

    // Arama koşullarını hazırla (eğer arama terimi varsa)
    if (!empty($search_input)) {
        $sql_where_parts[] = "(ogrenci_no LIKE ? OR adi_soyadi LIKE ?)";
        $search_param_for_query = "%" . $search_input . "%";
        $execute_params[] = $search_param_for_query;
        $execute_params[] = $search_param_for_query;
    }

    // --- Toplam Öğe Sayısı Sorgusu ---
    $sql_count_parts = ["SELECT COUNT(*) as total_count", $base_sql_from];
    if (!empty($sql_where_parts)) {
        $sql_count_parts[] = "WHERE " . implode(" AND ", $sql_where_parts);
    }
    $sql_count = implode(" ", $sql_count_parts);
    
    try {
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute($execute_params); // Arama parametrelerini kullan
        $total_items = (int)$stmt_count->fetchColumn();

        if ($total_items > 0) {
            $total_pages = ceil($total_items / $items_per_page);

            // Geçerli sayfanın sınırlar içinde olduğundan emin ol
            if ($current_page > $total_pages && $total_pages > 0) {
                $current_page = $total_pages;
                $offset = ($current_page - 1) * $items_per_page;
            } elseif ($current_page <= 0) { // $current_page en az 1 olmalı
                 $current_page = 1;
                 $offset = 0;
            }

            // --- Veri Çekme Sorgusu ---
            $sql_data_parts = ["SELECT *", $base_sql_from];
            $data_execute_params = $execute_params; // Arama parametrelerini kopyala

            if (!empty($sql_where_parts)) {
                $sql_data_parts[] = "WHERE " . implode(" AND ", $sql_where_parts);
            }
            $sql_data_parts[] = "ORDER BY id ASC LIMIT ? OFFSET ?"; // Örnek sıralama
            
            $data_execute_params[] = $items_per_page; // LIMIT için
            $data_execute_params[] = $offset;         // OFFSET için
            
            $sql_data = implode(" ", $sql_data_parts);
            $stmt_data = $pdo->prepare($sql_data);
            
            // Parametre türlerini belirtmek için döngü (PDO execute dizisi genellikle türleri otomatik belirler)
            // Ancak LIMIT/OFFSET için explicit INT daha güvenli olabilir.
            // Bu örnekte, execute() içine doğrudan dizi veriyoruz. PDO, sayısal değerleri doğru şekilde ele almalıdır.
            // Gerekirse, bindParam ile türleri tek tek belirleyebilirsiniz.
            $param_idx = 1;
            foreach ($data_execute_params as $key => $value) {
                 if (is_int($value) && ($key == count($data_execute_params) - 2 || $key == count($data_execute_params) -1) ) { // LIMIT ve OFFSET için
                    $stmt_data->bindValue($param_idx++, $value, PDO::PARAM_INT);
                 } else {
                    $stmt_data->bindValue($param_idx++, $value, PDO::PARAM_STR);
                 }
            }
            $stmt_data->execute();
            $results = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $results = []; // Sonuç bulunamadı
            $total_pages = 0; // Sayfa yok
        }
    } catch (PDOException $e) {
        // Gerçek bir uygulamada burada hata loglanmalı
        // echo "Veritabanı hatası: " . $e->getMessage(); // Geliştirme sırasında
        $results = [];
        $total_items = 0;
        $total_pages = 0;
        // Kullanıcıya dostça bir mesaj gösterilebilir
    }
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

    <form method="POST" action="ogrenci-ara.php" class="d-flex justify-content-center mb-4">
        <input type="text" name="search" class="form-control w-50" placeholder="Öğrenci No veya Ad Soyad girin..." value="<?= htmlspecialchars($search_input) ?>">
        <button type="submit" class="btn btn-primary ms-2">Ara</button>
    </form>

    <?php if ($query_active && !empty($results)): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
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
                                  style="cursor: pointer;"
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
    <?php elseif ($query_active && !empty($search_input) && empty($results)): ?>
        <p class="alert alert-warning text-center">"<?= htmlspecialchars($search_input) ?>" için sonuç bulunamadı.</p>
    <?php elseif ($query_active && empty($search_input) && empty($results)): ?>
        <p class="alert alert-info text-center">Listelenecek öğrenci bulunmamaktadır.</p>
    <?php endif; ?>

    <?php if ($query_active && !empty($results) && $total_pages > 1): ?>
    <nav aria-label="Sayfa navigasyonu" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_input) ?>">Önceki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Önceki</span></li>
            <?php endif; ?>

            <?php
            $num_links_around_current = 2;
            $start_loop = max(1, $current_page - $num_links_around_current);
            $end_loop = min($total_pages, $current_page + $num_links_around_current);

            if ($start_loop > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search_input) . '">1</a></li>';
                if ($start_loop > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_input) ?>"><?= $i ?></a></li>
            <?php endfor;

            if ($end_loop < $total_pages) {
                if ($end_loop < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search_input) . '">' . $total_pages . '</a></li>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_input) ?>">Sonraki</a></li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Sonraki</span></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Durum Değişikliği Onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p id="confirmText"></p>
                <input type="hidden" id="selectedOgrenciNoModalInput">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Evet, Durumu Değiştir</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">İşlem Başarılı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p>Öğrenci durumu başarıyla güncellendi.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="location.reload();">Tamam</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Modal için veri aktarımı
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal) {
        confirmModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Badge'e tıklandığında
            const ogrenciNo = button.getAttribute('data-ogrenci-no');
            const ogrenciAd = button.getAttribute('data-ad');
            const yasakliMi = button.getAttribute('data-yasakli') === "1";

            const modalTitle = confirmModal.querySelector('.modal-title');
            const modalBodyText = confirmModal.querySelector('#confirmText');
            const modalOgrenciNoInput = confirmModal.querySelector('#selectedOgrenciNoModalInput');

            modalOgrenciNoInput.value = ogrenciNo;
            let mesaj = yasakliMi 
                ? `${ogrenciNo} numaralı "${ogrenciAd}" isimli öğrencinin yasağını kaldırmak istediğinize emin misiniz?` 
                : `${ogrenciNo} numaralı "${ogrenciAd}" isimli öğrenciyi yasaklamak istediğinize emin misiniz?`;
            modalBodyText.innerText = mesaj;
        });
    }

    // Durum değiştirme onayı
    const confirmActionButton = document.getElementById("confirmAction");
    if (confirmActionButton) {
        confirmActionButton.addEventListener("click", function () {
            let ogrenciNo = document.getElementById("selectedOgrenciNoModalInput").value;
            
            const confirmModalInstance = bootstrap.Modal.getInstance(confirmModal);
            confirmModalInstance.hide(); // Onay modalını gizle

            fetch("ogrenci-ara.php", { // Aynı sayfaya POST
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "ogrenci_no=" + encodeURIComponent(ogrenciNo)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const successModal = new bootstrap.Modal(document.getElementById("successModal"));
                    successModal.show();
                    // Sayfa yenileme successModal'daki Tamam butonuna bırakıldı.
                    // setTimeout(() => location.reload(), 1500); // Eski yöntem
                } else {
                    alert("Bir hata oluştu: " + (data.message || "Bilinmeyen hata"));
                }
            })
            .catch(error => {
                console.error("Fetch hatası:", error);
                alert("İstek gönderilirken bir hata oluştu.");
            });
        });
    }
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>