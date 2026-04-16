<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/layout.php';

layout_head('Dasar Privasi — ' . APP_NAME);
layout_header();
?>
<div style="max-width:800px;margin:0 auto;padding:40px 16px;">
  <h1 style="font-size:1.8rem;font-weight:800;color:var(--kasih-dark);margin-bottom:8px;">Dasar Privasi</h1>
  <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:32px;">Kemaskini terakhir: 1 Januari 2025</p>

  <div style="background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.08);line-height:1.8;color:#374151;">

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">1. Pengenalan</h2>
    <p style="margin-bottom:20px;">Kasih Gold Easy ("kami", "Platform") komited untuk melindungi privasi anda. Dasar Privasi ini menerangkan bagaimana kami mengumpul, menggunakan, dan melindungi maklumat peribadi anda selaras dengan <strong>Akta Perlindungan Data Peribadi 2010 (PDPA)</strong> Malaysia.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">2. Maklumat Yang Dikumpul</h2>
    <p style="margin-bottom:12px;">Kami mengumpul maklumat berikut semasa pendaftaran dan penggunaan:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li><strong>Maklumat peribadi:</strong> Nama penuh, alamat e-mel, nombor telefon</li>
      <li><strong>Maklumat transaksi:</strong> Sejarah pembelian, pindahan, baki dompet</li>
      <li><strong>Maklumat teknikal:</strong> Alamat IP, jenis pelayar, log akses</li>
      <li><strong>Kandungan yang dimuat naik:</strong> Bukti pembayaran, gambar profil</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">3. Tujuan Pengumpulan Data</h2>
    <p style="margin-bottom:12px;">Maklumat anda digunakan untuk:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li>Mengurus dan mengesahkan akaun anda</li>
      <li>Memproses transaksi dan pembayaran</li>
      <li>Memaklumkan anda tentang transaksi dan kemaskini penting</li>
      <li>Mematuhi keperluan undang-undang dan pencegahan penipuan</li>
      <li>Meningkatkan perkhidmatan Platform</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">4. Perkongsian Maklumat</h2>
    <p style="margin-bottom:20px;">Kami <strong>tidak menjual</strong> maklumat peribadi anda kepada pihak ketiga. Kami berkongsi data hanya dengan:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li>Pedagang yang terlibat dalam transaksi anda (maklumat penghantaran sahaja)</li>
      <li>Penyedia perkhidmatan teknikal (hosting, e-mel) yang terikat dengan perjanjian kerahsiaan</li>
      <li>Pihak berkuasa apabila dikehendaki oleh undang-undang</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">5. Keselamatan Data</h2>
    <p style="margin-bottom:20px;">Kami melaksanakan langkah-langkah keselamatan berikut:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li>Kata laluan disimpan dalam bentuk hash (bcrypt) — tidak boleh dibaca</li>
      <li>Sambungan HTTPS/SSL untuk semua komunikasi</li>
      <li>Kawalan akses berasaskan peranan (RBAC)</li>
      <li>Log audit untuk setiap tindakan sensitif</li>
      <li>Sandaran data berkala</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">6. Hak Anda</h2>
    <p style="margin-bottom:12px;">Di bawah PDPA, anda berhak untuk:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li><strong>Akses:</strong> Meminta salinan data peribadi anda</li>
      <li><strong>Pembetulan:</strong> Mengemaskini data yang tidak tepat</li>
      <li><strong>Pemadaman:</strong> Meminta pemadaman akaun dan data (tertakluk kepada keperluan undang-undang)</li>
      <li><strong>Bantahan:</strong> Membantah pemprosesan data untuk tujuan tertentu</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">7. Kuki (Cookies)</h2>
    <p style="margin-bottom:20px;">Platform menggunakan sesi PHP (bukan kuki pihak ketiga) untuk mengekalkan sesi log masuk anda. Tiada kuki penjejakan pihak ketiga digunakan.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">8. Pengekalan Data</h2>
    <p style="margin-bottom:20px;">Data akaun aktif disimpan selama akaun anda aktif. Selepas penutupan akaun, data transaksi disimpan selama 7 tahun untuk tujuan pematuhan undang-undang. Log audit disimpan selama 2 tahun.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">9. Kanak-Kanak</h2>
    <p style="margin-bottom:20px;">Platform ini tidak ditujukan kepada individu di bawah umur 18 tahun. Kami tidak dengan sengaja mengumpul data peribadi kanak-kanak.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">10. Hubungi Kami</h2>
    <p style="margin-bottom:0;">Untuk sebarang pertanyaan atau permintaan berkaitan privasi, hubungi kami di:
      <br><strong>E-mel:</strong> privacy@kasihgold.my
      <br><strong>WhatsApp:</strong> <?= h(get_setting('support_whatsapp', '+60123456789')) ?>
    </p>
  </div>

  <div style="text-align:center;margin-top:32px;">
    <a href="<?= APP_URL ?>/terms" class="btn-gold-outline" style="display:inline-block;">Terma &amp; Syarat</a>
    <a href="<?= APP_URL ?>/register" class="btn-gold" style="display:inline-block;margin-left:10px;">Daftar Sekarang</a>
  </div>
</div>
<?php layout_footer(); ?>
