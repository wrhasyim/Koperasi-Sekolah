<?php
// PROSES GANTI PASSWORD
if(isset($_POST['ganti_password'])){
    $pass_lama = $_POST['pass_lama'];
    $pass_baru = $_POST['pass_baru'];
    $konfirmasi = $_POST['konfirmasi'];
    $id_user = $_SESSION['user']['id'];

    // Ambil data user saat ini
    $stmt = $pdo->prepare("SELECT password FROM anggota WHERE id = ?");
    $stmt->execute([$id_user]);
    $user_db = $stmt->fetch();

    if(password_verify($pass_lama, $user_db['password'])){
        if($pass_baru == $konfirmasi){
            // Update Password Baru
            $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE anggota SET password = ? WHERE id = ?");
            $update->execute([$hash_baru, $id_user]);
            
            echo "<script>alert('Password Berhasil Diubah! Silakan Login Ulang.'); window.location='process/auth_logout.php';</script>";
        } else {
            echo "<script>alert('Password Baru dan Konfirmasi Tidak Cocok!');</script>";
        }
    } else {
        echo "<script>alert('Password Lama Salah!');</script>";
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Profil Saya</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-user-shield"></i> Ganti Password
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" class="form-control" value="<?= $_SESSION['user']['nama'] ?>" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label>Password Lama</label>
                        <input type="password" name="pass_lama" class="form-control" required>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label>Password Baru</label>
                        <input type="password" name="pass_baru" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Ulangi Password Baru</label>
                        <input type="password" name="konfirmasi" class="form-control" required>
                    </div>
                    <button type="submit" name="ganti_password" class="btn btn-primary w-100">Simpan Password Baru</button>
                </form>
            </div>
        </div>
    </div>
</div>