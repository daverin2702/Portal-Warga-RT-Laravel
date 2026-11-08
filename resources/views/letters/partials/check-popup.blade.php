<!-- Pop-up cek keaslian surat -->
<button class="cek-fab" id="cekFab" aria-label="Cek keaslian surat"><i class="bi bi-qr-code-scan"></i><span>Cek Surat</span></button>
<div class="cek-pop d-none" id="cekPop" role="dialog" aria-label="Cek keaslian surat">
  <div class="chat-head"><strong class="flex-grow-1"><i class="bi bi-patch-check me-1"></i>Cek keaslian surat</strong><button class="btn btn-sm text-white" id="cekClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button></div>
  <div class="p-3">
    <ul class="nav nav-pills nav-fill mb-3 small"><li class="nav-item"><button class="nav-link active" data-cek="scan"><i class="bi bi-camera me-1"></i>Scan QR</button></li><li class="nav-item"><button class="nav-link" data-cek="manual"><i class="bi bi-keyboard me-1"></i>Input manual</button></li></ul>
    <div id="cekScan">
      <div id="cekReader" class="cek-reader"><div class="text-center text-secondary small p-4"><i class="bi bi-qr-code fs-1 d-block mb-2"></i>Arahkan kamera ke kode QR di bagian tanda tangan surat.</div></div>
      <div class="d-flex gap-2 mt-2"><button class="btn btn-primary flex-grow-1" id="cekMulai"><i class="bi bi-camera-video me-1"></i>Nyalakan kamera</button>
        <label class="btn btn-light" title="Pindai dari foto"><i class="bi bi-image me-1"></i>Foto<input type="file" accept="image/*" id="cekFoto" hidden></label></div>
      <select class="form-select form-select-sm mt-2 d-none" id="cekKamera" aria-label="Pilih kamera"></select>
      <div class="form-text">Kamera hanya berfungsi di alamat https atau localhost. Tidak ada kamera? Pilih foto kode QR atau pakai Input manual.</div>
    </div>
    <form id="cekManual" class="d-none">
      <label class="form-label">Nomor surat</label><input class="form-control mb-2" id="cekNomor" placeholder="mis. 001/RT.05/RW.02/XI/2026" required>
      <label class="form-label">Tanggal pembuatan surat</label><input type="date" class="form-control mb-3" id="cekTgl" required>
      <button class="btn btn-primary w-100">Cek surat</button>
    </form>
    <div id="cekHasil" class="mt-3"></div>
  </div>
</div>
