<?php
// app/Http/Controllers/Guardian/CorController.php
namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\CorHeader;
use Illuminate\Support\Facades\Auth;

class CorController extends Controller
{
public function fetch(CorHeader $cor)
{
    $guardian = Auth::user()->guardian ?? null;

    if (!$guardian || $cor->guardian_id !== $guardian->id) {
        abort(403);
    }

    return response()->json([
        'html' => $cor->html_snapshot ?? '<p class="text-muted">No COR stored.</p>'
    ]);
}
}
