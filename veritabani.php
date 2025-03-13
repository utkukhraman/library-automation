<?php
$sunucu = "localhost";
$kullanici_adi = "root";
$sifre = "";
$veritabani = "kutuphane"; 
$conn = new mysqli($sunucu, $kullanici_adi, $sifre, $veritabani);

if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}
?>
