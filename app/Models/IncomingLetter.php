<?php

namespace App\Models;

use App\Notifications\IncomingLetterForwardedNotification;
use App\Services\LetterNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
        'forwarded_to', 'forwarded_at', 'forward_notes', 'forwarded_by',
    ];

    protected $casts = [
        'tanggal_surat'  => 'date',
        'tanggal_terima' => 'date',
        'read_at'        => 'datetime',
        'completed_at'   => 'datetime',
        'forwarded_at'   => 'datetime',
        'nomor_urut'     => 'integer',
        'tahun'          => 'integer',
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

    public function forwardedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_to');
    }

    public function forwarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    /**
     * Daftar pilihan user untuk dropdown teruskan surat — semua user aktif kecuali pengirim.
     * Jika $companyId diberikan, dibatasi ke perusahaan yang sama.
     */
    public static function recipientOptions(?int $excludeUserId = null, ?int $companyId = null): array
    {
        return User::query()
            ->with(['roles', 'department', 'unit'])
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (User $u) {
                $role = $u->roles->first()?->name ?? 'staf';
                $dept = $u->department?->name ?? $u->unit?->name ?? '';
                $label = $u->name . ' — ' . ucfirst($role) . ($dept ? " ({$dept})" : '');

                return [$u->id => $label];
            })
            ->all();
    }

    /**
     * Teruskan surat masuk ke orang tertentu (disposisi manual).
     * Penerima terusan mendapat notifikasi dan bisa melihat surat meski bukan unit aslinya.
     */
    public function forwardTo(User $recipient, User $sender, ?string $notes = null): void
    {
        $this->forceFill([
            'forwarded_to'   => $recipient->id,
            'forwarded_by'   => $sender->id,
            'forwarded_at'   => now(),
            'forward_notes'  => $notes,
        ])->save();

        activity('surat_masuk')
            ->performedOn($this)
            ->causedBy($sender)
            ->withProperties([
                'nomor_agenda'   => $this->nomor_agenda,
                'forwarded_to'   => $recipient->id,
                'recipient_name' => $recipient->name,
                'forward_notes'  => $notes,
            ])
            ->event('forwarded')
            ->log("Meneruskan surat masuk ke {$recipient->name}");

        try {
            $recipient->notify(new \App\Notifications\IncomingLetterForwardedNotification($this, $sender));
        } catch (\Throwable $e) {
            Log::error('Failed to send IncomingLetterForwardedNotification: ' . $e->getMessage());
        }
    }

    /**
     * Surat masuk yang boleh dilihat user:
     * - admin global / pencatat surat (izin Create:IncomingLetter) / direktur → seluruh perusahaannya,
     * - kabid → departemennya, staf → unitnya, serta surat yang ia catat sendiri.
     * - penerima terusan (forwarded_to) → dapat melihat surat yang diteruskan kepadanya.
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

        // Bangun kondisi akses sebagai OR-conditions langsung di Builder utama,
        // tanpa nested closure di level pertama (menghindari Filament Builder-clone bug).
        $table = (new self)->getTable();

        // Kondisi 1: surat yang ia buat sendiri
        $conditions   = ["`{$table}`.`created_by` = ?"];
        $bindings     = [$user->id];

        // Kondisi 2: surat yang diteruskan kepadanya
        $conditions[] = "`{$table}`.`forwarded_to` = ?";
        $bindings[]   = $user->id;

        // Kondisi 3: surat di perusahaan yang sama, dengan pembatasan unit/departemen
        if ($user->hasRole('direktur') || $user->can('Create:IncomingLetter')) {
            // Direktur & pencatat: semua surat perusahaan
            $conditions[] = "`{$table}`.`company_id` = ?";
            $bindings[]   = $user->company_id;
        } elseif ($user->hasRole('kabid') && $user->department_id && $user->unit_id) {
            // Kabid punya unit: surat untuk unitnya ATAU departemennya
            $conditions[] = "(`{$table}`.`company_id` = ? AND (`{$table}`.`unit_id` = ? OR `{$table}`.`department_id` = ?))";
            $bindings[]   = $user->company_id;
            $bindings[]   = $user->unit_id;
            $bindings[]   = $user->department_id;
        } elseif ($user->hasRole('kabid') && $user->department_id) {
            // Kabid tanpa unit: surat untuk departemennya
            $conditions[] = "(`{$table}`.`company_id` = ? AND `{$table}`.`department_id` = ?)";
            $bindings[]   = $user->company_id;
            $bindings[]   = $user->department_id;
        } elseif ($user->unit_id) {
            // Staf: surat untuk unitnya
            $conditions[] = "(`{$table}`.`company_id` = ? AND `{$table}`.`unit_id` = ?)";
            $bindings[]   = $user->company_id;
            $bindings[]   = $user->unit_id;
        }
        // else: hanya surat buatan sendiri + yang diteruskan kepadanya

        return $query->whereRaw('(' . implode(' OR ', $conditions) . ')', $bindings);
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
