<?php

namespace App\Models\GA;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Concerns\Approvable;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GaRequest extends Model
{
    use Approvable;

    protected $fillable = [
        'request_number',
        'division_id',
        'branch_id',
        'category',
        'description',
        'requested_by',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    // --- Konstanta kategori (kolom "Tujuan" di dokumen GAR asli) ---
    public const CATEGORY_PERBAIKAN_FASILITAS = 'perbaikan_fasilitas';
    public const CATEGORY_INFRASTRUKTUR_SISTEM = 'infrastruktur_sistem';
    public const CATEGORY_KESELAMATAN_KEBERSIHAN = 'keselamatan_kebersihan';
    public const CATEGORY_OPERASIONAL_PENGEMBANGAN = 'operasional_pengembangan';
    public const CATEGORY_MAINTENANCE_STOCK = 'maintenance_stock';

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_PERBAIKAN_FASILITAS => 'Perbaikan Fasilitas',
            self::CATEGORY_INFRASTRUKTUR_SISTEM => 'Infrastruktur & Sistem',
            self::CATEGORY_KESELAMATAN_KEBERSIHAN => 'Keselamatan & Kebersihan',
            self::CATEGORY_OPERASIONAL_PENGEMBANGAN => 'Operasional & Pengembangan',
            self::CATEGORY_MAINTENANCE_STOCK => 'Maintenance & Stock',
        ];
    }

    // --- Konstanta status ---
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECEIVED = 'received';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_IN_REVIEW => 'Dalam Review',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_RECEIVED => 'Diterima',
        ];
    }

    /**
     * Warna badge Tailwind per status, dipakai di view.
     */
    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-700',
            self::STATUS_SUBMITTED => 'bg-blue-100 text-blue-700',
            self::STATUS_IN_REVIEW => 'bg-yellow-100 text-yellow-700',
            self::STATUS_APPROVED => 'bg-green-100 text-green-700',
            self::STATUS_REJECTED => 'bg-red-100 text-red-700',
            self::STATUS_RECEIVED => 'bg-emerald-100 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GaRequestItem::class);
    }

    // --- Business logic ---

    /**
     * Hitung ulang total_amount dari seluruh item, lalu simpan.
     * Dipanggil setiap kali item berubah (tambah/hapus/edit).
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('total');
        $this->save();
    }

    /**
     * Generate nomor request unik: 000X/[bulan romawi]/GAR/[tahun].
     * Urutan (000X) kontinu sepanjang tahun berjalan, reset di tahun berikutnya.
     * Dibungkus transaksi + lock supaya aman dari race condition saat
     * dua request dibuat nyaris bersamaan.
     */
    public static function generateRequestNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('request_number', 'like', "%/GAR/{$year}")
                ->lockForUpdate()
                ->count();

            $nextSequence = $count + 1;
            $sequencePadded = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/GAR/{$year}";
        });
    }

    private static function monthToRoman(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Generate 3 baris approval berjenjang sesuai alur dokumen GAR:
     * Step 1: Finance ("Diketahui oleh")
     * Step 2: Head ("Disetujui oleh")
     * Step 3: Finance lagi ("Diterima Finance")
     *
     * Dipanggil sekali saat request pertama kali disubmit.
     */
    public function generateApprovalSteps(): void
    {
        // Hindari duplikat kalau method ini ke-panggil dua kali
        if ($this->approvals()->exists()) {
            return;
        }

        $steps = [
            ['step' => 1, 'role' => Role::FINANCE],
            ['step' => 2, 'role' => Role::HEAD],
            ['step' => 3, 'role' => Role::FINANCE],
        ];

        foreach ($steps as $step) {
            $this->approvals()->create([
                'step' => $step['step'],
                'approver_role' => $step['role'],
                'status' => Approval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Hanya pembuat request yang boleh resubmit request yang ditolak.
     */
    public function canBeResubmittedBy(User $user): bool
    {
        return $this->status === self::STATUS_REJECTED
            && $this->requested_by === $user->id;
    }
}
