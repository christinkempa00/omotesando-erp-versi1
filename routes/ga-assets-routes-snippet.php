// Tambahkan use statement ini di atas routes/web.php:
use App\Http\Controllers\GA\AssetController;

// Tambahkan route group ini (boleh di bawah group 'ga.requests' yang sudah ada):
Route::middleware(['auth', 'role:GA,Head,Admin,Finance'])
    ->prefix('ga')
    ->name('ga.')
    ->group(function () {
        Route::resource('assets', AssetController::class);
    });

// Ini akan menghasilkan route:
// GET    /ga/assets              -> ga.assets.index
// GET    /ga/assets/create       -> ga.assets.create
// POST   /ga/assets              -> ga.assets.store
// GET    /ga/assets/{asset}      -> ga.assets.show
// GET    /ga/assets/{asset}/edit -> ga.assets.edit
// PUT    /ga/assets/{asset}      -> ga.assets.update
// DELETE /ga/assets/{asset}      -> ga.assets.destroy

// CATATAN: kalau Anda taruh ini di GROUP TERPISAH dari group 'ga.requests'
// yang sudah ada (bukan digabung ke group yang sama), tidak masalah -
// Laravel akan tetap menghasilkan route name yang sama karena prefix &
// name-nya identik.
