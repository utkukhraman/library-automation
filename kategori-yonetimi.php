<?php
include 'menu.php'; 


if (isset($_POST['kategori_ekle'])) {
    $kategori_adi = mysqli_real_escape_string($conn, $_POST['kategori_adi']);
    $kategori_adi = ucwords(strtolower(trim($kategori_adi)));

    $check_query = "SELECT COUNT(*) AS count FROM kitap_kategori WHERE kategori_adi = '$kategori_adi'";
    $check_result = mysqli_query($conn, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);

    if ($check_row['count'] == 0) {
        $query = "INSERT INTO kitap_kategori (kategori_adi) VALUES ('$kategori_adi')";
        mysqli_query($conn, $query);
    }

    header("Location: kategori-yonetimi.php");
}

if (isset($_GET['sil'])) {
    $kategori_id = intval($_GET['sil']);
    
    $check_query = "SELECT COUNT(*) AS count FROM kitaplar WHERE kategori_id = $kategori_id";
    $check_result = mysqli_query($conn, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] == 0) {
        $query = "DELETE FROM kitap_kategori WHERE kategori_id = $kategori_id";
        mysqli_query($conn, $query);
        header("Location: kategori-yonetimi.php");
    } else {
        echo "<script>alert('Bu kategoriye bağlı kitaplar olduğu için silinemez.');</script>";
    }
}

if (isset($_POST['kategori_guncelle'])) {
    $kategori_id = intval($_POST['kategori_id']);
    $kategori_adi = mysqli_real_escape_string($conn, $_POST['kategori_adi']);
    $kategori_adi = ucwords(strtolower(trim($kategori_adi)));

    $query = "UPDATE kitap_kategori SET kategori_adi = '$kategori_adi' WHERE kategori_id = $kategori_id";
    mysqli_query($conn, $query);
    header("Location: kategori-yonetimi.php");
}

$query = "SELECT * FROM kitap_kategori";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <h2 class="text-center mb-4">Kategori Yönetimi</h2>
        
        <div class="card p-4 shadow-sm">
            <form method="POST" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="kategori_adi" class="form-control" placeholder="Yeni kategori adı" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="kategori_ekle" class="btn btn-primary w-100">Ekle</button>
                </div>
            </form>
        </div>

        <div class="card mt-4 p-4 shadow-sm">
            <table class="table table-striped text-center">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Kategori Adı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['kategori_id'] ?></td>
                            <td>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="kategori_id" value="<?= $row['kategori_id'] ?>">
                                    <input type="text" name="kategori_adi" class="form-control me-2" value="<?= htmlspecialchars($row['kategori_adi']) ?>">
                                    <button type="submit" name="kategori_guncelle" class="btn btn-success">Güncelle</button>
                                </form>
                            </td>
                            <td>
                                <a href="kategori-yonetimi.php?sil=<?= $row['kategori_id'] ?>" class="btn btn-danger" onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">Sil</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>
