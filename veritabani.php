<?php
$sunucu = "localhost";
$kullanici_adi = "root";
$sifre = "";
$veritabani = "kutuphane1"; 
$conn = new mysqli($sunucu, $kullanici_adi, $sifre, $veritabani);

if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=kutuphane1', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Veritabanı bağlantı hatası: " . $e->getMessage();
    exit;
}
?>

