<?php
// pages/laporan_rapat.php
// Pastikan sesi dimulai di index, file ini hanya konten
$tahun_ini = date('Y');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Pelaporan & Pertanggungjawaban</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">
            <i class="fas fa-gavel me-2 text-primary"></i>Laporan Rapat Anggota (RAT)
        </h2>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="fas fa-sliders-h me-2"></i> Konfigurasi Laporan Tahunan</h6>
            </div>
            <div class="card-body p-5">
                
                <div class="alert alert-info d-flex align-items-center rounded-3 mb-4" role="alert">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <strong>Fitur Laporan Cerdas:</strong>
                        <br>Laporan ini akan otomatis membandingkan kinerja tahun ini dengan tahun lalu (<i>Year-on-Year</i>), menghitung Neraca Aset, serta membagi SHU sesuai persentase yang diatur di menu Pengaturan.
                    </div>
                </div>

                <form method="POST" action="process/export_laporan_rapat.php" target="_blank">
                    
                    <div class="card border bg-light mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                <i class="fas fa-calendar-alt me-2"></i> Periode Buku
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="fw-bold small mb-1">Dari Bulan</label>
                                    <select name="bulan_awal" class="form-select shadow-sm" required>
                                        <?php 
                                        $bln_indo = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                                        for($i=1; $i<=12; $i++){
                                            $sel = ($i==1) ? 'selected' : '';
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
                                            $sel = ($i==date('n')) ? 'selected' : '';
                                            echo "<option value='$i' $sel>".$bln_indo[$i]."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="fw-bold small mb-1">Tahun Laporan</label>
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
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold text-dark mb-2">
                            <i class="fas fa-edit me-2 text-warning"></i> Berita Acara / Keputusan Rapat
                        </label>
                        <textarea name="berita_acara" class="form-control" rows="6" placeholder="Tuliskan poin-poin penting hasil rapat di sini.&#10;Contoh:&#10;1. Disepakati SHU dibagikan tanggal 20.&#10;2. Pengurus lama dibubarkan dan dibentuk pengurus baru.&#10;3. Rencana kenaikan simpanan wajib tahun depan."></textarea>
                        <div class="form-text">Teks ini akan dicetak langsung pada halaman terakhir laporan Excel.</div>
                    </div>

                    <div class="card border bg-light mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                <i class="fas fa-filter me-2"></i> Cakupan Unit Usaha
                            </h6>
                            <div class="d-flex gap-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="inc_seragam" id="cekSeragam" checked>
                                    <label class="form-check-label fw-bold text-dark" for="cekSeragam">Sertakan Unit Seragam</label>
                                    <div class="small text-muted">Hitung omzet penjualan seragam sekolah.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="inc_eskul" id="cekEskul" checked>
                                    <label class="form-check-label fw-bold text-dark" for="cekEskul">Sertakan Unit Eskul</label>
                                    <div class="small text-muted">Hitung omzet atribut ekstrakurikuler.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="export_rapat" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm fs-5">
                        <i class="fas fa-file-excel me-2"></i> DOWNLOAD DOKUMEN RAT LENGKAP
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>