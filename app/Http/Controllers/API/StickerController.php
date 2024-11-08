<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\User;
use App\Models\Sticker;
use App\Models\UserSticker;
use App\Models\StripeCard;
use Stripe\StripeClient;

class StickerController extends Controller
{
    
    use ApiResponser;
    public function index(Request $request)
    {
        $keyword = $request->search;
        $type = $request->type;

        // Define the base query to include only active stickers
        $query = Sticker::where("is_active", 1);

        // If a type is provided, apply the sticker_type filter
        if ($type !== null) {
            $query->where("sticker_type", "=", $type);
        }

        // If a keyword is provided, apply the search filters
        if ($keyword !== null) {
            $query->where(function ($subQuery) use ($keyword) {
             $subQuery->where('sticker_name', 'like', '%' . $keyword . '%')
                 ->orWhere('price', $keyword);
            });
        }

      // Execute the query
        $stickers = $query->get();

      // Check if stickers exist and return the appropriate response
        if ($stickers->isNotEmpty()) {
           return response()->json([
               'status' => 1,
                'message' => 'Stickers found successfully.',
                'data' => $stickers,
         ]);
      } else {
         return response()->json([
             'status' => 0,
            'message' => 'No Stickers Found Yet!',
         ]);
        }
    }

    public function userStickers()
    {
        $userID = auth()->user()->id;

        // Get all sticker IDs for the authenticated user
        $userStickerIds = UserSticker::where("user_id", $userID)->pluck('sticker_id')->toArray();

        // Retrieve the stickers based on the sticker IDs
        $userStickers = Sticker::whereIn('id', $userStickerIds)->get();

        // Check if stickers exist and return appropriate response
        if ($userStickers->isNotEmpty()) {
            return response()->json([
                'status' => 1,
                "message" => "User Stickers found successfully.",
                'data' => $userStickers,
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'User has not purchased or redeemed any sticker yet!',
            ]);
        }
    }
    
    public function purchaseSticker(Request $request)
    {
        $this->validate($request, [
            'sticker_id' => 'required',
            'card_id' => 'nullable|integer'
        ]);

        $sticker = Sticker::findOrFail($request->sticker_id);
        $user = auth()->user();

        // Check if the user has already purchased the sticker
        $isAlreadyPurchased = UserSticker::where('sticker_id', $sticker->id)
            ->where('user_id', $user->id)
            ->first();

        if ($isAlreadyPurchased) {
            return response()->json(['status' => 'OK', 'message' => 'Sticker already purchased!']);
        } elseif ($sticker->type == "premium") {
            $userSticker = new UserSticker();
            $userSticker->user_id = $user->id;
            $userSticker->sticker_id = $request->sticker_id;
            $userSticker->type = $sticker->sticker_type;
            $userSticker->save();

            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
            // $stripeCard = StripeCard::where(['user_id' => $user->id, 'id' => $request->card_id])->first();

            // if (!$stripeCard) {
            //     return $this->errorResponse('Card not found', 404);
            // }

            $isPriceInCents = (is_int($sticker->price) && $sticker->price >= 10 && $sticker->price <= 500);
            $amount = $sticker->price;
            $amountInCents = $isPriceInCents ? (int)$amount : (int)($amount * 100);

            try {
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => $amountInCents,
                    'currency' => 'usd',
                    'customer' => $user->customer_id,
                    'off_session' => true,
                    'confirm' => true,
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);

                if ($paymentIntent->client_secret != null) {
                    $userSticker->receipt = $paymentIntent->id;
                    $userSticker->source = 'stripe';
                    $userSticker->save();

                    return response()->json([
                        'status' => 1,
                        "message" => "Sticker purchased successfully.",
                        'clientSecret' => $paymentIntent->client_secret,
                        'dpmCheckerLink' => "https://dashboard.stripe.com/settings/payment_methods/review?transaction_id={$paymentIntent->id}",
                    ]);
                }
            } catch (\Exception $e) {
                return $this->errorResponse($e->getMessage(), 400);
            }
        } else {
            $userSticker = new UserSticker();
            $userSticker->user_id = $user->id;
            $userSticker->sticker_id = $request->sticker_id;
            $userSticker->type = $sticker->sticker_type;
            $userSticker->save();

            $userPoints = UserPoints::where('user_id', $user->id)->first();
            $userPoints->user_points -= $sticker->price;
            $userPoints->save();

            return response()->json([
                'status' => 1,
                "message" => "Sticker redeemed successfully.",
                'data' => $userSticker,
            ]);
        }
    }
    // public function purchaseSticker(Request $request)
    // {
    //     $this->validate($request, [
    //         'sticker_id' => 'required',
    //         'card_id' => 'nullable|integer'
    //     ]);

    //     $sticker = Sticker::findOrFail($request->sticker_id);  // Find the sticker or return 404
    //     $user = auth()->user();  // Get the authenticated user

    //     // Check if the user has already purchased the sticker
    //     $isAlreadyPurchased = UserSticker::where('sticker_id', '=', $sticker->id)
    //         ->where('user_id', '=', $user->id)  // Add user check
    //         ->first();

    //     if ($isAlreadyPurchased) {
    //         return response()->json(['status' => 'OK', 'message' => 'Sticker already purchased!']);
    //     } elseif($sticker->type == "premium") 
    //     {
    //         $userSticker = new UserSticker();
    //         $userSticker->user_id = $user->id;
    //         $userSticker->sticker_id = $request->sticker_id;
    //         $userSticker->type = $sticker->sticker_type;
    //         $userSticker->save();

    //         $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
    //         $stripeCard = StripeCard::where(['user_id' => $user->id, 'id' => $request->card_id])->first();
    //         if (!$stripeCard) {
    //             return $this->errorResponse('Card not found', 404);
    //         }
    //         $isPriceInCents = (is_int($sticker->price) && $sticker->price >= 10 && $sticker->price <= 500);
    //         $amount = $sticker->price;

    //         if ($isPriceInCents) {
    //             // If the price is already in cents, no need to convert
    //             $amountInCents = (int) $amount;
    //         } else {
    //             // If the price is in dollars, multiply by 100 to convert to cents
    //             $amountInCents = (int) ($amount * 100);
    //         }
    //         $charge = $stripe->charges->create([
    //             'amount' => $amountInCents,
    //             'currency' => 'usd',
    //             'source' => $stripeCard->token,
    //             'customer' => $user->id,
    //         ]);

    //         // dd($charge);
    //         if ($charge->status !== 'succeeded') {
    //             return $this->errorResponse($charge->error->message, 400);
    //         }else{
    //             $userSticker->receipt = $stripeCard->token;
    //             $userSticker->source = 'stripe';
    //             $userSticker->save();
    //             return response()->json([
    //                 'status' => 1,
    //                 "message" => "Sticker purchased successfully.",
    //                 'data' => $userSticker,
    //             ]);
    //         }
    //     }else
    //     {
    //         $userSticker = new UserSticker();
    //         $userSticker->user_id = $user->id;
    //         $userSticker->sticker_id = $request->sticker_id;
    //         $userSticker->type = $sticker->sticker_type;
    //         $userSticker->save();
            
    //         $userPoints = UserPoints::where('user_id',$user->id)->first();
    //         $userPoints = $userPoints->user_points - $sticker->price;
    //         $userPoints->save();
    //         return response()->json([
    //             'status' => 1,
    //             "message" => "Sticker redeemed successfully.",
    //             'data' => $userSticker,
    //             ]);
    //     }
    // }
}
