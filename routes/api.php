<?php


use App\Http\Controllers\API\AuthController;;

use App\Http\Controllers\API\ContentController;
use App\Http\Controllers\API\CoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\StickerController;
use App\Http\Controllers\API\StripeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


/* Authentication Module */

Route::controller(AuthController::class)->group(function () {
    Route::post('signup', 'signup')->name('signup');
    Route::post('login', 'login')->name('login');
    Route::post('verification', 'verification');
    Route::post('resend-otp', 'resendOtp');
    Route::post('social-login', 'socialAuth');


    Route::post('forgot-password', 'forgotPassword');
    Route::post('forgot-password-otp-verify', 'forgotPasswordOtpVerify');
    Route::post('forgot-password-resend-otp', 'forgotPasswordResendOtp');
    Route::post('reset-password', 'resetPassword');


    Route::controller(ContentController::class)->group(function () {
        Route::get("content", "content");
    });

    Route::group(['middleware' => 'auth:sanctum'], function () {

        Route::get('user-profile', 'userProfile');
        Route::post('change-password', 'changePassword');
        Route::post('complete-profile', 'completeProfile');
        Route::post('logout', 'logout');

        Route::get('delete-account', 'deleteAccount');

        Route::post('become-verified', 'becomeVerified');


        Route::controller(CoreController::class)->group(function () {

            //Chat
            Route::get('chat-messages', 'chatMessages');
            Route::post('delete-chat', 'deleteChat');
            Route::post('chat-attachment', 'chatAttachment');

            //Notification
            Route::get('notifications', 'notifications');
            Route::post('enable-notification', 'notifications_settings');

            //Subscription
            Route::get('subscriptions', 'subscriptions');
            Route::post('buy-subscription', 'buySubscription');

            Route::get('faqs', 'faqs');

            Route::post('follow', 'followUser');
            Route::post('update-follow-status', 'updatefollowUser');

            Route::get('search-followers', 'searchFollower');

            Route::get('list-of-followers', 'listofFollowers');
            Route::get('list-of-following', 'listofFollowing');


            // Add stripe card
            Route::post('add_card', 'addCard');
        });


        Route::controller(PostController::class)->group(function () {
            Route::prefix('post')->group(function () {

                Route::get('view', 'viewPosts');
                Route::get('detail/{id}', 'viewPostsDetail');

                Route::post('create', 'createPost');
                Route::post('edit', 'editPost');
                Route::post('delete', 'deletePost');

                Route::post('like', 'likePost');
                Route::post('comment', 'commentPost');
                Route::get('comment-detail', 'commentDetail');

                Route::get('like-detail', 'likeDetail');

                Route::post('report', 'reportPost');
                Route::post('hide', 'hidePost');
                Route::post('save', 'savePost');

                Route::get('view-save', 'viewSavePost');

                Route::get('view-journal', 'viewJournal');

                Route::get('search', 'searchPost');

                Route::get('search-filter', 'searchFilterJournal');

                Route::get('view-draft', 'viewDraft');
            });
        });


        Route::controller(StripeController::class)->group(function () {
                Route::get('create_merchant', 'createStripeAccount');
                Route::get('check/merchant/account', 'checkMerchantAccount');



                // Add stripe card
                Route::post('add_card', 'addCard');
                Route::get('card/list', 'listCard');
                Route::delete('card/delete', 'deleteCard');
                Route::post('card/set-as-default', 'setAsDefaultCard');
        });

        Route::controller(StickerController::class)->group(function () {
            Route::prefix('stickers')->group(function () {

                Route::get('all_stickers', 'index');
                Route::get('user_stickers', action: 'userStickers');
                Route::post('purchase_sticker', action: 'purchaseSticker');
            });
        });
    });
});

// //Registration
// Route::post('signup',[AuthController::class,'signup'])->name('signup');
// Route::post('verification',[AuthController::class,'verification'])->name('verification');
// Route::post('resend-otp',[AuthController::class,'resendOtp'])->name('resend-otp');

// //Login
// Route::post('login',[AuthController::class,'login'])->name('login');
// Route::post('social-login',[AuthController::class,'socialAuth'])->name('social-login');

// //Reset Password
// Route::post('forgot-password',[AuthController::class,'forgotPassword'])->name('forgot-password');
// Route::post("forgot-password-otp-verify",[AuthController::class,'forgotPasswordOtpVerify'])->name('forgot-password-otp-verify');
// Route::post("forgot-password-resend-otp",[AuthController::class,'forgotPasswordResendOtp'])->name('forgot-password-resend-otp');
// Route::post('reset-password',[AuthController::class,'resetPassword'])->name('reset-password');

// //Content
// Route::get("content",[ContentController::class,'content'])->name('content');

// //Auth
// Route::middleware('auth:sanctum')->group(function () {

// //Profile
//     Route::get("user-profile",[AuthController::class,'userProfile'])->name('user-profile');
//     Route::post("complete-profile",[AuthController::class,'completeProfile'])->name('complete-profile');
//     Route::post("change-password",[AuthController::class,'changePassword'])->name('change-password');
//     Route::post("logout",[AuthController::class,'logout'])->name('logout');

// //Chat
//     Route::get('chat-messages',[CoreController::class,'chatMessages'])->name('chat-messages');

// //Notification
//     Route::get('notifications',[CoreController::class,'notifications'])->name('notifications');

// //Subscription
//     Route::get('subscriptions',[CoreController::class,'subscriptions'])->name('subscriptions');
// });
