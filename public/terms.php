<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/layout.php';

layout_head('Terma & Syarat — ' . APP_NAME);
layout_header();
?>
<div style="max-width:820px;margin:0 auto;padding:40px 16px 60px;">

  <h1 style="font-size:1.9rem;font-weight:800;color:var(--kasih-dark);margin-bottom:6px;">Terma &amp; Syarat Penggunaan</h1>
  <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:6px;">Berkuat kuasa: 1 April 2025 &nbsp;·&nbsp; Kemaskini terakhir: <?= date('d F Y') ?></p>
  <p style="color:#6B7280;font-size:0.875rem;margin-bottom:32px;">
    Sila baca terma ini dengan teliti sebelum menggunakan platform Kasih Gold Easy.
    Dengan mendaftar atau menggunakan platform ini, anda bersetuju terikat dengan terma berikut.
  </p>

  <!-- Vault ownership anchor box -->
  <div style="background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:2px solid #F59E0B;border-radius:12px;padding:20px 24px;margin-bottom:32px;">
    <div style="font-weight:800;color:#92400E;font-size:1rem;margin-bottom:10px;">🔒 Pernyataan Penting — Pemilikan &amp; Simpanan Emas</div>
    <p style="margin:0 0 8px;color:#374151;font-size:0.9rem;line-height:1.7;">
      <strong>Kasih Gold Easy bertindak sebagai penjaga amanah (<em>custodian</em>) bagi emas fizikal yang dibeli daripada kami.</strong>
      Pelanggan mengekalkan pemilikan penuh ke atas emas mereka pada setiap masa.
      Perkhidmatan simpanan dalam peti besi adalah <strong>PERCUMA</strong> sebagai kemudahan kepada pelanggan.
      <strong>Tiada pulangan dijamin.</strong> Risiko turun naik harga emas ditanggung sepenuhnya oleh pelanggan.
      Peti besi diinsuranskan sehingga <strong>RM10,000,000</strong> dan tertakluk kepada audit bebas.
    </p>
    <p style="margin:0;font-size:0.78rem;color:#92400E;font-style:italic;">
      Kasih Gold Easy berdaftar di bawah Suruhanjaya Syarikat Malaysia (SSM) dan beroperasi sebagai perniagaan runcit emas.
      Platform ini bukan skim pelaburan, bukan produk deposit, dan bukan dikawal oleh Suruhanjaya Sekuriti (SC) atau Bank Negara Malaysia (BNM).
    </p>
  </div>

  <div style="background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 4px rgba(0,0,0,0.07);line-height:1.8;color:#374151;">

    <?php
    $h2 = 'font-size:1.05rem;font-weight:700;color:var(--kasih-dark);margin:28px 0 10px;padding-bottom:6px;border-bottom:1px solid #F3F4F6;';
    $p  = 'margin-bottom:16px;';
    $ul = 'margin:0 0 16px;padding-left:22px;';
    ?>

    <h2 style="<?= $h2 ?>margin-top:0;">1. Pihak Berkenaan</h2>
    <p style="<?= $p ?>">
      "Kasih Gold Easy", "kami", "platform" merujuk kepada syarikat yang mendaftar dan mengoperasikan platform ini di bawah SSM Malaysia.
      "Pengguna", "anda", "ahli" merujuk kepada individu yang mendaftar akaun dan menggunakan platform ini.
    </p>

    <h2 style="<?= $h2 ?>">2. Perkhidmatan Platform</h2>
    <p style="<?= $p ?>">Kasih Gold Easy menyediakan perkhidmatan berikut:</p>
    <ul style="<?= $ul ?>">
      <li><strong>Pembelian Emas Digital</strong> — Beli emas pada harga pasaran semasa, disimpan sebagai Gold Points (1 mata = 0.01 gram emas 999).</li>
      <li><strong>Simpanan Peti Besi Percuma</strong> — Emas fizikal anda disimpan secara percuma dalam peti besi berinsurens yang diaudit.</li>
      <li><strong>Pengeluaran Emas Fizikal</strong> — Tukar Gold Points kepada plat emas fizikal 999 (minimum 0.2 gram = 1 plat) untuk dikutip di pejabat kami.</li>
      <li><strong>Jual Balik Emas</strong> — Jual emas anda semula kepada kami pada harga belian harian yang ditetapkan. Bayaran ke akaun bank anda dalam 3–5 hari bekerja.</li>
      <li><strong>Kasih Ar Rahnu</strong> — Program gadaian emas secara Islam. Dapatkan pembiayaan sehingga 70% daripada nilai pasaran emas anda. Yuran ujrah dikenakan bulanan — tiada faedah riba. Patuh Syariah.</li>
      <li><strong>Pindahan Gold Points</strong> — Pindah Gold Points kepada ahli lain. Pindahan adalah muktamad.</li>
      <li><strong>Pasaran Maya</strong> — Tebus Gold Points untuk produk dan perkhidmatan daripada pedagang berdaftar.</li>
      <li><strong>Program Kempen</strong> — Simpanan bersasar dengan matlamat yang ditetapkan sendiri.</li>
      <li><strong>Program Rujukan</strong> — Jana komisen dengan merujuk ahli baharu.</li>
    </ul>

    <h2 style="<?= $h2 ?>">3. Pemilikan Emas &amp; Peranan Penjaga Amanah</h2>
    <p style="<?= $p ?>">
      <strong>Anda adalah pemilik penuh emas yang dibeli melalui platform ini.</strong>
      Kasih Gold Easy bertindak sebagai penjaga amanah (<em>custodian</em>) sahaja — kami menyimpan emas bagi pihak anda tetapi tidak memilikinya.
      Simpanan peti besi adalah perkhidmatan percuma, bukan produk kewangan, bukan skim deposit, dan bukan skim pelaburan kolektif.
    </p>
    <p style="<?= $p ?>">
      Emas yang disimpan oleh semua pelanggan <strong>diasingkan sepenuhnya daripada aset syarikat</strong>.
      Sekiranya syarikat mengalami sebarang kesulitan kewangan, emas pelanggan tidak boleh digunakan untuk menyelesaikan hutang syarikat.
    </p>

    <h2 style="<?= $h2 ?>">4. Tiada Jaminan Pulangan</h2>
    <p style="<?= $p ?>">
      Harga emas adalah berdasarkan pasaran dan boleh berubah-ubah. <strong>Kasih Gold Easy tidak membuat sebarang jaminan, ramalan, atau representasi berkenaan nilai emas pada masa hadapan.</strong>
      Risiko turun naik harga emas ditanggung sepenuhnya oleh pelanggan. Platform ini bukan pelaburan berkaitan sekuriti dan tidak dikawal oleh Suruhanjaya Sekuriti (SC).
    </p>

    <h2 style="<?= $h2 ?>">5. Gold Points (Mata Emas Digital)</h2>
    <p style="<?= $p ?>">
      Gold Points adalah unit perwakilan digital bagi emas fizikal dalam platform. Formula: <strong>100 mata = 1 gram emas 999</strong>.
      Harga emas (RM per gram) ditetapkan oleh pentadbir mengikut harga pasaran dan boleh berubah.
      Nilai Gold Points anda pada sebarang masa = (jumlah mata ÷ 100) × harga emas semasa.
      Pindahan Gold Points antara ahli adalah muktamad dan tidak boleh dibatalkan.
    </p>

    <h2 style="<?= $h2 ?>">6. Pengeluaran Emas Fizikal</h2>
    <p style="<?= $p ?>">
      Ahli yang memiliki sekurang-kurangnya 20 Gold Points (0.2 gram) boleh memohon pengeluaran plat emas fizikal 999.
      Gold Points akan ditolak serta-merta apabila permohonan dihantar.
      Plat akan disediakan dalam 3–7 hari bekerja selepas kelulusan admin.
      Pengambilan di pejabat memerlukan pengesahan identiti (IC asal).
      Jika permohonan ditolak, Gold Points akan dikembalikan kepada baki anda.
    </p>

    <h2 style="<?= $h2 ?>">7. Program Jual Balik Emas</h2>
    <p style="<?= $p ?>">
      Anda boleh menjual semula emas kepada Kasih Gold Easy pada harga belian harian yang ditetapkan oleh admin.
      Harga belian adalah lebih rendah daripada harga jualan semasa (spread berlaku).
      Gold Points akan ditolak serta-merta apabila permohonan jual dihantar.
      Pembayaran ke akaun bank yang didaftarkan dalam 3–5 hari bekerja selepas kelulusan.
      Jika permohonan ditolak, Gold Points akan dikembalikan.
    </p>

    <h2 style="<?= $h2 ?>">8. Kasih Ar Rahnu (Gadaian Emas Islam)</h2>
    <p style="<?= $p ?>">
      Program Ar Rahnu membolehkan anda mendapatkan pembiayaan tunai dengan menggadaikan emas digital anda kepada Kasih Gold Easy.
      Pembiayaan adalah sehingga 70% daripada nilai pasaran emas yang digadaikan.
      Yuran ujrah (caj simpanan) dikenakan bulanan mengikut kadar yang dipersetujui dan <strong>bukan faedah (riba)</strong>.
      Emas yang digadaikan kekal sebagai hak milik anda selagi pembiayaan belum tamat tempoh atau dilunaskan.
      Jika pembiayaan tidak dilunaskan selepas tamat tempoh, kami berhak menyelesaikan hutang daripada nilai emas yang digadaikan.
      Program ini distrukturkan mengikut prinsip Syariah dan disemak oleh penasihat Syariah bertauliah.
    </p>

    <h2 style="<?= $h2 ?>">9. Keselamatan &amp; Insurans Peti Besi</h2>
    <p style="<?= $p ?>">
      Emas pelanggan disimpan dalam peti besi bertauliah yang:
    </p>
    <ul style="<?= $ul ?>">
      <li>Diinsuranskan sehingga <strong>RM10,000,000</strong> terhadap kecurian, kebakaran, dan bencana alam</li>
      <li>Diaudit secara bebas untuk mengesahkan stok fizikal sepadan dengan rekod digital</li>
      <li>Dilengkapi dengan sistem keselamatan berlapis (CCTV, kawalan akses, kawalan iklim)</li>
      <li>Sijil insurans dan laporan audit tersedia atas permintaan</li>
    </ul>

    <h2 style="<?= $h2 ?>">10. Kelayakan Akaun &amp; eKYC</h2>
    <p style="<?= $p ?>">
      Anda mesti berumur sekurang-kurangnya <strong>18 tahun</strong> dan warganegara atau pemastautin tetap Malaysia untuk mendaftar.
      Setiap individu hanya dibenarkan <strong>satu akaun</strong>.
      Pengesahan identiti (eKYC) diperlukan untuk mengakses ciri pengeluaran emas fizikal, jual balik, dan Ar Rahnu.
      Anda bertanggungjawab memastikan maklumat yang diberikan adalah tepat dan terkini.
    </p>

    <h2 style="<?= $h2 ?>">11. Program Rujukan</h2>
    <p style="<?= $p ?>">
      Komisen rujukan diberikan secara automatik apabila ahli yang anda rujuk membuat pembelian emas yang sah.
      Kadar dan struktur komisen boleh berubah mengikut budi bicara pihak pengurusan dengan notis 7 hari.
      Komisen yang diperoleh melalui cara penipuan, akaun palsu, atau salah guna akan dibatalkan dan akaun berkenaan akan digantung.
    </p>

    <h2 style="<?= $h2 ?>">12. Marketplace (Pasaran Maya)</h2>
    <p style="<?= $p ?>">
      Platform bertindak sebagai perantara teknikal antara pedagang dan pembeli.
      Pedagang adalah pihak ketiga bebas yang bertanggungjawab ke atas produk dan perkhidmatan mereka.
      Kami tidak menjamin kualiti, penghantaran, atau kepuasan berkenaan produk pedagang.
      Sebarang pertikaian antara pembeli dan pedagang perlu diselesaikan secara langsung; platform boleh membantu sebagai orang tengah atas permintaan.
    </p>

    <h2 style="<?= $h2 ?>">13. Bayaran &amp; Bayaran Balik</h2>
    <p style="<?= $p ?>">
      Semua pembelian emas adalah <strong>muktamad</strong> setelah pembayaran disahkan. Tiada bayaran balik untuk pembelian emas biasa.
      Bayaran balik hanya dipertimbangkan dalam kes ralat teknikal yang dapat disahkan oleh sistem.
      Untuk perkhidmatan jual balik, sila gunakan fungsi "Jual Emas" dalam platform.
      Platform berhak menolak atau menangguhkan transaksi yang mencurigakan atau melanggar terma ini.
    </p>

    <h2 style="<?= $h2 ?>">14. Perlindungan Data (PDPA 2010)</h2>
    <p style="<?= $p ?>">
      Kami mengumpul dan memproses data peribadi anda (termasuk imej IC untuk eKYC) semata-mata untuk tujuan pengesahan identiti, pematuhan undang-undang, dan operasi perkhidmatan.
      Data anda tidak dijual atau dikongsi dengan pihak ketiga tanpa persetujuan anda, kecuali seperti yang dikehendaki oleh undang-undang.
      Anda berhak meminta akses, pembetulan, atau pemadaman data anda. Sila rujuk <a href="<?= APP_URL ?>/privacy" style="color:var(--gold-dark);">Dasar Privasi</a> kami untuk maklumat lanjut.
    </p>

    <h2 style="<?= $h2 ?>">15. Larangan Penggunaan</h2>
    <p style="<?= $p ?>">Pengguna dilarang daripada:</p>
    <ul style="<?= $ul ?>">
      <li>Menggunakan platform untuk aktiviti pengubahan wang haram (AML), penipuan, atau aktiviti haram</li>
      <li>Mendaftar lebih daripada satu akaun atau akaun bagi pihak orang lain tanpa kebenaran bertulis</li>
      <li>Menjual, memindahkan, atau menyewakan akaun kepada pihak lain</li>
      <li>Menggunakan bot, skrip automatik, atau alat scraping tanpa kebenaran bertulis</li>
      <li>Mengganggu, merosakkan, atau cuba mengeksploit sistem platform</li>
      <li>Menyalahgunakan program rujukan dengan akaun palsu atau transaksi rekaan</li>
    </ul>

    <h2 style="<?= $h2 ?>">16. Penggantungan &amp; Penamatan Akaun</h2>
    <p style="<?= $p ?>">
      Kami berhak menggantung atau menamatkan akaun yang melanggar terma ini, dengan atau tanpa notis awal, bergantung kepada keseriusan pelanggaran.
      Sebelum penamatan, kami akan cuba menyelesaikan sebarang Gold Points yang sah melalui proses jual balik.
      Gold Points dalam akaun yang digantung atas sebab penipuan atau aktiviti haram tidak akan dikembalikan.
    </p>

    <h2 style="<?= $h2 ?>">17. Had Liabiliti</h2>
    <p style="<?= $p ?>">
      Setakat yang dibenarkan oleh undang-undang, Kasih Gold Easy tidak bertanggungjawab atas:
    </p>
    <ul style="<?= $ul ?>">
      <li>Kerugian daripada turun naik harga emas pasaran</li>
      <li>Gangguan teknikal sementara di luar kawalan kami (force majeure, bencana)</li>
      <li>Kerugian akibat kelalaian pengguna (kata laluan dikongsi, akaun diakses oleh pihak ketiga)</li>
      <li>Tindakan atau peninggalan pedagang pihak ketiga di marketplace</li>
    </ul>
    <p style="<?= $p ?>">
      Tanggungjawab maksimum kami dalam apa-apa keadaan adalah terhad kepada nilai emas yang dipegang bagi pihak anda pada masa tuntutan.
    </p>

    <h2 style="<?= $h2 ?>">18. Perubahan Terma</h2>
    <p style="<?= $p ?>">
      Kami berhak mengubah terma ini pada bila-bila masa dengan notis sekurang-kurangnya <strong>14 hari</strong> melalui e-mel berdaftar atau notifikasi dalam platform.
      Penggunaan berterusan selepas tarikh berkuat kuasa dianggap sebagai penerimaan terma baharu.
      Sekiranya anda tidak bersetuju dengan perubahan, sila tutup akaun anda sebelum tarikh berkuat kuasa.
    </p>

    <h2 style="<?= $h2 ?>">19. Undang-Undang &amp; Bidang Kuasa</h2>
    <p style="margin-bottom:0;">
      Terma ini dikawal sepenuhnya oleh undang-undang Malaysia.
      Kasih Gold Easy berdaftar dan beroperasi di Malaysia di bawah Suruhanjaya Syarikat Malaysia (SSM).
      Sebarang pertikaian yang tidak dapat diselesaikan secara muafakat tertakluk kepada bidang kuasa eksklusif mahkamah Malaysia.
      Bagi isu berkaitan pematuhan Syariah dalam program Ar Rahnu, rujukan boleh dibuat kepada Majlis Penasihat Syariah yang dilantik.
    </p>

  </div>

  <!-- Contact -->
  <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:20px 24px;margin-top:24px;text-align:center;">
    <div style="font-weight:700;color:var(--kasih-dark);margin-bottom:6px;">Ada soalan berkenaan Terma ini?</div>
    <p style="color:#6B7280;font-size:0.875rem;margin:0 0 14px;">Hubungi pasukan kami dan kami akan membantu.</p>
    <a href="<?= APP_URL ?>/register" class="btn-gold" style="display:inline-block;">Daftar Akaun</a>
    <a href="<?= APP_URL ?>/privacy" class="btn-gold-outline" style="display:inline-block;margin-left:10px;">Dasar Privasi</a>
  </div>

</div>
<?php layout_footer(); ?>
