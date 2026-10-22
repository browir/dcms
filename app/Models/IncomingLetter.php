<?php

namespace App\Models;

use App\Services\LetterNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Surat Masuk dari pihak luar. Nomornya dari pengirim (nomor_surat); di DCMS dicatat dengan
 * nomor agenda masuk per perusahaan per tahun: {urut}/SM/{kode_perusahaan}/{bulan_romawi}/{tahun}.
 */
class IncomingLetter extends Model
{
    public const STATUS_BARU = 'baru';

    public const STATUS_DIBACA = 'dibaca';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS = [
        self::STATUS_BARU => 'Baru',
        self::STATUS_DIBACA => 'Sudah dibaca',
        self::STATUS_SELESAI => 'Selesai',
    ];

    public const SIFAT = [
        'biasa' => 'Biasa',
        'penting' => 'Penting',
        'segera' => 'Segera',
        'rahasia' => 'Rahasia',
    ];

    protected $fillable = [
        'company_id', 'pengirim', 'nomor_surat', 'tanggal_surat', 'tanggal_terima', 'perihal',
        'ringkasan', 'sifat', 'file_path', 'department_id', 'unit_id', 'created_by',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_terima' => 'date',
        'read_at' => 'datetime',
        'completed_at' => 'datetime',
        'nomor_urut' => 'integer',
        'tahun' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (IncomingLetter $letter): void {
            $letter->department_id ??= $letter->unit?->department_id;
            $letter->company_id ??= $letter->department?->company_id ?? Auth::user()?->company_id;
            $letter->created_by ??= Auth::id();
            $letter->status ??= self::STATUS_BARU;
            $letter->assignAgendaNumber();
        });
    }

    /**
     * Nomor agenda berikutnya untuk perusahaan & tahun terima. Dipanggil di event creating,
     * di dalam transaksi create() (lihat save) supaya kunci baris bertahan sampai commit.
     */
    public function assignAgendaNumber(): void
    {
        $tanggal = Carbon::parse($this->tanggal_terima ?? today());
        $company = Company::query()->findOrFail($this->company_id);

        $last = static::query()
            ->where('company_id', $company->id)
            ->where('tahun', $tanggal->year)
            ->lockForUpdate()
            ->max('nomor_urut');

        $this->tahun = $tanggal->year;
        $this->nomor_urut = ((int) $last) + 1;
        $this->nomor_agenda = sprintf(
            '%03d/SM/%s/%s/%d',
            $this->nomor_urut,
            app(LetterNumberService::class)->companyCode($company),
            LetterNumberService::ROMAWI[$tanggal->month - 1],
            $tanggal->year,
        );
    }

    public function save(array $options = []): bool
    {
        // INSERT baru dibungkus transaksi supaya lock nomor agenda bertahan sampai commit.
        return $this->exists ? parent::save($options) : DB::transaction(fn () => parent::save($options));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Surat masuk yang boleh dilihat user:
     * - admin global / pencatat surat (izin Create:IncomingLetter) / direktur → seluruh perusahaannya,
     * - kabid → departemennya, staf → unitnya, serta surat yang ia catat sendiri.
     */
    public function scopeAccess(Builder $query, ?User $user = null): Builder
    {
        /** @var User|null $user */
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin') || $user->can('view_all_companies_data')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhere(function (Builder $scope) use ($user) {
                    $scope->where('company_id', $user->company_id);

                    if ($user->hasRole('direktur') || $user->can('Create:IncomingLetter')) {
                        return;
                    }

                    $scope->where(function (Builder $own) use ($user) {
                        $own->when($user->unit_id, fn (Builder $u) => $u->where('unit_id', $user->unit_id))
                            ->when($user->hasRole('kabid') && $user->department_id, fn (Builder $d) => $d->orWhere('department_id', $user->department_id))
                            ->when(! $user->unit_id && ! ($user->hasRole('kabid') && $user->department_id), fn (Builder $none) => $none->whereRaw('1 = 0'));
                    });
                });
        });
    }

    /** User yang menjadi tujuan surat (anggota unit tujuan). */
    public function isForUser(User $user): bool
    {
        return $this->unit_id && $this->unit_id === $user->unit_id;
    }

    public function markReadBy(User $user): void
    {
        if ($this->status === self::STATUS_BARU && $this->isForUser($user)) {
            $this->forceFill(['status' => self::STATUS_DIBACA, 'read_at' => now(), 'read_by' => $user->id])->save();
        }
    }

    public function markCompleted(): void
    {
        $this->forceFill([
            'status' => self::STATUS_SELESAI,
            'completed_at' => now(),
            'read_at' => $this->read_at ?? now(),
        ])->save();
    }
}
