<?php
require_once 'veritabani.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$isLoggedIn) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
        exit;
    }

    try {
        if (!isset($_POST['action'])) {
            throw new Exception('Geçersiz istek: Action parametresi eksik');
        }

        $action = $_POST['action'];

        if ($action === 'search_student') {
            if (!isset($_POST['search'])) {
                throw new Exception('Arama parametresi eksik');
            }

            $search = trim($_POST['search']);
            if (empty($search)) {
                echo json_encode([]);
                exit;
            }

            $query = "SELECT id, ogrenci_no, adi_soyadi, yasakli_mi FROM ogrenciler
                      WHERE ogrenci_no LIKE CONCAT('%', ?, '%')
                         OR adi_soyadi LIKE CONCAT('%', ?, '%')
                      LIMIT 20";

            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception('Sorgu hazırlama hatası: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "ss", $search, $search);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Sorgu çalıştırma hatası: ' . mysqli_stmt_error($stmt));
            }

            $result = mysqli_stmt_get_result($stmt);
            $ogrenciler = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $ogrenciler[] = $row;
            }

            echo json_encode($ogrenciler);
            exit;
        }

        if ($action === 'give_loan') {
            $ogrenci_id = (int)($_POST['ogrenci_id'] ?? 0);
            $kitap_id = (int)($_POST['kitap_id'] ?? 0);
            $emanet_veren_id = $_SESSION['user_id'] ?? 0;

            if (!$ogrenci_id || !$kitap_id) {
                throw new Exception('Eksik parametre');
            }

            // ögr yasakli mi ?
            $ogrenci_kontrol_query = "SELECT yasakli_mi FROM ogrenciler WHERE id = ?";
            $stmt_ogrenci = mysqli_prepare($conn, $ogrenci_kontrol_query);
            if (!$stmt_ogrenci) {
                throw new Exception('Öğrenci yasaklı kontrol sorgusu hazırlama hatası: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_ogrenci, "i", $ogrenci_id);
            mysqli_stmt_execute($stmt_ogrenci);
            $result_ogrenci = mysqli_stmt_get_result($stmt_ogrenci);
            $ogrenci_bilgi = mysqli_fetch_assoc($result_ogrenci);

            if ($ogrenci_bilgi && $ogrenci_bilgi['yasakli_mi'] == 1) {
                throw new Exception('Bu öğrenci yasaklı olduğu için emanet verilemez.');
            }

            $kontrol = "SELECT * FROM emanet WHERE kitap_id = $kitap_id AND durum = 0";
            $kontrol_sonuc = mysqli_query($conn, $kontrol);

            if (!$kontrol_sonuc) {
                throw new Exception('Sorgu hatası: ' . mysqli_error($conn));
            }

            if (mysqli_num_rows($kontrol_sonuc) > 0) {
                throw new Exception('Bu kitap zaten emanet verilmiş.');
            }

            $bugun = date("Y-m-d H:i:s");
            $bitis = date("Y-m-d H:i:s", strtotime("+15 days"));

            $insert = "INSERT INTO emanet (ogrenci_id, kitap_id, emanet_veren_id, emanet_baslangic, emanet_bitis, durum)
                       VALUES (?, ?, ?, ?, ?, 0)"; 

            $stmt_insert = mysqli_prepare($conn, $insert);
            if (!$stmt_insert) {
                throw new Exception('Emanet ekleme sorgusu hazırlama hatası: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_insert, "iisss", $ogrenci_id, $kitap_id, $emanet_veren_id, $bugun, $bitis);

            if (mysqli_stmt_execute($stmt_insert)) {
                $update = "UPDATE kitaplar SET emanet = 1 WHERE kitap_id = ?";
                $stmt_update = mysqli_prepare($conn, $update);
                if (!$stmt_update) {
                    throw new Exception('Kitap durum güncelleme sorgusu hazırlama hatası: ' . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt_update, "i", $kitap_id);
                mysqli_stmt_execute($stmt_update);
                echo json_encode(['success' => true, 'message' => 'Emanet başarılı bir şekilde verildi.']);
            } else {
                throw new Exception('Emanet verirken hata oluştu: ' . mysqli_stmt_error($stmt_insert));
            }
            exit;
        }

        throw new Exception('Geçersiz işlem');

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if (!$isLoggedIn) {
    die("Giriş yapmalısınız. <a href='login.php'>Giriş Yap</a>");
}

$kitap_id = (int)($_GET['id'] ?? 0);
if (!$kitap_id) {
    die("Geçersiz kitap ID.");
}

$query = "SELECT k.kitap_adi, y.yazar_ad, k.barkod_no, k.basim_tarihi, k.emanet
          FROM kitaplar k
          LEFT JOIN yazarlar y ON k.yazar_id = y.yazar_id
          WHERE k.kitap_id = $kitap_id";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("Kitap bulunamadı.");
}
$kitap = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Emanet Ver - Kitap Yönetimi</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .badge { font-size: 14px; padding: 6px 10px; }
        #ogrenci_results { margin-top: 15px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container mt-5">
    <h2 class="mb-4">Kitabı Emanet Ver</h2>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><strong>Kitap Bilgileri</strong></div>
        <div class="card-body">
            <p><strong>Kitap Adı:</strong> <?= htmlspecialchars($kitap['kitap_adi']) ?></p>
            <p><strong>Yazar:</strong> <?= htmlspecialchars($kitap['yazar_ad'] ?? 'Bilinmiyor') ?></p>
            <p><strong>Barkod No:</strong> <?= htmlspecialchars($kitap['barkod_no']) ?></p>
            <p><strong>Basım Yılı:</strong> <?= htmlspecialchars($kitap['basim_tarihi']) ?></p>
            <p><strong>Durum:</strong> <span id="kitap-durum"><?= $kitap['emanet'] ? '<span class="badge bg-danger">Emanette</span>' : '<span class="badge bg-success">Kütüphanede</span>' ?></span></p>
        </div>
    </div>

    <?php if ($kitap['emanet']): ?>
        <div class="alert alert-warning">Bu kitap zaten emanet verilmiş. Yeni emanet veremezsiniz.</div>
    <?php else: ?>
        <div class="mb-3">
            <label for="ogrenci_search" class="form-label">Öğrenci Ara:</label>
            <input type="text" id="ogrenci_search" class="form-control form-control-lg" placeholder="Öğrenci no veya ad soyad girin..." autocomplete="off" />
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="ogrenci_results" style="display:none;">
                <thead class="table-dark">
                    <tr>
                        <th>Öğrenci No</th>
                        <th>Ad Soyad</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div id="mesaj" class="mt-3"></div>
    <?php endif; ?>

    <a href="kitap-ara-admin.php" class="btn btn-secondary mt-3">Geri Dön</a>
</div>

<script>
$(function(){
    function handleAjaxError(xhr) {
        let errorMsg = "İşlem sırasında hata oluştu.";
        try {
            const res = JSON.parse(xhr.responseText);
            if(res.message) errorMsg = res.message;
            if(xhr.status === 401) {
                window.location.href = 'login.php';
                return "Yönlendiriliyorsunuz...";
            }
        } catch(e) {
            console.error("Hata ayrıştırma hatası:", e);
        }
        return errorMsg;
    }

    $("#ogrenci_search").on("input", function(){
        const val = $(this).val().trim();
        if(val.length < 2){
            $("#ogrenci_results").hide();
            return;
        }

        $.ajax({
            url: 'emanet-ver.php',
            method: 'POST',
            data: {action:'search_student', search: val},
            dataType: 'json',
            success: function(res){
                const tbody = $("#ogrenci_results tbody");
                tbody.empty();

                if(res.length && !res.error){
                    res.forEach(function(ogr){
                        let rowHtml = `<tr>
                            <td>${ogr.ogrenci_no}</td>
                            <td>${ogr.adi_soyadi}`;

                        // emanet ver butonu diable
                        if (ogr.yasakli_mi == 1) {
                            rowHtml += ` 
                            <td>
                                <button class="btn btn-danger btn-sm" disabled>
                                    <i class="bi bi-x-circle"></i> Yasaklı
                                </button>
                            </td>`;
                        } else {
                            rowHtml += `</td>
                            <td>
                                <button class="btn btn-success btn-sm emanet-ver-btn" data-id="${ogr.id}">
                                    <i class="bi bi-book"></i> Emanet Ver
                                </button>
                            </td>`;
                        }
                        rowHtml += `</tr>`;
                        tbody.append(rowHtml);
                    });
                    $("#ogrenci_results").show();
                } else {
                    tbody.append('<tr><td colspan="3" class="text-center">Öğrenci bulunamadı</td></tr>');
                    $("#ogrenci_results").show();
                }
            },
            error: function(xhr){
                $("#ogrenci_results tbody").html(
                    `<tr><td colspan="3" class="text-danger">${handleAjaxError(xhr)}</td></tr>`
                );
                $("#ogrenci_results").show();
            }
        });
    });

    $(document).on('click', '.emanet-ver-btn', function(){
        if(!confirm("Bu öğrenciye emanet vermek istediğinize emin misiniz?")) return;

        const ogrenci_id = $(this).data('id');
        const kitap_id = <?= $kitap_id ?>;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> İşleniyor...');

        $.ajax({
            url: 'emanet-ver.php',
            method: 'POST',
            data: {
                action: 'give_loan',
                ogrenci_id: ogrenci_id,
                kitap_id: kitap_id
            },
            dataType: 'json',
            success: function(res){
                if(res.success){
                    $("#mesaj").html('<div class="alert alert-success">'+res.message+'</div>');
                    $("#kitap-durum").html('<span class="badge bg-danger">Emanette</span>');
                    $("#ogrenci_results").hide();
                    $("#ogrenci_search").prop('disabled', true);
                } else {
                    $("#mesaj").html('<div class="alert alert-danger">'+res.message+'</div>');
                    $btn.prop('disabled', false).html('<i class="bi bi-book"></i> Emanet Ver');
                }
            },
            error: function(xhr){
                $("#mesaj").html('<div class="alert alert-danger">'+handleAjaxError(xhr)+'</div>');
                $btn.prop('disabled', false).html('<i class="bi bi-book"></i> Emanet Ver');
            }
        });
    });
});
</script>
</body>
</html>