<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {   
        $userPayload = ['name'=>$data['name'], 'email' => $data['email'], 'password' => $data['api_key'], 'type' => User::TYPE_MERCHANT];
        $user = User::create($userPayload);
        $merchantPayload = ['display_name' => $data['name'], 'domain' => $data['domain']];
        $merchant = $user->merchant()->create($merchantPayload);
        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $merchant = $user->merchant()->update([
            'domain' => $data['domain'],
            'display_name' => $data['name']
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->with('merchant')->first();
        return $user->merchant ?? null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $orders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();
        foreach($orders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }
}
