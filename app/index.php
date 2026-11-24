<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'db-proxy';
$user = 'user_app';
$pass = 'password_app';
$db   = 'db_proyek';
$port = 6033;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$conn = null;
$error = '';

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset('utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'hapus') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM kamar_kos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            header('Location: /?msg=hapus');
            exit;
        }

        if ($action === 'tambah' || $action === 'ubah') {
            $nomor = trim($_POST['nomor_kamar'] ?? '');
            $tipe = trim($_POST['tipe_kamar'] ?? '');
            $harga = filter_var(
                $_POST['harga_per_bulan'] ?? '',
                FILTER_VALIDATE_INT
            );
            $status = $_POST['status'] ?? 'Tersedia';
            $keterangan = trim($_POST['keterangan'] ?? '');

            if (
                $nomor === '' ||
                $tipe === '' ||
                $harga === false ||
                $harga < 0 ||
                !in_array($status, ['Tersedia', 'Terisi'], true)
            ) {
                header('Location: /?msg=invalid');
                exit;
            }

            if ($action === 'tambah') {
                $stmt = $conn->prepare(
                    'INSERT INTO kamar_kos
                    (nomor_kamar, tipe_kamar, harga_per_bulan, status, keterangan)
                    VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'ssiss', $nomor, $tipe, $harga, $status, $keterangan
                );
                $stmt->execute();
                header('Location: /?msg=tambah');
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare(
                'UPDATE kamar_kos
                 SET nomor_kamar=?, tipe_kamar=?, harga_per_bulan=?,
                     status=?, keterangan=?
                 WHERE id=?'
            );
            $stmt->bind_param(
                'ssissi', $nomor, $tipe, $harga, $status, $keterangan, $id
            );
            $stmt->execute();
            header('Location: /?msg=ubah');
            exit;
        }
    }

    $editData = null;
    if (isset($_GET['edit'])) {
        $idEdit = (int)$_GET['edit'];
        $stmt = $conn->prepare('SELECT * FROM kamar_kos WHERE id = ?');
        $stmt->bind_param('i', $idEdit);
        $stmt->execute();
        $editData = $stmt->get_result()->fetch_assoc();
    }

    $result = $conn->query(
        'SELECT * FROM kamar_kos ORDER BY id DESC'
    );

    $totalKamar = (int)$conn->query(
        'SELECT COUNT(*) AS jumlah FROM kamar_kos'
    )->fetch_assoc()['jumlah'];

    $totalTersedia = (int)$conn->query(
        "SELECT COUNT(*) AS jumlah FROM kamar_kos WHERE status='Tersedia'"
    )->fetch_assoc()['jumlah'];

    $totalTerisi = (int)$conn->query(
        "SELECT COUNT(*) AS jumlah FROM kamar_kos WHERE status='Terisi'"
    )->fetch_assoc()['jumlah'];

} catch (Throwable $ex) {
    $error = 'Koneksi atau operasi database gagal. Periksa konfigurasi MySQL dan ProxySQL.';
}

$messages = [
    'tambah' => 'Data kamar berhasil ditambahkan.',
    'ubah' => 'Data kamar berhasil diperbarui.',
    'hapus' => 'Data kamar berhasil dihapus.',
    'invalid' => 'Data tidak valid. Periksa kembali formulir.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KosKita - Pengelolaan Kamar Kos</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f6fb;color:#202b3c;font-family:Arial,sans-serif}
header{background:#173b65;color:white;padding:25px 6%}
header h1{margin:0 0 8px;font-size:28px}
header p{margin:0;color:#d9e6f5}
main{max-width:1100px;margin:28px auto;padding:0 18px}
.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:22px}
.card,.panel{background:white;border-radius:12px;padding:20px;box-shadow:0 3px 12px #182c4610}
.card span{color:#65758b;font-size:14px}
.card strong{display:block;font-size:28px;margin-top:9px;color:#173b65}
.panel{margin-bottom:22px}
h2{font-size:20px;margin:0 0 18px}
.formgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
label{display:block;font-size:14px;font-weight:bold;margin-bottom:7px}
input,select,textarea{width:100%;padding:11px;border:1px solid #ccd5e0;border-radius:7px;font:inherit}
textarea{resize:vertical}
.full{grid-column:1/-1}
button,.btn{border:0;border-radius:7px;padding:10px 14px;font-weight:bold;cursor:pointer;text-decoration:none;display:inline-block;font-size:13px}
.primary{background:#1b65b9;color:white}
.secondary{background:#e6edf5;color:#263c55}
.danger{background:#fde8e8;color:#a52323}
.success{background:#e4f7ec;color:#17683b;padding:12px;border-radius:8px;margin-bottom:15px}
.error{background:#fff0e7;color:#963d12;padding:12px;border-radius:8px;margin-bottom:15px}
.tablewrap{overflow-x:auto}
table{border-collapse:collapse;width:100%;font-size:14px}
th,td{text-align:left;padding:12px;border-bottom:1px solid #e7ecf2;vertical-align:top}
th{background:#f7f9fc;color:#526277}
.status{display:inline-block;padding:5px 8px;border-radius:20px;font-size:12px}
.tersedia{background:#e4f7ec;color:#17683b}
.terisi{background:#fff0d7;color:#8b5700}
.actions{display:flex;gap:7px;flex-wrap:wrap}
.empty{text-align:center;padding:28px;color:#65758b}
footer{text-align:center;padding:20px;color:#65758b;font-size:13px}
@media(max-width:650px){.cards{grid-template-columns:1fr}.formgrid{grid-template-columns:1fr}.full{grid-column:auto}header{padding:22px}}
</style>
</head>
<body>
<header>
    <h1>KosKita</h1>
    <p>Sistem Pengelolaan Kamar Kos · Cloud Computing</p>
</header>

<main>
<?php if ($error): ?>
    <div class="error"><?= e($error) ?></div>
<?php else: ?>

    <?php if ($msg): ?>
        <div class="success"><?= e($msg) ?></div>
    <?php endif; ?>

    <section class="cards">
        <div class="card"><span>Total Kamar</span><strong><?= $totalKamar ?></strong></div>
        <div class="card"><span>Kamar Tersedia</span><strong><?= $totalTersedia ?></strong></div>
        <div class="card"><span>Kamar Terisi</span><strong><?= $totalTerisi ?></strong></div>
    </section>

    <section class="panel">
        <h2><?= $editData ? 'Ubah Data Kamar' : 'Tambah Kamar Baru' ?></h2>
        <form method="post" action="/">
            <input type="hidden" name="action" value="<?= $editData ? 'ubah' : 'tambah' ?>">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">
            <?php endif; ?>

            <div class="formgrid">
                <div>
                    <label for="nomor_kamar">Nomor Kamar</label>
                    <input id="nomor_kamar" name="nomor_kamar" required maxlength="20"
                        placeholder="Contoh: A01"
                        value="<?= e($editData['nomor_kamar'] ?? '') ?>">
                </div>
                <div>
                    <label for="tipe_kamar">Tipe Kamar</label>
                    <input id="tipe_kamar" name="tipe_kamar" required maxlength="100"
                        placeholder="Contoh: Kamar standar"
                        value="<?= e($editData['tipe_kamar'] ?? '') ?>">
                </div>
                <div>
                    <label for="harga_per_bulan">Harga per Bulan (Rp)</label>
                    <input id="harga_per_bulan" type="number" name="harga_per_bulan"
                        min="0" required placeholder="Contoh: 500000"
                        value="<?= e($editData['harga_per_bulan'] ?? '') ?>">
                </div>
                <div>
                    <label for="status">Status Kamar</label>
                    <select id="status" name="status">
                        <?php foreach (['Tersedia','Terisi'] as $s): ?>
                            <option value="<?= e($s) ?>"
                                <?= ($editData['status'] ?? 'Tersedia') === $s ? 'selected' : '' ?>>
                                <?= e($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="2"
                        placeholder="Fasilitas atau catatan kamar"><?= e($editData['keterangan'] ?? '') ?></textarea>
                </div>
                <div class="full actions">
                    <button class="primary" type="submit">
                        <?= $editData ? 'Simpan Perubahan' : 'Tambah Kamar' ?>
                    </button>
                    <?php if ($editData): ?>
                        <a class="btn secondary" href="/">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Kamar Kos</h2>
        <div class="tablewrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th><th>Kamar</th><th>Harga/Bulan</th>
                    <th>Status</th><th>Keterangan</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="6" class="empty">Belum ada data kamar. Tambahkan kamar melalui formulir di atas.</td></tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><strong><?= e($row['nomor_kamar']) ?></strong><br><?= e($row['tipe_kamar']) ?></td>
                    <td>Rp <?= number_format((int)$row['harga_per_bulan'], 0, ',', '.') ?></td>
                    <td><span class="status <?= $row['status'] === 'Tersedia' ? 'tersedia' : 'terisi' ?>"><?= e($row['status']) ?></span></td>
                    <td><?= e($row['keterangan'] ?? '') ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn secondary" href="/?edit=<?= (int)$row['id'] ?>">Ubah</a>
                            <form method="post" action="/" onsubmit="return confirm('Yakin ingin menghapus kamar ini?')">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <section class="panel">
        <strong>Status Server:</strong> Aktif di Docker Container<br><br>
        <strong>Status Database:</strong>
        <span style="color:#17683b">Terhubung melalui ProxySQL</span>
    </section>
<?php endif; ?>
</main>
<footer>KosKita · Proyek Cloud Computing</footer>
</body>
</html>
