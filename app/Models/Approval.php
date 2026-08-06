<?php

namespace App\Models;

use App\Models\GA\GaRequest;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Purchasing\PurchaseRequisition;
use App\Models\SCM\MaterialRequest;
use App\Models\SCM\ProductionBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Approval extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'step',
        'approver_role',
        'approver_user_id',
        'status',
        'note',
        'approved_at',
        'signature_path',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * Label ramah-baca per FQCN model yang memakai trait Approvable — dipakai
     * filter "Modul" di Approval Inbox Head. Tambah baris di sini kalau ada
     * model baru yang mengadopsi Approvable (lihat komentar di trait itu).
     */
    public static function approvableModuleLabels(): array
    {
        return [
            GaRequest::class => 'Request',
            MaterialRequest::class => 'Pengajuan Bahan',
            ProductionBatch::class => 'Batch Produksi',
            PurchaseOrder::class => 'Purchase Order',
            PurchaseRequisition::class => 'Purchase Requisition',
        ];
    }

    /**
     * Decode data URL PNG (dari canvas tanda tangan) & simpan ke disk public.
     * Sama polanya dengan UniformRecordController::storeSignature() — tanda
     * tangan berbasis gambar ini BUKAN tanda tangan tersertifikasi/legal,
     * cuma jejak audit internal. Mengembalikan null (tanpa error) kalau data
     * kosong/tidak valid, supaya tanda tangan tetap opsional.
     */
    public static function storeSignatureImage(?string $signatureData): ?string
    {
        if (! $signatureData || ! preg_match('/^data:image\/png;base64,(.+)$/', $signatureData, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1]);
        if ($binary === false) {
            return null;
        }

        $path = 'approvals/signatures/'.Str::random(32).'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
