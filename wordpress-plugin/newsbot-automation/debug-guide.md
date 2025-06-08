# NewsBot Plugin Hata Ayıklama Rehberi

## 🚨 Beyaz Ekran Sorunu Çözümü

### **1. WordPress Hata Ayıklama Modunu Açın**

WordPress'in `wp-config.php` dosyasına şu satırları ekleyin:

```php
// Hata ayıklama modunu aç
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
```

### **2. PHP Hata Loglarını Kontrol Edin**

- **cPanel/Hosting Panel**: Error Logs bölümünü kontrol edin
- **XAMPP**: `xampp/apache/logs/error.log` dosyasını kontrol edin
- **WordPress**: `/wp-content/debug.log` dosyasını kontrol edin

### **3. Plugin Dosya İzinlerini Kontrol Edin**

```bash
# Klasör izinleri: 755
chmod 755 /wp-content/plugins/newsbot-automation/

# Dosya izinleri: 644
chmod 644 /wp-content/plugins/newsbot-automation/*.php
```

### **4. PHP Sürümünü Kontrol Edin**

Plugin minimum **PHP 7.4** gerektirir. XAMPP'te PHP sürümünü kontrol edin:

```php
<?php phpinfo(); ?>
```

### **5. WordPress Veritabanı Bağlantısını Test Edin**

WordPress admin panelinde **Araçlar** > **Site Durumu** sayfasını kontrol edin.

## 🔧 **Hızlı Çözümler**

### **Çözüm 1: Plugin'i Yeniden Etkinleştirin**
1. WordPress admin panelinde **Eklentiler** sayfasına gidin
2. NewsBot Automation'ı **Devre Dışı Bırak**
3. Tekrar **Etkinleştir**

### **Çözüm 2: WordPress Önbelleğini Temizleyin**
- Önbellek plugin'i varsa temizleyin
- Tarayıcı önbelleğini temizleyin (Ctrl+F5)

### **Çözüm 3: Diğer Plugin'leri Devre Dışı Bırakın**
Çakışma olup olmadığını test etmek için diğer plugin'leri geçici olarak devre dışı bırakın.

### **Çözüm 4: Tema Değiştirin**
Geçici olarak varsayılan WordPress temasına geçin.

## 📋 **Kontrol Listesi**

- [ ] PHP 7.4+ sürümü aktif
- [ ] WordPress 5.0+ sürümü
- [ ] Plugin dosyaları doğru dizinde
- [ ] Dosya izinleri doğru (755/644)
- [ ] WordPress veritabanı bağlantısı çalışıyor
- [ ] Hata ayıklama modu açık
- [ ] Diğer plugin'lerle çakışma yok

## 🆘 **Acil Durum Çözümü**

Eğer site tamamen erişilemez hale geldiyse:

1. **FTP/cPanel ile plugin klasörünü silin**:
   `/wp-content/plugins/newsbot-automation/`

2. **Veya plugin dosyasını yeniden adlandırın**:
   `newsbot-automation.php` → `newsbot-automation.php.bak`

3. **Site normale döndükten sonra tekrar yükleyin**

## 📞 **Destek İçin Gerekli Bilgiler**

Destek talep ederken şu bilgileri hazırlayın:

- WordPress sürümü
- PHP sürümü  
- Hosting sağlayıcısı
- Hata mesajları (error.log'dan)
- Aktif plugin listesi
- Kullanılan tema

---

**Not**: Bu rehber XAMPP, WAMP, MAMP gibi yerel geliştirme ortamları ve canlı hosting için geçerlidir.