/**
 * Web App penerima sinkronisasi setoran Bank Sampah → Google Sheets.
 *
 * Perbaikan waktu: kolom "Waktu Input" kini ditulis sebagai object Date dari
 * created_at (ISO-8601 +07:00 yang dikirim Laravel), bukan string mentah,
 * sehingga Sheets menampilkan jam sebenarnya, bukan 00:00.
 *
 * WAJIB sekali setup di spreadsheet:
 *   File → Settings → Time zone → (GMT+07:00) Jakarta
 * lalu Deploy ulang Web App (Deploy → Manage deployments → Edit → Version: New).
 */
function doPost(e) {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    var data = JSON.parse(e.postData.contents);

    if (sheet.getLastRow() === 0) {
      sheet.appendRow([
        "Waktu Input",
        "No. Bukti",
        "Tanggal Setor",
        "Kode Nasabah",
        "Nama Nasabah",
        "Jenis Nasabah",
        "Kategori Sampah",
        "Kode Kategori",
        "Berat (Kg)",
        "Harga/Kg (Rp)",
        "Total (Rp)",
        "Faktor Emisi (kg CO2e/kg)",
        "Emisi Terhindar (kg CO2e)",
        "Petugas",
        "Catatan"
      ]);
      sheet.getRange("A1:O1").setFontWeight("bold").setBackground("#e4e4e7").setFontColor("#18181b");
      sheet.setFrozenRows(1);
    }

    var rows = data.items || [data];
    var startRow = sheet.getLastRow() + 1; // baris pertama yang akan diisi batch ini

    for (var i = 0; i < rows.length; i++) {
      var r = rows[i];

      // created_at dikirim sebagai ISO-8601 dengan offset (mis. 2026-08-29T20:06:59+07:00).
      // WAJIB dibungkus new Date() supaya jadi nilai tanggal-waktu, bukan teks mentah.
      var waktuInput = r.waktu_input ? new Date(r.waktu_input) : new Date();
      if (isNaN(waktuInput.getTime())) {
        waktuInput = new Date();
      }

      sheet.appendRow([
        waktuInput,
        r.nomor_bukti,
        r.tanggal_setor,
        r.kode_nasabah,
        r.nama_nasabah,
        r.jenis_nasabah,
        r.kategori_sampah,
        r.kode_kategori,
        r.berat_kg,
        r.harga_per_kg,
        r.total_rupiah,
        r.faktor_emisi,
        r.emisi_terhindar_kg_co2e,
        r.petugas,
        r.catatan || ""
      ]);
    }

    // Paksa format kolom pada baris yang baru ditulis — berlaku juga untuk sheet
    // yang sudah berisi data lama (blok header di atas hanya jalan saat sheet kosong).
    if (rows.length > 0) {
      sheet.getRange(startRow, 1, rows.length, 1).setNumberFormat("dd/MM/yyyy HH:mm:ss"); // Waktu Input
      sheet.getRange(startRow, 3, rows.length, 1).setNumberFormat("dd/MM/yyyy");          // Tanggal Setor
    }

    return ContentService.createTextOutput(JSON.stringify({ status: "success", count: rows.length }))
      .setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ status: "error", message: err.toString() }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

function testAuth() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  Logger.log("Izin berhasil untuk: " + sheet.getName());
}
