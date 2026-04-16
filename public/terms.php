<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/layout.php';

layout_head('Terma & Syarat — ' . APP_NAME);
layout_header();
?>
<div style="max-width:800px;margin:0 auto;padding:40px 16px;">
  <h1 style="font-size:1.8rem;font-weight:800;color:var(--kasih-dark);margin-bottom:8px;">Terma &amp; Syarat Penggunaan</h1>
  <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:32px;">Kemaskini terakhir: 1 Januari 2025</p>

  <div style="background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.08);line-height:1.8;color:#374151;">

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">1. Penerimaan Terma</h2>
    <p style="margin-bottom:20px;">Dengan menggunakan platform Kasih Gold Easy ("Platform"), anda bersetuju untuk mematuhi terma dan syarat yang dinyatakan di sini. Jika anda tidak bersetuju, sila hentikan penggunaan Platform ini.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">2. Perkhidmatan</h2>
    <p style="margin-bottom:20px;">Kasih Gold Easy menyediakan platform simpanan emas digital yang membolehkan pengguna:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li>Membeli dan menyimpan mata emas (gold points)</li>
      <li>Memindahkan mata emas kepada pengguna lain</li>
      <li>Membeli produk dan perkhidmatan di marketplace</li>
      <li>Menyertai program simpanan dan kempen</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">3. Kelayakan Akaun</h2>
    <p style="margin-bottom:20px;">Anda mestilah berumur sekurang-kurangnya 18 tahun untuk mendaftar. Setiap individu hanya dibenarkan satu akaun. Anda bertanggungjawab menjaga kerahsiaan kata laluan akaun anda.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">4. Mata Emas (Gold Points)</h2>
    <p style="margin-bottom:20px;">Mata emas adalah unit simpanan digital dalam Platform ini. Formula pengiraan: <strong>1 mata = 0.01 gram emas</strong>. Harga emas ditetapkan oleh pentadbir sistem dan boleh berubah. Nilai mata emas adalah berdasarkan harga emas semasa pada masa transaksi. Pindahan mata emas adalah muktamad dan tidak boleh dibatalkan.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">5. Pembayaran</h2>
    <p style="margin-bottom:20px;">Semua pembayaran diproses melalui kaedah yang disokong oleh Platform. Bayaran balik hanya dipertimbangkan dalam kes ralat teknikal yang disahkan. Platform berhak menolak atau membatalkan transaksi yang mencurigakan.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">6. Program Rujukan</h2>
    <p style="margin-bottom:20px;">Komisen rujukan diberikan secara automatik apabila ahli yang anda rujuk membuat pembelian. Kadar komisen boleh berubah mengikut budi bicara pihak pengurusan. Komisen yang diperoleh melalui cara penipuan akan dibatalkan.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">7. Marketplace</h2>
    <p style="margin-bottom:20px;">Platform bertindak sebagai perantara antara pedagang dan pembeli. Kami tidak menjamin kualiti produk yang dijual oleh pedagang pihak ketiga. Sebarang pertikaian antara pembeli dan pedagang perlu diselesaikan secara terus.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">8. Larangan</h2>
    <p style="margin-bottom:12px;">Pengguna dilarang daripada:</p>
    <ul style="margin:0 0 20px;padding-left:24px;">
      <li>Menggunakan Platform untuk aktiviti haram atau penipuan</li>
      <li>Menjual atau memindahkan akaun kepada pihak lain</li>
      <li>Menggunakan bot atau automasi tanpa kebenaran</li>
      <li>Mengganggu atau merosakkan sistem Platform</li>
    </ul>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">9. Penggantungan Akaun</h2>
    <p style="margin-bottom:20px;">Platform berhak menggantung atau menamatkan akaun yang melanggar terma ini tanpa notis awal. Mata emas dalam akaun yang digantung atas sebab penipuan tidak akan dikembalikan.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">10. Had Liabiliti</h2>
    <p style="margin-bottom:20px;">Platform tidak bertanggungjawab atas sebarang kerugian yang berpunca daripada turun naik harga emas, gangguan teknikal, atau tindakan pengguna sendiri.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">11. Perubahan Terma</h2>
    <p style="margin-bottom:20px;">Kami berhak mengubah terma ini pada bila-bila masa. Pengguna akan dimaklumkan melalui e-mel atau notifikasi dalam Platform. Penggunaan berterusan selepas perubahan dianggap sebagai penerimaan terma baharu.</p>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--kasih-dark);margin:0 0 12px;">12. Undang-Undang Berkenaan</h2>
    <p style="margin-bottom:0;">Terma ini dikawal oleh undang-undang Malaysia. Sebarang pertikaian tertakluk kepada bidang kuasa mahkamah Malaysia.</p>
  </div>

  <div style="text-align:center;margin-top:32px;">
    <a href="<?= APP_URL ?>/register" class="btn-gold" style="display:inline-block;">Daftar Sekarang</a>
    <a href="<?= APP_URL ?>/privacy" class="btn-gold-outline" style="display:inline-block;margin-left:10px;">Dasar Privasi</a>
  </div>
</div>
<?php layout_footer(); ?>
