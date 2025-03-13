<?php
session_start();
require 'veritabani.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $giris_bilgisi = $_POST['giris_bilgisi'];
    $sifre = md5($_POST['sifre']); 
    $query = "SELECT * FROM kullanicilar WHERE (email = ? OR ad = ?) AND sifre = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $giris_bilgisi, $giris_bilgisi, $sifre);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: panel.php"); 
        exit();
    } else {
        $hata = "Hatalı giriş bilgileri!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş Yap - Kütüphane Otomasyonu</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stil.css"> 
</head>
<body class="giris-govde">
    <div class="giris-kapsayici">
        <div class="giris-kart">
            <h3 class="giris-baslik">Giriş Yap</h3>
            <?php if (isset($hata)) echo "<div class='giris-hata'>$hata</div>"; ?>
            <form method="POST">
                <div class="giris-form-alani">
                    <label class="giris-etiket">E-Posta</label>
                    <input type="text" name="giris_bilgisi" class="giris-girdi" required>
                </div>
                <div class="giris-form-alani">
                    <label class="giris-etiket">Şifre</label>
                    <input type="password" name="sifre" class="giris-girdi" required>
                </div>
                <button type="submit" class="giris-buton">Giriş Yap</button>
            </form>
        </div>
    </div>
</body>
</html>
