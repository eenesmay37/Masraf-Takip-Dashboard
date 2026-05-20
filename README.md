# 💸 Profesyonel Masraf Takip Dashboard (Expense Tracker)

Bu proje, kişisel veya kurumsal harcamaları düzenli bir şekilde kayıt altına almak, kategorize etmek ve finansal durumu analiz etmek amacıyla geliştirilmiş, tam donanımlı bir web tabanlı CRUD (Create, Read, Update, Delete) uygulamasıdır. 

## 🚀 Öne Çıkan Özellikler

* **🔐 Güvenli Yönetim Paneli (Login/Session):** Sisteme sadece yetkili kullanıcıların erişebilmesini sağlayan PHP tabanlı oturum yönetimi.
* **📊 Dinamik Veri Görselleştirme:** Chart.js entegrasyonu ile harcamaların kategorilere göre dağılımını gösteren interaktif pasta grafik.
* **📑 Excel Raporlama (Export):** Tek tıkla veritabanındaki tüm harcama geçmişini Excel (`.xls`) formatında bilgisayara indirebilme.
* **🌙 Karanlık Tema (Dark Mode):** Tek tuşla aktif edilebilen, modern ve göz yormayan arayüz seçeneği.
* **📱 Tam Uyumlu Arayüz (Responsive):** Bootstrap 5 framework'ü kullanılarak mobil, tablet ve masaüstü cihazlar için optimize edilmiş şık tasarım.
* **⚙️ Temiz Kod (Clean Code) Mimarisi:** Veritabanı bağlantı işlemlerinin ana koddan izole edildiği yönetilebilir yapı ve PDO ile SQL Injection koruması.

## 🛠️ Kullanılan Teknolojiler

* **Backend:** PHP (PDO)
* **Veritabanı:** MySQL
* **Frontend:** HTML5, CSS3 (Bootstrap 5), JavaScript (Chart.js)

## ⚙️ Kurulum ve Çalıştırma

1. Yerel sunucunuzda (XAMPP/WAMP) `masraf_db` adında boş bir MySQL veritabanı oluşturun.
2. Aşağıdaki SQL sorgusunu çalıştırarak gerekli tabloyu kurun:
   ```sql
   CREATE TABLE masraflar (
       id INT AUTO_INCREMENT PRIMARY KEY,
       baslik VARCHAR(255) NOT NULL,
       miktar DECIMAL(10,2) NOT NULL,
       kategori VARCHAR(100) NOT NULL,
       tarih DATE NOT NULL
   );