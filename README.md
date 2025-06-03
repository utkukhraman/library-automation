# Kütüphane Otomasyon Sistemi Projesi

Merhaba! Bu benim PHP ve MySQL kullanarak geliştirdiğim Kütüphane Otomasyon Sistemi projem. Hem yöneticiler için bir panel hem de kullanıcıların kitap arayabileceği basit arayüzler içeriyor.

## 🔗 Projeyi Denemek İstersen (Demo)

Aşağıdaki linkten projeyi canlı olarak deneyebilirsin:

* **Demo Adresi:** [https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kutuphane-otomasyonu/](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kutuphane-otomasyonu/)
* **Kullanıcı Adı:** `ben@utkukahraman.dev`
* **Şifre:** `Test1234.`

*Not: Bu bir demo sürümüdür, bilgiler zaman zaman sıfırlanabilir veya demo yayından kalkabilir.*

## 📖 Bu Proje Ne Yapar?

Bu sistemle bir kütüphanedeki kitapları, öğrencileri ve emanet işlemlerini yönetebiliriz. Amaç, kütüphane işlerini biraz daha kolaylaştırmak!

## ✨ Projedeki Ana Özellikler ve Sayfalar

Projede bir sürü sayfa var, her birinin farklı bir görevi var:

**1. Giriş ve Kullanıcı İşlemleri:**
* **`giris.php`:** Yöneticilerin sisteme e-posta/kullanıcı adı ve şifreleriyle girdiği sayfa.
* **`menu.php`:** Giriş yapınca çıkan, her sayfada görünen üst menü. Buradan her yere ulaşabiliyoruz.
* **`profil.php`:** Giriş yapan kullanıcı kendi bilgilerini görebiliyor ve şifresini değiştirebiliyor.
* **`cikis.php`:** Güvenli çıkış yapmak için.

**2. Yönetim Paneli (`panel.php`):**
* Bu sayfa, kütüphanenin genel durumunu özetliyor.
* **Hızlı Bilgiler (Kartlar):** Toplam kitap, mevcut kitap, toplam öğrenci, aktif emanet, geciken teslimler ve 3 gün içinde teslim edilecek kitap sayısı gibi bilgileri gösteriyor.
* **Önemli Listeler (İlk 5):**
    * En son eklenen 5 kitap.
    * En çok ödünç alınan 5 kitap.
    * En çok kitap alan 5 öğrenci.
    * Teslim tarihi en yakın 5 emanet.

![](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/panel.png)

**3. Kitap İşlemleri:**
* **`kitap-ekle.php`:** Kütüphaneye yeni kitap ekleme sayfası.
[![Kitap Ekleme Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kitap-ekle.mp4)
* **`kitap-ara-admin.php`:** Yöneticilerin kitap arayıp, emanet vermek için seçtiği sayfa.
[![Kitap Arama Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kitap-ara-admin.mp4)
* **`kitap-ara-duzenle.php`:** Yöneticilerin kitap arayıp, düzenlemek için seçtiği sayfa.
* **`kitap-duzenle.php`:** Seçilen bir kitabın bilgilerini güncelleme ve kitabı silme işlemleri.
[![Kitap Ara ve Düzenleme Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kitap-duzenle.mp4)
* **`kitap-ara.php`:** Öğrenci kitap arama sayfası, kitapların durumunu gösteriyor.
[![Öğrenci Kitap Arama Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kitap-ara.mp4)
* **`kategori-yonetimi.php`:** Kitap kategorilerini ekleme, güncelleme ve silme.
[![Kategori Ekleme Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/kategori-ekle.mp4)

**4. Öğrenci İşlemleri:**
* **`ogrenci-ara.php`:** Öğrencileri arama, listeleme ve yasaklı durumunu değiştirme.
[![Öğrenci Arama ve Yasaklama Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/ogrenci-ara.mp4)
* **`ogrenci-detay.php`:** Seçilen bir öğrencinin detaylı bilgileri ve aldığı kitapların listesi.
[![Öğrenci Detay Bilgileri Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/ogrenci-detay.mp4)

**5. Emanet (Ödünç Verme/Alma) İşlemleri:**
* **`emanet-ver.php`:** Bir kitabı öğrenciye emanet verme sayfası (öğrenci arama ve yasak/kitap durumu kontrolü ile).
[![Emanet Verme Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/emanet-ver.mp4)
* **`emanet-ara.php`:** Aktif emanetleri arama, listeleme ve filtreleme (aktif/geciken/yaklaşan). Kitapları iade alma.
[![Emanet Arama Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/emanet-ara.mp4)
* **`emanet-gecmis-ara.php`:** Daha önce verilip iade edilmiş tüm emanetlerin listesi.
[![Öğrenci Detay Bilgileri Videosu]](https://files.utkukahraman.dev/isu/2/bahar/bitirme-projesi/emanet-gecmisi-ara.mp4)

**Diğerleri:**
* **`veritabani.php`:** Veritabanı bağlantısını yapan dosyalar.
* **`footer.php`:** Her sayfanın altında çıkan genel bilgiler.
* **`assets/` klasörü:** CSS ve JS dosyaları.


## 🛠️ Kullanılan Teknolojiler

* **PHP:** Backend kısımlarını yapıyor.
* **MySQL:** Bütün bilgiler burada saklanıyor.
* **HTML & CSS:** Sayfaların görünümü için.
* **Bootstrap:** Sayfalar daha düzenli ve mobil uyumlu görünsün diye.
* **JavaScript (ve jQuery):** Bazı sayfalarda işleri güzelleştirmek için.

## 🚀 Nasıl Çalıştırılır?

1.  XAMPP, WAMP gibi bir programla bilgisayarında PHP ve MySQL sunucusu kur.
2.  Veritabanını oluştur ve tabloları içe aktar.
3.  `veritabani.php` dosyasındaki veritabanı bağlantı bilgilerini kendi sunucuna göre ayarla.
4.  Proje dosyalarını sunucunun web klasörüne (XAMPP için genelde `htdocs`) at.
5.  Tarayıcıdan `localhost/proje-klasor-adi/giris.php` adresine git.

Hepsi bu kadar! 😊