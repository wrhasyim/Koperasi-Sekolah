<?php
// 1. SETUP PARAMETER & DEFAULT
$mode  = isset($_GET['mode']) ? $_GET['mode'] : 'harian';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// 2. LOGIKA QUERY DINAMIS
$sql = "";
$params = [];
$labels = [];
$data_masuk = [];
$data_keluar = [];
$judul_grafik = "";

// Filter Kategori: HANYA KOPERASI MURNI (Exclude Seragam/Eskul)
$filter_kategori = "AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul')";

if($mode == 'harian'){
    // --- MODE HARIAN (Data per tanggal dalam 1 bulan) ---
    $judul_grafik = "Laporan Harian - Bulan " . tglIndo("$tahun-$bulan-01");
    $sql = "SELECT DATE(tanggal) as label_waktu, 
            SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
            SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
            FROM transaksi_kas 
            WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? $filter_kategori
            GROUP BY DATE(tanggal) ORDER BY label_waktu ASC";
    $params = [$bulan, $tahun];

} elseif($mode == 'bulanan'){
    // --- MODE BULANAN (Data per bulan dalam 1 tahun) ---
    $judul_grafik = "Laporan Bulanan - Tahun $tahun";
    $sql = "SELECT MONTH(tanggal) as label_waktu, 
            SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
            SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
            FROM transaksi_kas 
            WHERE YEAR(tanggal) = ? $filter_kategori
            GROUP BY MONTH(tanggal) ORDER BY label_waktu ASC";
    $params = [$tahun];

} elseif($mode == 'tahunan'){
    // --- MODE TAHUNAN (5 Tahun Terakhir) ---
    $judul_grafik = "Tren Tahunan (5 Tahun Terakhir)";
    $sql = "SELECT YEAR(tanggal) as label_waktu, 
            SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
            SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
            FROM transaksi_kas 
            WHERE YEAR(tanggal) >= ? $filter_kategori
            GROUP BY YEAR(tanggal) ORDER BY label_waktu ASC";
    $params = [$tahun - 4]; // Ambil dari 4 tahun lalu
}

// 3. EKSEKUSI QUERY
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_data = $stmt->fetchAll();

// 4. FORMAT DATA KE ARRAY JS
// Inisialisasi array kosong agar grafik tetap muncul walau data kosong
$temp_data = [];
foreach($raw_data as $r) {
    $temp_data[$r['label_waktu']] = $r;
}

if($mode == 'harian'){
    // Loop tanggal 1 s/d jumlah hari di bulan itu
    $jml_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    for($i=1; $i<=$jml_hari; $i++){
        $tgl_cek = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i); // YYYY-MM-DD
        $labels[] = date('d', strtotime($tgl_cek)); // Label tgl saja
        $data_masuk[] = isset($temp_data[$tgl_cek]) ? $temp_data[$tgl_cek]['total_masuk'] : 0;
        $data_keluar[] = isset($temp_data[$tgl_cek]) ? $temp_data[$tgl_cek]['total_keluar'] : 0;
    }
} elseif($mode == 'bulanan'){
    $nama_bln = [1=>"Jan","Feb","Mar","Apr","Mei","Jun","Jul","Ags","Sep","Okt","Nov","Des"];
    for($i=1; $i<=12; $i++){
        $labels[] = $nama_bln[$i];
        $data_masuk[] = isset($temp_data[$i]) ? $temp_data[$i]['total_masuk'] : 0;
        $data_keluar[] = isset($temp_data[$i]) ? $temp_data[$i]['total_keluar'] : 0;
    }
} elseif($mode == 'tahunan'){
    for($i = ($tahun-4); $i <= $tahun; $i++){
        $labels[] = $i;
        $data_masuk[] = isset($temp_data[$i]) ? $temp_data[$i]['total_masuk'] : 0;
        $data_keluar[] = isset($temp_data[$i]) ? $temp_data[$i]['total_keluar'] : 0;
    }
}

// JSON Encode untuk dikirim ke Javascript
$js_labels = json_encode($labels);
$js_masuk = json_encode($data_masuk);
$js_keluar = json_encode($data_keluar);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Visualisasi Data</h6>
        <h2 class="h3 fw-bold mb-0">Analisis Keuangan</h2>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form method="GET" class="row g-2 align-items-center">
            
            <div class="col-auto">
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="mode" id="mode_harian" value="harian" <?= $mode=='harian'?'checked':'' ?> onchange="this.form.submit()">
                    <label class="btn btn-outline-primary btn-sm fw-bold" for="mode_harian">Harian</label>

                    <input type="radio" class="btn-check" name="mode" id="mode_bulanan" value="bulanan" <?= $mode=='bulanan'?'checked':'' ?> onchange="this.form.submit()">
                    <label class="btn btn-outline-primary btn-sm fw-bold" for="mode_bulanan">Bulanan</label>

                    <input type="radio" class="btn-check" name="mode" id="mode_tahunan" value="tahunan" <?= $mode=='tahunan'?'checked':'' ?> onchange="this.form.submit()">
                    <label class="btn btn-outline-primary btn-sm fw-bold" for="mode_tahunan">Tahunan</label>
                </div>
            </div>

            <div class="col-auto border-start ps-3 ms-2"></div>

            <?php if($mode == 'harian'): ?>
            <div class="col-auto">
                <select name="bulan" class="form-select form-select-sm border-0 shadow-sm bg-white fw-bold" onchange="this.form.submit()">
                    <?php 
                    $bln_indo = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                    for($i=1; $i<=12; $i++){
                        $sel = ($i==$bulan) ? 'selected' : '';
                        echo "<option value='$i' $sel>".$bln_indo[$i]."</option>";
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-auto">
                <select name="tahun" class="form-select form-select-sm border-0 shadow-sm bg-white fw-bold" onchange="this.form.submit()">
                    <?php 
                    for($y=date('Y'); $y>=2020; $y--){
                        $sel = ($y==$tahun) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    }
                    ?>
                </select>
            </div>

        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i> <?= $judul_grafik ?></h6>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">(Koperasi Murni)</small>
            </div>
            <div class="card-body p-4">
                <div style="height: 450px;">
                    <canvas id="grafikKeuangan"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById('grafikKeuangan').getContext('2d');
    
    // Style Gradasi
    var gradientMasuk = ctx.createLinearGradient(0, 0, 0, 400);
    gradientMasuk.addColorStop(0, 'rgba(28, 200, 138, 0.7)');
    gradientMasuk.addColorStop(1, 'rgba(28, 200, 138, 0.05)');

    var gradientKeluar = ctx.createLinearGradient(0, 0, 0, 400);
    gradientKeluar.addColorStop(0, 'rgba(231, 74, 59, 0.7)');
    gradientKeluar.addColorStop(1, 'rgba(231, 74, 59, 0.05)');

    var myChart = new Chart(ctx, {
        type: 'line', // Ganti jadi Line agar lebih smooth untuk trend, atau 'bar' jika suka batang
        data: {
            labels: <?= $js_labels ?>, 
            datasets: [
                {
                    label: 'Pemasukan (Rp)',
                    data: <?= $js_masuk ?>,
                    backgroundColor: gradientMasuk,
                    borderColor: '#1cc88a',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#1cc88a',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 // Membuat garis melengkung (smooth)
                },
                {
                    label: 'Pengeluaran (Rp)',
                    data: <?= $js_keluar ?>,
                    backgroundColor: gradientKeluar,
                    borderColor: '#e74a3b',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#e74a3b',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { family: "'Inter', sans-serif", size: 12, weight: 'bold' },
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#2c3e50',
                    bodyColor: '#2c3e50',
                    borderColor: '#e3e6f0',
                    borderWidth: 1,
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f8f9fc', borderDash: [5, 5] },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k'; // Singkat angka ribuan
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
        }
    });
});
</script>