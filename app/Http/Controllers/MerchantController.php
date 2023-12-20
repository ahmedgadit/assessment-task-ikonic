<?php

namespace App\Http\Controllers;

use App\Jobs\PayoutOrderJob;
use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    protected $merchantService;

    public function __construct(
        MerchantService $merchantService
    ) {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        try {
            $from = Carbon::parse($request->input('from'));
            $to = Carbon::parse($request->input('to'));
            $orders = Order::whereBetween('created_at', [$from, $to])->get();
            $revenue = $orders->sum('subtotal');
            $totalOrders = $orders->count();

            $unpaidCommission = Order::has('affiliate')->whereBetween('created_at', [$from, $to])
                ->where('payout_status', '=', Order::STATUS_UNPAID)
                ->get();

            return response()->json(['count' => $totalOrders, 'revenue' => $revenue, 'commissions_owed' => $unpaidCommission->sum('commission_owed')]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
