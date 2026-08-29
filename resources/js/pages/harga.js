// resources/js/pages/harga.js — dari admin/harga.blade.php
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.getElementById('form-ubah-wrapper');
  const idInput = document.getElementById('ubah_kategori_id');
  const label = document.getElementById('ubah_nama_label');
  const hargaInput = document.getElementById('ubah_harga');
  const batal = document.getElementById('batal-ubah');
  if (!wrapper || !idInput) return;

  document.querySelectorAll('.buka-form-ubah').forEach((btn) => {
    btn.addEventListener('click', () => {
      idInput.value = btn.dataset.kategoriId || '';
      if (label) label.textContent = btn.dataset.nama || '';
      if (hargaInput) {
        hargaInput.value = btn.dataset.harga || '';
        hargaInput.focus();
      }
      wrapper.classList.remove('hidden');
      wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });
  if (batal) batal.addEventListener('click', () => wrapper.classList.add('hidden'));
});
