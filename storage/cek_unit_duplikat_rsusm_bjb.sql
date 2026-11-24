-- =====================================================================
-- CEK UNIT DUPLIKAT RSU SYIFA MEDIKA BANJARBARU (read-only, tidak mengubah data)
-- Jalankan di phpMyAdmin / mysql pada database produksi, kirim hasil kedua query.
-- =====================================================================

-- ─── QUERY 1: Semua unit RSUSM BJB + jumlah pemakaiannya ─────────────
-- (unit resmi bernomor = company_id 5; unit lama sering company_id NULL
--  dan hanya terhubung lewat departemen)
SELECT
    u.id,
    u.name,
    u.kode,
    u.prefix,
    u.company_id,
    d.code  AS dept_code,
    d.name  AS dept_name,
    u.is_active,
    (SELECT COUNT(*) FROM users                x WHERE x.unit_id = u.id) AS n_users,
    (SELECT COUNT(*) FROM documents            x WHERE x.unit_id = u.id) AS n_documents,
    (SELECT COUNT(*) FROM document_unit_access x WHERE x.unit_id = u.id) AS n_doc_access,
    (SELECT COUNT(*) FROM letter_numbers       x WHERE x.unit_id = u.id) AS n_letter_numbers,
    (SELECT COUNT(*) FROM surat                x WHERE x.unit_id = u.id) AS n_surat,
    (SELECT COUNT(*) FROM agendas              x WHERE x.unit_id = u.id) AS n_agendas,
    (SELECT COUNT(*) FROM incoming_letters     x WHERE x.unit_id = u.id) AS n_incoming,
    (SELECT COUNT(*) FROM meetings             x WHERE x.unit_id = u.id) AS n_meetings
FROM units u
LEFT JOIN departments d ON d.id = u.department_id
WHERE COALESCE(u.company_id, d.company_id) = (
    SELECT id FROM companies WHERE id = 5 OR code = 'RSUSMBJB' ORDER BY id = 5 DESC LIMIT 1
)
ORDER BY d.code, (u.kode IS NULL OR u.kode = ''), LENGTH(u.kode), u.kode, u.name;


-- ─── QUERY 2: Kandidat pasangan duplikat (unit lama ↔ unit bernomor) ──
-- Pencocokan nama kasar: nama unit lama tanpa "Banjarbaru"/"BJB" dicari di
-- nama unit bernomor (atau sebaliknya). Hasil ini hanya saran — tetap diperiksa manual.
SELECT
    lama.id     AS id_lama,
    lama.name   AS nama_lama,
    lama.prefix AS prefix_lama,
    (SELECT COUNT(*) FROM users x WHERE x.unit_id = lama.id) AS users_lama,
    baru.id     AS id_baru,
    baru.kode   AS kode_baru,
    baru.name   AS nama_baru,
    (SELECT COUNT(*) FROM users x          WHERE x.unit_id = baru.id) AS users_baru,
    (SELECT COUNT(*) FROM letter_numbers x WHERE x.unit_id = baru.id) AS nomor_baru
FROM units lama
LEFT JOIN departments dl ON dl.id = lama.department_id
JOIN units baru
    ON baru.company_id = 5
   AND baru.kode IS NOT NULL AND baru.kode <> ''
   AND baru.id <> lama.id
WHERE COALESCE(lama.company_id, dl.company_id) = 5
  AND (lama.kode IS NULL OR lama.kode = '')
  AND (
        LOWER(baru.name) LIKE CONCAT('%', LOWER(TRIM(REPLACE(REPLACE(lama.name, 'Banjarbaru', ''), 'BJB', ''))), '%')
     OR LOWER(lama.name) LIKE CONCAT('%', LOWER(TRIM(REPLACE(REPLACE(REPLACE(baru.name, 'Unit ', ''), 'Instalasi ', ''), 'Tim ', ''))), '%')
  )
ORDER BY baru.kode;
