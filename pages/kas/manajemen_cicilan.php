<?php
// --- PROSES BAYAR CICILAN ---
if(isset($_POST['bayar_cicilan'])){
    $id_cicilan = $_POST['id_cicilan'];
    $bayar      = (float) $_POST['jumlah_bayar'];
    $ket        = $_POST['keterangan'];
    $sisa_lama  = (float) $_POST['sisa_tagihan'];
    $nama_brg   = $_POST['nama_barang_hidden'];
    $nama_siswa = $_POST['nama_siswa_hidden'];
    $kelas_siswa= $_POST['kelas_hidden'];
    $kategori   = $_POST['kategori_hidden']; 
    $tanggal    = date('Y-m-d');
    $user_id    = $_SESSION['user']['id'];
    $kat_kas    = ($kategori == 'seragam') ? 'penjualan_seragam' : 'penjualan_eskul';

    if($bayar > $sisa_lama){
        setFlash('danger', 'Pembayaran melebihi sisa tagihan!');
    } else {
        try {
            $pdo->beginTransaction();
            $ket_transaksi = "Cicilan $nama_siswa ($kelas_siswa): $nama_brg - " . $ket;
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, ?, 'masuk', ?, ?, ?)")
                ->execute([$tanggal, $kat_kas, $bayar, $ket_transaksi, $user_id]);

            $sisa_baru = $sisa_lama - $bayar;
            $status = ($sisa_baru <= 0) ? 'lunas' : 'belum';
            $pdo->prepare("UPDATE cicilan SET terbayar = terbayar + ?, sisa = ?, status = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$bayar, $sisa_baru, $status, $id_cicilan]);

            $pdo->commit();
            // [FLASH MESSAGE + CETAK]
            setFlash('success', "Pembayaran Berhasil! <a href='pages/cetak_struk.php?id=$id_cicilan' target='_blank' class='btn btn-sm btn-light ms-2 text-dark fw-bold text-decoration-underline'><i class='fas fa-print'></i> Cetak Struk</a>");
            echo "<script>window.location='kas/manajemen_cicilan';</script>";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// --- LOGIKA TAMPILAN (VIEW) ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'tagihan'; // 'tagihan' atau 'rekap'
$list_kelas = $pdo->query("SELECT DISTINCT kelas FROM cicilan ORDER BY kelas ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Manajemen Piutang & Rekap</h2>
    </div>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm">
    <li class="nav-item">
        <a class="nav-link <?= $tab=='tagihan' ? 'active fw-bold' : '' ?>" href="kas/manajemen_cicilan?tab=tagihan">
            <i class="fas fa-hand-holding-usd me-2"></i> Pembayaran Cicilan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab=='rekap' ? 'active fw-bold' : '' ?>" href="kas/manajemen_cicilan?tab=rekap">
            <i class="fas fa-chart-pie me-2"></i> Rekapitulasi Kelas (Lunas)
        </a>
    </li>
</ul>

<?php if($tab == 'tagihan'): 
    $sql_tagihan = "SELECT * FROM cicilan WHERE status = 'belum' ORDER BY created_at DESC";
    $data_tagihan = $pdo->query($sql_tagihan)->fetchAll();
?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-file-invoice-dollar me-2"></i> Daftar Tagihan Aktif</h6>
            <small class="text-muted">Fokus penagihan siswa yang belum lunas</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Siswa & Kelas</th>
                        <th>Barang / Paket</th>
                        <th>Total</th>
                        <th>Terbayar</th>
                        <th class="text-danger fw-bold">Sisa</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_tagihan)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Hore! Tidak ada tagihan aktif (Semua Lunas).</td></tr>
                    <?php endif; 
                    foreach($data_tagihan as $row): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($row['kelas']) ?></span>
                        </td>
                        <td>
                            <span class="d-block text-dark"><?= htmlspecialchars($row['nama_barang']) ?></span>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?= $row['kategori_barang'] ?></small>
                        </td>
                        <td><?= formatRp($row['total_tagihan']) ?></td>
                        <td class="text-success"><?= formatRp($row['terbayar']) ?></td>
                        <td class="fw-bold text-danger fs-6"><?= formatRp($row['sisa']) ?></td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-primary shadow-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalBayar<?= $row['id'] ?>">
                                Bayar
                            </button>
                            <a href="pages/cetak_struk.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 ms-1" title="Cetak Kartu Hutang">
                                <i class="fas fa-print"></i>
                            </a>

                            <div class="modal fade" id="modalBayar<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                                                <h6 class="modal-title fw-bold">Input Pembayaran</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <input type="hidden" name="id_cicilan" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="sisa_tagihan" value="<?= $row['sisa'] ?>">
                                                <input type="hidden" name="nama_barang_hidden" value="<?= $row['nama_barang'] ?>">
                                                <input type="hidden" name="nama_siswa_hidden" value="<?= $row['nama_siswa'] ?>">
                                                <input type="hidden" name="kelas_hidden" value="<?= $row['kelas'] ?>">
                                                <input type="hidden" name="kategori_hidden" value="<?= $row['kategori_barang'] ?>">

                                                <div class="text-center mb-3">
                                                    <small class="text-muted d-block">Sisa Tagihan</small>
                                                    <h3 class="fw-bold text-danger"><?= formatRp($row['sisa']) ?></h3>
                                                    <div class="small text-dark mt-1"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted">Nominal Bayar</label>
                                                    <input type="number" name="jumlah_bayar" class="form-control fw-bold border-success text-center" required min="1000" max="<?= $row['sisa'] ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted">Catatan</label>
                                                    <input type="text" name="keterangan" class="form-control bg-light" placeholder="Cicilan ke-...">
                                                </div>
                                                
                                                <button type="submit" name="bayar_cicilan" class="btn btn-primary w-100 fw-bold py-2 rounded-pill">SIMPAN</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif($tab == 'rekap'): 
    $filter_kelas = $_GET['kelas'] ?? '';
    
    // Query Rekap Data
    $sql_rekap = "SELECT * FROM cicilan WHERE 1=1";
    if($filter_kelas) $sql_rekap .= " AND kelas = '$filter_kelas'";
    $sql_rekap .= " ORDER BY kelas ASC, nama_siswa ASC";
    $data_rekap = $pdo->query($sql_rekap)->fetchAll();

    $total_siswa = count($data_rekap);
    $total_lunas = 0;
    foreach($data_rekap as $d) { if($d['status'] == 'lunas') $total_lunas++; }
    $persen_lunas = $total_siswa > 0 ? round(($total_lunas / $total_siswa) * 100) : 0;
?>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light rounded-3 p-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="rekap"> <div class="col-auto fw-bold text-muted small">FILTER KELAS:</div>
                <div class="col-auto">
                    <select name="kelas" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                        <option value="">-- Tampilkan Semua --</option>
                        <?php foreach($list_kelas as $k): ?>
                        <option value="<?= $k['kelas'] ?>" <?= $filter_kelas == $k['kelas'] ? 'selected' : '' ?>><?= $k['kelas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-auto ms-auto">
                    <a href="process/export_cicilan.php?kelas_filter=<?= $filter_kelas ?>" target="_blank" class="btn btn-success shadow-sm btn-sm fw-bold">
                        <i class="fas fa-file-excel me-2"></i> Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if($filter_kelas): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white p-3 border-0 shadow-sm text-center">
                <small class="text-white-50 text-uppercase fw-bold">Total Siswa (<?= $filter_kelas ?>)</small>
                <h3 class="mb-0 fw-bold"><?= $total_siswa ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white p-3 border-0 shadow-sm text-center">
                <small class="text-white-50 text-uppercase fw-bold">Sudah Lunas</small>
                <h3 class="mb-0 fw-bold"><?= $total_lunas ?> <span class="fs-6 opacity-75">(<?= $persen_lunas ?>%)</span></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white p-3 border-0 shadow-sm text-center">
                <small class="text-white-50 text-uppercase fw-bold">Belum Lunas</small>
                <h3 class="mb-0 fw-bold"><?= $total_siswa - $total_lunas ?></h3>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-sm">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-4 py-3">Kelas</th>
                        <th class="py-3">Nama Siswa</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-end">Total Tagihan</th>
                        <th class="py-3 text-end">Sisa Hutang</th>
                        <th class="py-3 text-center pe-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_rekap)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                    <?php endif; 
                    $curr_kelas = '';
                    foreach($data_rekap as $row): 
                        $show_kelas = ($curr_kelas != $row['kelas']);
                        if($show_kelas) $curr_kelas = $row['kelas'];
                    ?>
                    <tr class="<?= $row['status']=='lunas' ? 'bg-success bg-opacity-10' : '' ?>">
                        <td class="ps-4 fw-bold text-primary">
                            <?= $show_kelas ? $row['kelas'] : '' ?>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                            <small class="d-block text-muted" style="font-size:0.7rem;"><?= $row['nama_barang'] ?></small>
                        </td>
                        <td>
                            <?php if($row['status'] == 'lunas'): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> LUNAS</span>
                            <?php else: ?>
                                <span class="badge bg-danger">HUTANG</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= formatRp($row['total_tagihan']) ?></td>
                        <td class="text-end fw-bold <?= $row['sisa']>0 ? 'text-danger' : 'text-muted' ?>">
                            <?= formatRp($row['sisa']) ?>
                        </td>
                        <td class="text-center pe-3 small text-muted">
                            <?= $row['status']=='lunas' ? 'Selesai' : 'Belum selesai' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>