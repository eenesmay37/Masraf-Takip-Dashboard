<?php
session_start();
// Hata ayıklama modunu açtık ki arkada ne döndüğünü bilelim
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'baglanti.php';

// 1. EXCEL İNDİRME İŞLEMİ
if (isset($_GET['excel']) && isset($_SESSION['oturum'])) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Masraf_Raporu_" . date('Y-m-d') . ".xls");
    echo "\xEF\xBB\xBF"; 
    
    $masraflar_excel = $db->query("SELECT * FROM masraflar ORDER BY tarih DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>
            <tr style='background-color:#2c3e50; color:white;'>
                <th>Tarih</th><th>Başlık</th><th>Kategori</th><th>Miktar (TL)</th>
            </tr>";
    if (!empty($masraflar_excel)) {
        foreach ($masraflar_excel as $m) {
            $tarih = date('d.m.Y', strtotime($m['tarih']));
            echo "<tr><td>{$tarih}</td><td>{$m['baslik']}</td><td>{$m['kategori']}</td><td>{$m['miktar']}</td></tr>";
        }
    }
    echo "</table>";
    exit;
}

// 2. GİRİŞ KONTROLÜ
if (isset($_POST['giris'])) {
    if ($_POST['kullanici'] == 'admin' && $_POST['sifre'] == '123456') {
        $_SESSION['oturum'] = true;
        header("Location: index.php"); exit;
    } else {
        $hata = "Hatalı kullanıcı adı veya şifre!";
    }
}

// 3. GÜVENLİ ÇIKIŞ
if (isset($_GET['cikis'])) {
    session_destroy();
    header("Location: index.php"); exit;
}

// OTURUM YOKSA GİRİŞ EKRANINI GÖSTER
if (!isset($_SESSION['oturum'])) {
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sisteme Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-lg p-4" style="width: 400px; border-radius: 15px;">
        <h3 class="text-center mb-4 fw-bold text-primary">🔐 Yönetim Girişi</h3>
        <?php if(isset($hata)) echo "<div class='alert alert-danger'>$hata</div>"; ?>
        <form method="POST">
            <input type="text" name="kullanici" class="form-control mb-3 p-3" placeholder="Kullanıcı Adı (admin)" required>
            <input type="password" name="sifre" class="form-control mb-3 p-3" placeholder="Şifre (123456)" required>
            <button type="submit" name="giris" class="btn btn-primary w-100 p-2 fw-bold">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// -- GİRİŞ YAPILDIYSA ÇALIŞACAK KISIM --

// Masraf Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ekle'])) {
    $sorgu = $db->prepare("INSERT INTO masraflar (baslik, miktar, kategori, tarih) VALUES (?, ?, ?, ?)");
    $sorgu->execute([$_POST['baslik'], $_POST['miktar'], $_POST['kategori'], $_POST['tarih']]);
    header("Location: index.php"); exit;
}

// Masraf Silme İşlemi
if (isset($_GET['sil'])) {
    $sorgu = $db->prepare("DELETE FROM masraflar WHERE id = ?");
    $sorgu->execute([$_GET['sil']]);
    header("Location: index.php"); exit;
}

// Verileri Çekme
$masraflar = $db->query("SELECT * FROM masraflar ORDER BY tarih DESC")->fetchAll(PDO::FETCH_ASSOC);

// Toplam Harcama (Boş Veri Kontrollü Güvenli Çekim)
$toplamSorgu = $db->query("SELECT SUM(miktar) as toplam FROM masraflar")->fetch(PDO::FETCH_ASSOC);
$toplam = (isset($toplamSorgu['toplam']) && is_numeric($toplamSorgu['toplam'])) ? (float)$toplamSorgu['toplam'] : 0.0;

// Grafik Verilerini Çekme (Boş Veri Kontrollü)
$grafikSorgu = $db->query("SELECT kategori, SUM(miktar) as k_toplam FROM masraflar GROUP BY kategori")->fetchAll(PDO::FETCH_ASSOC);
$kategoriler = []; $k_toplamlar = [];
if (!empty($grafikSorgu)) {
    foreach ($grafikSorgu as $g) {
        if (!empty($g['kategori'])) {
            $kategoriler[] = $g['kategori'];
            $k_toplamlar[] = (float)$g['k_toplam'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr" data-bs-theme="light" id="htmlTag">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesyonel Masraf Takip Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-body-tertiary">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-body rounded shadow-sm">
        <h3 class="m-0 text-primary fw-bold">💸 Masraf Dashboard</h3>
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="temaDegistir()">🌙 Tema</button>
            <a href="?excel=1" class="btn btn-success me-2">📊 Excel İndir</a>
            <a href="?cikis=1" class="btn btn-danger">🚪 Çıkış</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body bg-primary text-white text-center rounded">
                    <h5 class="mb-1 text-white-50">Toplam Bütçe Harcaması</h5>
                    <h2 class="m-0 fw-bold">₺<?php echo number_format($toplam, 2, ',', '.'); ?></h2>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="baslik" placeholder="Harcama Başlığı (Örn: Market)" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="number" step="0.01" class="form-control" name="miktar" placeholder="Tutar (₺)" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <select class="form-select" name="kategori" required>
                                    <option value="">Kategori Seç</option>
                                    <option value="Yemek">Yemek</option>
                                    <option value="Ulaşım">Ulaşım</option>
                                    <option value="Fatura">Fatura</option>
                                    <option value="Eğlence">Eğlence</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="date" class="form-control" name="tarih" required>
                        </div>
                        <button type="submit" name="ekle" class="btn btn-primary w-100 fw-bold">Masraf Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <h5 class="text-center text-muted mb-3">Harcama Dağılımı</h5>
                    <div style="width: 100%; max-height: 280px;">
                        <canvas id="masrafGrafik"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tarih</th>
                            <th>Başlık</th>
                            <th>Kategori</th>
                            <th>Miktar</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($masraflar)): ?>
                            <?php foreach ($masraflar as $masraf): ?>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($masraf['tarih'])); ?></td>
                                <td><?php echo htmlspecialchars($masraf['baslik']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($masraf['kategori']); ?></span></td>
                                <td class="fw-bold text-danger">₺<?php echo number_format($masraf['miktar'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="?sil=<?php echo $masraf['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Henüz hiç masraf eklenmedi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('masrafGrafik').getContext('2d');
    const kategoriler = <?php echo json_encode($kategoriler); ?>;
    const toplamlar = <?php echo json_encode($k_toplamlar); ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: kategoriler,
            datasets: [{
                data: toplamlar,
                backgroundColor: ['#e74c3c', '#3498db', '#f1c40f', '#2ecc71', '#9b59b6'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    function temaDegistir() {
        const html = document.getElementById('htmlTag');
        if (html.getAttribute('data-bs-theme') === 'light') {
            html.setAttribute('data-bs-theme', 'dark');
        } else {
            html.setAttribute('data-bs-theme', 'light');
        }
    }
</script>

</body>
</html>