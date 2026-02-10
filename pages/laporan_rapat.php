<?php
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$tahun_ini = date('Y');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Pelaporan Eksekutif</h6>
        <h2 class="h3 fw-bold mb-0">Laporan Rapat (Bulanan / Tahunan)</h2>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i> Konfigurasi Laporan</h6>
            </div>
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="https://img.icons8.com/color/96/microsoft-excel-2019--v1.png" alt="Excel" class="mb-3" style="width: 64px;">
                    <h5 class="fw-bold">Export Laporan Keuangan Detail</h5>
                    <p class="text-muted text-center" style="max-width: 500px; margin: 0 auto;">
                        Laporan Laba Rugi & Neraca sesuai standar akuntansi untuk keperluan Rapat Anggota (RAT).
                    </p>
                </div>

                <form method="POST" action="process/export_laporan_rapat.php" target="_blank">
                    
                    <div class="bg-light p-4 rounded-3 border mb-3">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fas fa-calendar-alt me-2"></i> Periode Laporan</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="fw-bold small mb-1">Dari Bulan</label>
                                <select name="bulan_awal" class="form-select shadow-sm" required>
                                    <?php 
                                    $bln_indo = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                                    for($i=1; $i<=12; $i++){
                                        $sel = ($i==1) ? 'selected' : ''; // Default Jan
                                        echo "<option value='$i' $sel>".$bln_indo[$i]."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small mb-1">Sampai Bulan</label>
                                <select name="bulan_akhir" class="form-select shadow-sm" required>
                                    <?php 
                                    for($i=1; $i<=12; $i++){
                                        $sel = ($i==date('n')) ? 'selected' : ''; // Default Bulan Ini
                                        echo "<option value='$i' $sel>".$bln_indo[$i]."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small mb-1">Tahun</label>
                                <select name="tahun" class="form-select shadow-sm" required>
                                    <?php 
                                    for($y=date('Y'); $y>=2020; $y--){
                                        $sel = ($y==$tahun_ini) ? 'selected' : '';
                                        echo "<option value='$y' $sel>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-light p-4 rounded-3 border mb-4">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fas fa-sliders-h me-2"></i> Opsi Data Tambahan</h6>
                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="inc_seragam" id="cekSeragam" checked>
                                <label class="form-check-label fw-bold text-dark" for="cekSeragam">Sertakan Unit Seragam</label>
                                <div class="form-text small">Hitung omzet & stok seragam sekolah.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="inc_eskul" id="cekEskul" checked>
                                <label class="form-check-label fw-bold text-dark" for="cekEskul">Sertakan Unit Eskul</label>
                                <div class="form-text small">Hitung omzet & stok atribut eskul.</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="export_rapat" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm fs-5">
                        <i class="fas fa-download me-2"></i> DOWNLOAD LAPORAN EXCEL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>