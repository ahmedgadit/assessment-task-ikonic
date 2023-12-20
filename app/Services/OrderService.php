<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
    }

    public function getPayoutOrders(): Order
    {
        // Implement logic to get orders for payout
        // You may want to filter orders based on payout status or other criteria
        $orders = Order::where('payout_status', Order::STATUS_UNPAID)->get();

        return $orders;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $merchant = Merchant::where('domain', $data['merchant_domain'])->firstOrFail();
        $affiliate = Affiliate::whereHas('user', function(Builder $query) use($data) {
            return $query->where('email', $data['customer_email']);
        })->first();
        if(!$affiliate) {
            $affiliate = $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
        } 
        $orderPayload = [
            'subtotal' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => null, 
            'commission_owed' => $data['subtotal_price'] * 0.1,
            'discount_code' => $data['discount_code'],
            'external_order_id' => $data['order_id']
        ];
        $order = Order::create($orderPayload);
    }

    public function getOrderByExternalOrderId($externalOrderId)
    {
        return Order::where('external_order_id', $externalOrderId)->first();
    }
}
