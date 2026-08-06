@props([
    'approvals', // Collection Approval, sudah terurut per step (lihat Approvable::approvals())
    'direction' => 'vertical', // 'vertical' (detail page, dgn meta) atau 'horizontal' (ringkas)
])

@php
    $hasRejection = $approvals->contains('status', \App\Models\Approval::STATUS_REJECTED);
    $currentAssigned = false;

    $steps = $approvals->map(function ($approval) use (&$currentAssigned, $hasRejection) {
        if ($approval->status === \App\Models\Approval::STATUS_APPROVED) {
            $state = 'done';
        } elseif ($approval->status === \App\Models\Approval::STATUS_REJECTED) {
            $state = 'rejected';
        } elseif (! $hasRejection && ! $currentAssigned) {
            $state = 'current';
            $currentAssigned = true;
        } else {
            $state = 'pending';
        }

        return ['approval' => $approval, 'state' => $state];
    });
@endphp

@if ($direction === 'horizontal')
    <div class="flex items-center">
        @foreach ($steps as $i => $step)
            <div class="flex items-center flex-1">
                <div class="flex flex-col items-center gap-1.5 min-w-[64px]">
                    <div @class([
                        'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0',
                        'bg-status-approved-fg text-white' => $step['state'] === 'done',
                        'bg-accent-tint text-accent border-2 border-accent' => $step['state'] === 'current',
                        'bg-status-rejected-fg text-white' => $step['state'] === 'rejected',
                        'bg-gray-100 text-ink-muted border border-hairline' => $step['state'] === 'pending',
                    ])>
                        @if ($step['state'] === 'done') ✓
                        @elseif ($step['state'] === 'rejected') ✕
                        @else {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="text-[10.5px] text-center text-ink-muted font-semibold leading-tight">{{ $step['approval']->approver_role }}</div>
                </div>
                @if (! $loop->last)
                    <div @class([
                        'flex-1 h-0.5 -mx-1 mb-5',
                        'bg-status-approved-fg' => $step['state'] === 'done',
                        'bg-hairline' => $step['state'] !== 'done',
                    ])></div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="flex flex-col">
        @foreach ($steps as $step)
            <div class="flex gap-3">
                <div class="flex flex-col items-center">
                    <div @class([
                        'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0',
                        'bg-status-approved-fg text-white' => $step['state'] === 'done',
                        'bg-accent-tint text-accent border-2 border-accent' => $step['state'] === 'current',
                        'bg-status-rejected-fg text-white' => $step['state'] === 'rejected',
                        'bg-gray-100 text-ink-muted border border-hairline' => $step['state'] === 'pending',
                    ])>
                        @if ($step['state'] === 'done') ✓
                        @elseif ($step['state'] === 'rejected') ✕
                        @else {{ $step['approval']->step }}
                        @endif
                    </div>
                    @if (! $loop->last)
                        <div @class([
                            'w-0.5 flex-1 min-h-[26px]',
                            'bg-status-approved-fg' => $step['state'] === 'done',
                            'bg-hairline' => $step['state'] !== 'done',
                        ])></div>
                    @endif
                </div>
                <div class="pb-5">
                    <div class="text-sm font-semibold text-ink">{{ $step['approval']->approver_role }}</div>
                    <div class="text-xs text-ink-muted mt-0.5">
                        @if ($step['approval']->status === \App\Models\Approval::STATUS_APPROVED)
                            Disetujui {{ $step['approval']->approver?->name ? 'oleh '.$step['approval']->approver->name.' · ' : '' }}{{ optional($step['approval']->approved_at)->format('d M Y H:i') }}
                        @elseif ($step['approval']->status === \App\Models\Approval::STATUS_REJECTED)
                            Ditolak {{ $step['approval']->approver?->name ? 'oleh '.$step['approval']->approver->name.' · ' : '' }}{{ optional($step['approval']->approved_at)->format('d M Y H:i') }}
                            @if ($step['approval']->note)
                                <div class="text-status-rejected-fg mt-0.5">{{ $step['approval']->note }}</div>
                            @endif
                        @elseif ($step['state'] === 'current')
                            Menunggu tindakan
                        @else
                            Belum diproses
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
