<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService,
        public  Affiliate $classAffiliate
    ) {}

    public function getAffiliateOrders(Affiliate $affiliate, $from, $to): Order
    {
        $orders = Order::where('affiliate_id', $affiliate->id)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        return $orders;
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {
            if($merchant->user->email == $email) {
                throw new AffiliateCreateException("email belongs to merchant user."); 
            }

            $userPayload = ['name' => $name, 'email' => $email, 'type' => User::TYPE_AFFILIATE];
            $user = User::create($userPayload);

            $discount = $this->apiService->createDiscountCode($merchant);

            $affiliate = Affiliate::create(['user_id' => $user->id, 'merchant_id' => $merchant->id, 'commission_rate' => $commissionRate, 'discount_code' => $discount['code']]);

            Mail::to($user)->send(new AffiliateCreated($affiliate));
            $this->classAffiliate = $affiliate;
            return $affiliate;
        } catch (Exception $th) {
            throw new AffiliateCreateException("email belongs to affiliated user already."); 
            throw $th;
        }
    }
}
