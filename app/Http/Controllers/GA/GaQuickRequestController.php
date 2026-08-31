<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreGaQuickRequestRequest;
use App\Models\BranchLocation;
use App\Models\GA\GaQuickRequest;
use App\Models\UserPagePermission;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;

class GaQuickRequestController extends Controller
{
    public function store(StoreGaQuickRequestRequest $request, TelegramNotifier $telegram): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_REQUESTS), 403);

        $validated = $request->validated();

        if ($userBranch = $request->user()->scopingBranch()) {
            $validated['branch_id'] = $userBranch->id;
            if (
                ($validated['branch_location_id'] ?? null)
                && ! BranchLocation::where('id', $validated['branch_location_id'])->where('branch_id', $userBranch->id)->exists()
            ) {
                $validated['branch_location_id'] = null;
            }
        }

        $quickRequest = GaQuickRequest::create([
            'requested_date' => $validated['requested_date'],
            'branch_id' => $validated['branch_id'],
            'branch_location_id' => $validated['branch_location_id'] ?? null,
            'user_name' => $validated['user_name'],
            'pic_name' => $validated['pic_name'],
            'urgency' => $validated['urgency'],
            'needs_description' => $validated['needs_description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $quickRequest->items()->create([
                'item_name' => $item['item_name'],
                'qty' => $item['qty'],
                'unit' => $item['unit'] ?? null,
                'notes' => $item['notes'] ?? null,
                'photo_link' => $item['photo_link'] ?? null,
            ]);
        }

        $quickRequest->load(['branch', 'branchLocation', 'items']);

        $sent = $telegram->sendMessage($quickRequest->toTelegramText());

        if ($sent) {
            $quickRequest->update(['sent_at' => now()]);

            return redirect()
                ->route($this->routeFor('requests.index'))
                ->with('success', 'Permintaan cepat berhasil dikirim ke Telegram.');
        }

        return redirect()
            ->route($this->routeFor('requests.index'))
            ->with('success', 'Permintaan cepat tersimpan. Pengiriman ke Telegram belum aktif — hubungkan bot Telegram di pengaturan untuk mengaktifkan pengiriman otomatis.');
    }
}
