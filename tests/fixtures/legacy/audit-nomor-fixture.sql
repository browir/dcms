-- Fixture dump untuk tests/MySQL/ImportCounterLamaTest.php
-- Mensimulasikan audit-nomor.sql dari sistem lama.
--
-- Pemetaan legacy_id yang ADA di companies:
--   legacy_id=1  => SGG
--   legacy_id=2  => RSUSM
--   legacy_id=3  => RSUSM-BRB
--
-- Pemetaan legacy_id yang TIDAK ADA (harus dilaporkan dilewati):
--   legacy_id=4  => Koperasi
--   legacy_id=7  => SMP
--
-- Nilai yang diharapkan setelah import:
--   SGG 2025      : MAX(305, 307) = 307
--   SGG 2026      : MAX(350, 351) = 351
--   RSUSM 2025    : MAX(1, 1)     = 1
--   RSUSM-BRB 2026: MAX(1580,1583)= 1583

-- -------------------------------------------------------------------------
-- Tabel 1: counter dari sistem lama
-- -------------------------------------------------------------------------
INSERT INTO `nomor_surat_counter` (`id_perusahaan`,`tahun`,`nomor_terakhir`) VALUES
(1,2025,305),
(1,2026,350),
(2,2025,1),
(3,2026,1580),
(4,2026,10),
(7,2026,5);

-- -------------------------------------------------------------------------
-- Tabel 2: surat dari sistem lama (keluar dan masuk)
-- Counter SGG 2025 harus MAX dari nomor surat (307 > 305)
-- Counter SGG 2026 harus MAX dari nomor surat (351 > 350)
-- Surat masuk (row id=3) harus diabaikan
-- Legacy 4 dan 7 harus dilaporkan dilewati
-- -------------------------------------------------------------------------
INSERT INTO `nomor_surat` (`id`,`id_perusahaan`,`nomor`,`jenis_surat`) VALUES
(1,1,'306/G.SGG/IX/2025','keluar'),
(2,1,'307/G.SGG/X/2025','keluar'),
(3,1,'100/G.SGG/IX/2025','masuk'),
(4,1,'351/G.SGG/I/2026','keluar'),
(5,2,'001/G.RSUSM/I/2025','keluar'),
(6,3,'1583/G.RSUSM-BRB/XII/2026','keluar'),
(7,4,'015/G.KOP/VI/2026','keluar'),
(8,7,'003/G.SMP/III/2026','keluar');
