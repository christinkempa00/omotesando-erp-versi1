<?php

namespace App\Models\GA;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Concerns\Approvable;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use App\Services\TelegramNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
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
        'priority',
        'description',
        'requested_by',
        'requester_name',
        'requester_signature_path',
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

    // --- Konstanta prioritas ---
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Rendah',
            self::PRIORITY_NORMAL => 'Sedang',
            self::PRIORITY_HIGH => 'Tinggi',
        ];
    }

    public static function priorityBadgeColor(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'bg-slate-100 text-slate-600',
            self::PRIORITY_NORMAL => 'bg-blue-100 text-blue-700',
            self::PRIORITY_HIGH => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
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
     * Warna badge per status — mengikuti kontrak 5-status baku Allez ERP
     * Redesign (Pending/Approved/Rejected/Received), SAMA di semua modul
     * (GA/SCM/dst) supaya user tidak belajar ulang arti warna tiap pindah
     * modul. draft/submitted/in_review semua masuk tone "pending" (masih
     * dalam proses, belum final).
     */
    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW => 'bg-status-pending-bg text-status-pending-fg',
            self::STATUS_APPROVED => 'bg-status-approved-bg text-status-approved-fg',
            self::STATUS_REJECTED => 'bg-status-rejected-bg text-status-rejected-fg',
            self::STATUS_RECEIVED => 'bg-status-received-bg text-status-received-fg',
            default => 'bg-status-pending-bg text-status-pending-fg',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(GaRequestAttachment::class);
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

    /**
     * Override pesan notifikasi generik dari trait Approvable supaya lebih
     * informatif — sertakan nomor pengajuan, pemohon, dan outlet.
     */
    public function approvalNotificationText(string $decision, ?string $note): string
    {
        $text = "*Pengajuan {$decision}*\n";
        $text .= "No: {$this->request_number}\n";
        $text .= 'Pemohon: '.($this->requester_name ?: ($this->requestedBy?->name ?? '-'))."\n";
        $text .= 'Outlet: '.($this->branch?->name ?? '-');

        if ($note) {
            $text .= "\nCatatan: {$note}";
        }

        return $text;
    }

    /**
     * Dipanggil trait Approvable tiap kali sebuah step di-approve/reject,
     * supaya status GaRequest ikut mencerminkan progres alur:
     * Step 1 (Finance "diketahui") approved -> in_review
     * Step 2 (Head "disetujui") approved     -> approved
     * Step 3 (Finance "diterima") approved   -> received (selesai)
     * Step manapun rejected                  -> rejected
     */
    public function syncStatusAfterApproval(): void
    {
        if ($this->hasRejectedStep()) {
            $this->status = self::STATUS_REJECTED;
        } elseif ($this->isFullyApproved()) {
            $this->status = self::STATUS_RECEIVED;
        } elseif ($this->approvals()->where('approver_role', Role::HEAD)->where('status', Approval::STATUS_APPROVED)->exists()) {
            $this->status = self::STATUS_APPROVED;
        } else {
            $this->status = self::STATUS_IN_REVIEW;
        }

        $this->save();
    }

    /**
     * Override hook dari trait Approvable — sekali alur approval selesai
     * penuh (status jadi RECEIVED), kirim PDF final (sudah memuat tanda
     * tangan gambar tiap step, kalau ada) ke Telegram sebagai jejak audit —
     * ke grup umum SEKALIGUS langsung ke chat pribadi pemohon (kalau sudah
     * mengisi telegram_chat_id di profilnya; kalau belum, cuma ke grup,
     * tidak error). TelegramNotifier aman-tanpa-kredensial, jadi ini tidak
     * melempar error kalau bot belum dikonfigurasi di environment ini.
     */
    protected function sendFinalDocumentIfComplete(): void
    {
        if ($this->status !== self::STATUS_RECEIVED) {
            return;
        }

        $this->loadMissing(['items', 'branch', 'division', 'requestedBy', 'approvals.approver']);

        $pdfContents = Pdf::loadView('ga.requests.document-pdf', [
            'gaRequest' => $this,
            'categoryLabels' => self::categoryLabels(),
        ])->setPaper('a4', 'portrait')->output();

        $filename = 'GAR-'.str_replace('/', '-', $this->request_number).'.pdf';
        $notifier = app(TelegramNotifier::class);

        $notifier->sendDocument(
            $filename,
            $pdfContents,
            "Dokumen final GAR {$this->request_number} — telah diterima & disetujui penuh."
        );

        if ($this->requestedBy?->telegram_chat_id) {
            $requesterName = $this->requester_name ?: $this->requestedBy->name;

            $notifier->sendDocument(
                $filename,
                $pdfContents,
                "Halo {$requesterName}, pengajuan GAR {$this->request_number} Anda sudah disetujui penuh. Berikut dokumen finalnya.",
                $this->requestedBy->telegram_chat_id
            );
        }
    }
}
