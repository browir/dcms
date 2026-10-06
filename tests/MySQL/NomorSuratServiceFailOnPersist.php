<?php

namespace Tests\MySQL;

use App\Models\Surat;
use App\Services\NomorSuratService;
use RuntimeException;

/**
 * Subclass untuk test rollback.
 * Melempar exception setelah counter di-increment tapi sebelum surat di-INSERT.
 * Membuktikan bahwa transaksi rollback counter bersama surat.
 *
 * HANYA dipakai di tests/ — jangan pernah dipakai di kode produksi.
 */
class NomorSuratServiceFailOnPersist extends NomorSuratService
{
    protected static function persistSurat(array $data): Surat
    {
        throw new RuntimeException(
            '[TEST] Simulasi kegagalan setelah counter di-increment. '
            . 'nomor_urut yang dimaksud: ' . $data['nomor_urut']
        );
    }
}
