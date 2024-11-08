<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\StripeCard;
use Stripe\StripeClient;

class StripeController extends Controller
{

    use ApiResponser;

    //Stripe Account Create
    public function createStripeAccount()
    {
        $authUser = auth()->user();
        $authId = $authUser->id;

        $user = User::where('id', $authId)->first();
        $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

        if ($user->account_number == NULL) {
            $create = $stripe->accounts->create([
                'type' => 'express'
            ]);
            User::where('id', $user->id)->update(["account_number" => $create->id]);
            $accountlink = $create->id;
        } else {
            $accountlink = $user->account_number;
        }
        //retrieve
        $retrieve = $stripe->accountLinks->create([
            'account' => $accountlink,
            'refresh_url' => 'https://server1.appsstaging.com/3559/sweet_dreams/public/thankyou',
            'return_url' => 'https://server1.appsstaging.com/3559/sweet_dreams/public/thankyou',
            'type' => 'account_onboarding',
        ]);
        // dd($retrieve->url);
        $redirect_url = $retrieve->url;
        return $this->successDataResponse('Stripe URL', $redirect_url);
    }

    // Card List
    public function listCard()
    {
        try {
            // $stripeCard = StripeCard::where('user_id', auth()->id())->latest('is_default')->get();

            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
            $card_list = $stripe->customers->allSources(
                auth()->user()->customer_id, ['object' => 'card']
            );

            if (count($card_list) > 0) {
                return $this->successDataResponse('Card list', $card_list);
            } else {
                return $this->errorResponse('Card list not found.', 400);
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }


    // check Merchant Account
    public function checkMerchantAccount()
    {
        $authUser = auth()->user();
        $authId = $authUser->id;

        $user = User::where('id', $authId)->first();
        $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

        if ($user->account_number != null) {

            $account_number = $user->account_number;

            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

            $retrieve = $stripe->accounts->retrieve(
                $account_number
            );
            // return $retrieve;
            if ($retrieve->capabilities->transfers == 'active' && $retrieve->capabilities->card_payments = 'active') {

                User::where('id', $authId)->update(['merchant_account' => '1']);

                $marchant_account = User::where('id', $authId)->first("merchant_account");
                return $this->successDataResponse("account is active", $marchant_account);
            } else {
                return $this->errorResponse("Please active account first", 400);
            }
        } else {
            return $this->errorResponse("Please create account first", 400);
        }
    }


    // Add Card
    public function addCard(Request $request)
    {
        $this->validate($request, [
            'card_number' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
            'cvc' => 'required'
        ]);

        try {
            // Prepare payload with card details inside 'cardDetails' object
            $payload = [
                'customerId' => auth()->user()->customer_id, // Assuming customer_id is stored for the user
                'cardDetails' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->expiry_month,
                    'exp_year' => $request->expiry_year,
                    'cvc' => $request->cvc,
                    'name' => $request->name // Optional, if the name is required
                ]
            ];

            // Call the Node.js Stripe API
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://server1.appsstaging.com:3333/card-tokenize', [
                'json' => $payload
            ]);

            // Parse the response from the Node.js API
            $responseBody = json_decode($response->getBody(), true);
            // dd($responseBody);
            if (isset($responseBody['status']) && $responseBody['status'] == 1) {
                // Card addition successful, save card details to the database
                $stripeCardCount = StripeCard::where('user_id', auth()->id())->count();

                $userCard = new StripeCard();
                $userCard->user_id = auth()->id();
                $userCard->brand = $responseBody['data']['card']['brand']; // Adjust according to the response
                $userCard->exp_month = $responseBody['data']['card']['exp_month'];
                $userCard->exp_year = $responseBody['data']['card']['exp_year'];
                $userCard->last4 = $responseBody['data']['card']['last4'];
                $userCard->fingerprint = $responseBody['data']['card']['fingerprint'] ?? "";
                $userCard->token = $responseBody['data']['card']['id'];
                $userCard->is_default = $stripeCardCount == 0 ? '1' : '0';
                $userCard->save();

                $carData = StripeCard::where('id', $userCard->id)->first();
                return $this->successDataResponse('Card added successfully.', $carData);
            } else {
                // Handle failure response from the Node.js API
                return $this->errorResponse('Failed to add card: ' . $responseBody['msg'], 400);
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

        // Delete Card
        public function deleteCard(Request $request)
        {
            $this->validate($request, [
                'card_id' => 'required'
            ]);
            try {
                $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

                $deleteResponse = $stripe->customers->deleteSource(
                    auth()->user()->customer_id,
                    $request->card_id,
                    []
                );

                if (isset($deleteResponse->deleted)) {
                    $checkDefaultCard = StripeCard::where(['user_id' => auth()->id(), 'token' => $request->card_id, 'is_default' => '1'])->first();

                    $cardsToDelete = StripeCard::where(['user_id' => auth()->id(), 'token' => $request->card_id]);

                    if ($checkDefaultCard) {
                        $cardsToDelete->delete();

                        $remainingCards = StripeCard::where(['user_id' => auth()->id()])->get();

                        if ($remainingCards->isNotEmpty()) {
                            $firstRemainingCard = $remainingCards->first();

                            $stripe->customers->update(
                                auth()->user()->customer_id,
                                ['default_source' => $firstRemainingCard->token]
                            );
                            $firstRemainingCard->update(['is_default' => '1']);
                        }
                    } else {
                        $cardsToDelete->delete();
                    }
                    $defaultCard = StripeCard::where(['user_id' => auth()->id(), 'is_default' => '1'])->first();
                    return $this->successDataResponse('Card deleted successfully.', $defaultCard);
                } else {
                    return $this->errorResponse('Something went wrong.', 400);
                }
            } catch (\Exception $exception) {
                return $this->errorResponse($exception->getMessage(), 400);
            }
        }

        // Set as default card
        public function setAsDefaultCard(Request $request)
        {
            $this->validate($request, [
                'card_id' => 'required'
            ]);
            try {
                $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

                $updated = $stripe->customers->update(
                    auth()->user()->customer_id,
                    ['default_source' => $request->card_id]
                );
                if (isset($updated)) {
                    // StripeCard::where(['user_id' => auth()->id()])->update(['is_default' => '0']);
                    // StripeCard::where(['user_id' => auth()->id(), 'token' => $request->card_id])->update(['is_default' => '1']);
                    return $this->successResponse('Card set as default successfully.');
                } else {
                    return $this->errorResponse('Something went wrong.', 400);
                }
            } catch (\Exception $exception) {
                return $this->errorResponse($exception->getMessage(), 400);
            }
        }
}
