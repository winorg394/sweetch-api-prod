<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TontineMemberOrder;
use Illuminate\Http\Request;

class TontineMemberController extends Controller
{
    public function index(Request $request)
    {
        return TontineMemberOrder::with(['tontine', 'member'])->where('tontine_id',$request->tontineId)
            ->orderBy('position')
            ->get();
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:tontine_members_order,id',
            'order.*.position' => 'required|integer'
        ]);

        foreach ($request->input('order') as $item) {
            TontineMemberOrder::where('id', $item['id'])
                ->update([
                    'position' => $item['position'],
                    'colleted' => $item['colleted'] ?? false
                ]);
        }

        return response()->json(['success' => true]);
    }
}
