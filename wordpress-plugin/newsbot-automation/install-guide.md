# NewsBot Automation WordPress Plugin Kurulum Rehberi

## 📦 **Plugin Kurulumu**

### **Yöntem 1: Manuel Kurulum**
1. `wordpress-plugin/newsbot-automation` klasörünü tamamen kopyalayın
2. WordPress sitenizin `/wp-content/plugins/` klasörüne yapıştırın
3. WordPress admin panelinde **Eklentiler** > **Yüklü Eklentiler** sayfasına gidin
4. "NewsBot Automation" eklentisini **Etkinleştir**

### **Yöntem 2: ZIP Dosyası ile Kurulum**
1. `newsbot-automation` klasörünü ZIP olarak sıkıştırın
2. WordPress admin panelinde **Eklentiler** > **Yeni Ekle** > **Eklenti Yükle**
3. ZIP dosyasını seçin ve yükleyin
4. Eklentiyi etkinleştirin

## ⚙️ **İlk Kurulum Ayarları**

### **1. Admin Panelinde Ayarlar**
- WordPress admin panelinde sol menüde **"NewsBot"** sekmesi görünecek
- **NewsBot** > **Ayarlar** sayfasına gidin

### **2. API Anahtarları (İsteğe Bağlı)**
```
Google Analytics ID: GA-XXXXXXXXX-X
SERP API Key: (SEO takibi için - serpapi.com)
Google Trends API: (Trend analizi için)
```

### **3. Anahtar Kelime Takibi**
- **"Anahtar Kelime Ekle"** butonuna tıklayın
- Takip etmek istediğiniz kelimeleri ekleyin
- Hedef URL'leri belirtin

## 🔧 **Teknik Gereksinimler**

- **WordPress**: 5.0 veya üzeri
- **PHP**: 7.4 veya üzeri
- **MySQL**: 5.6 veya üzeri
- **cURL**: Etkin olmalı (API çağrıları için)

## 📊 **Özellikler ve Kullanım**

### **Dashboard**
- Günlük ziyaretçi istatistikleri
- SEO sıralama özeti
- En popüler içerikler
- Trafik kaynak analizi

### **Haber Analizi**
- Otomatik haber tarama
- Google Trends entegrasyonu
- İçerik önerileri
- Yayınlama zamanlaması

### **SEO Takibi**
- Anahtar kelime sıralama takibi
- Günlük pozisyon değişiklikleri
- Rekabet analizi
- Arama hacmi bilgileri

### **Web Sitesi Analitikleri**
- Ziyaretçi davranış analizi
- En çok aranan kelimeler
- Sayfa performans metrikleri
- Çıkma oranı takibi

## 🚀 **Gelişmiş Özellikler**

### **Otomatik Görevler**
Plugin otomatik olarak şu görevleri yapar:
- Günlük SEO sıralama kontrolü
- Ziyaretçi istatistik toplama
- Trend konu analizi
- Performans raporlama

### **Veritabanı Tabloları**
Plugin şu tabloları oluşturur:
- `wp_newsbot_analytics`: Günlük istatistikler
- `wp_newsbot_seo_history`: SEO geçmişi
- `wp_newsbot_keywords`: Takip edilen kelimeler

## 🔒 **Güvenlik**

- Tüm AJAX çağrıları nonce ile korunur
- Kullanıcı yetkileri kontrol edilir
- SQL injection koruması
- XSS koruması

## 🆘 **Sorun Giderme**

### **Plugin Görünmüyor**
- Dosya izinlerini kontrol edin (755)
- WordPress hata loglarını inceleyin
- PHP hata raporlamasını açın

### **API Çalışmıyor**
- cURL modülünün aktif olduğunu kontrol edin
- API anahtarlarının doğru olduğunu kontrol edin
- Sunucu güvenlik duvarı ayarlarını kontrol edin

### **Veriler Görünmüyor**
- Veritabanı tablolarının oluştuğunu kontrol edin
- WordPress cron job'larının çalıştığını kontrol edin
- Plugin aktivasyon/deaktivasyon yapın

## 📞 **Destek**

Herhangi bir sorun yaşarsanız:
1. WordPress hata loglarını kontrol edin
2. Plugin ayarlarını sıfırlayın
3. Teknik destek için iletişime geçin

## 🔄 **Güncelleme**

Plugin güncellemeleri için:
1. Yeni sürümü indirin
2. Eski plugin klasörünü silin
3. Yeni sürümü yükleyin
4. Ayarları kontrol edin

---

**Not**: Bu plugin React tabanlı modern bir arayüz kullanır ve WordPress'in REST API'sini kullanarak çalışır. İlk kurulumdan sonra tüm özellikler otomatik olarak aktif hale gelir.