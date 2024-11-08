<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\Follower;
use App\Models\User;
use App\Models\Chat;
use App\Models\ChatDelete;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\FAQ;
use App\Models\BuySubscription;
use ReceiptValidator\iTunes\Validator as iTunesValidator;
use ReceiptValidator\GooglePlay\Validator as PlayValidator;
use App\Http\Resources\AuthResource;
use App\Models\StripeCard;
use Stripe\StripeClient;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Illuminate\Support\Facades\Log;


class CoreController extends Controller
{

    use ApiResponser;

    /** Chat Message */

    public function chatMessages()
    {
        $user = Auth::user();

        $c1 = Conversation::leftJoin('users', function ($join) {
            $join->on('conversations.receiver_id', '=', 'users.id');
        })
            ->where('conversations.sender_id', $user->id)
            ->select([
                'conversations.sender_id',
                'conversations.receiver_id',
                'conversations.type',
                'conversations.last_message',
                'conversations.created_at',
                'users.id',
                'users.full_name',
                'users.profile_image',
            ])
            ->whereNotIn('receiver_id', function ($query) use ($user) {
                $query->select('receiver_id')
                    ->from(with(new ChatDelete)->getTable())
                    ->where('user_id', $user->id);
            })
            ->whereNotIn('sender_id', function ($query) use ($user) {
                $query->select('receiver_id')
                    ->from(with(new ChatDelete)->getTable())
                    ->where('user_id', $user->id);
            });

        $c2 = Conversation::leftJoin('users', function ($join) {
            $join->on('conversations.sender_id', '=', 'users.id');
        })
            ->where('conversations.receiver_id', $user->id)
            ->select([
                'conversations.sender_id',
                'conversations.receiver_id',
                'conversations.type',
                'conversations.last_message',
                'conversations.created_at',
                'users.id',
                'users.full_name',
                'users.profile_image',
            ])
            // ->union($c1);
            ->whereNotIn('receiver_id', function ($query) use ($user) {
                $query->select('receiver_id')
                    ->from(with(new ChatDelete)->getTable())
                    ->where('user_id', $user->id);
            })
            ->whereNotIn('sender_id', function ($query) use ($user) {
                $query->select('receiver_id')
                    ->from(with(new ChatDelete)->getTable())
                    ->where('user_id', $user->id);
            });

        $chat = $c1->union($c2)->orderBy('created_at', 'desc')->get();

        // $chat = $c2->select('conversations.sender_id','conversations.receiver_id','conversations.type','conversations.last_message','conversations.created_at','users.id', 'users.full_name', 'users.profile_image')->get();

        $data = [];
        foreach ($chat as $c) {
            $d1 = strtotime($c->created_at);
            $d2 = strtotime(Carbon::now());

            $datediff = $d2 - $d1;
            $days = round($datediff / (60 * 60 * 24));

            if ($days < 1) {
                $day = "Today";
            } elseif ($days == 1) {
                $day = $days . " day ago";
            } else {
                $day = $days . " days ago";
            }

            $data[] = [
                "sender_id" => $c->sender_id,
                "receiver_id" => $c->receiver_id,
                "type" => $c->type,
                "last_message" => $c->last_message,
                "created_at" => $c->created_at,
                "id" => $c->id,
                "full_name" => $c->full_name,
                "profile_image" => $c->profile_image,
                "days" => $day
            ];
        }


        if ($chat->count() > 0) {
            return response()->json([
                "status"    =>  1,
                "message"   =>  "Message Found",
                "data"      =>  $data
            ]);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Message Not Found",
                "data" => $data
            ]);
        }
    }

    public function chatAttachment(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "attachment" => "required"
        );
        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 0,
                    'message' => $validator->errors()->all()[0]
                ],
                400
            );
        }

        if ($request->hasFile('attachment')) {
            // $imageName = time().'.'.$request->attachment->getClientOriginalExtension();
            // $request->attachment->move(public_path('/uploadedattach'), $imageName);

            // $message = asset('public/uploadedattach')."/".$imageName;

            $uploadFolder = 'chat_attachment';
            $image = $request->file('attachment');
            $message = $image->store($uploadFolder, 'public');

            return response()->json([
                "status"    =>  1,
                "message"   =>  "Attachment Done",
                "data"      =>  array("attachUrl" => $message)
            ]);

            // }

        }
    }


    public function deleteChat(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "receiver_id" => "nullable|exists:users,id"
        );
        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 0,
                    'message' => $validator->errors()->all()[0]
                ],
                400
            );
        }

        // $column = $request->has("group_id") ? 'group_id' : 'receiver_id';
        // $id = $request->has("group_id") ? $request->group_id : $request->receiver_id;

        // if($request->has('receiver_id'))
        // {
        //     $chats = Chat::where('sender_id', $id)->orWhere('receiver_id',$id)->get();
        // }
        // else
        // {
        //     $chats = Chat::where($column, $id)->get();
        // }

        $chats = Chat::where('sender_id', $request->receiver_id)
            ->orWhere('receiver_id', $request->receiver_id)->get();

        if ($chats->isEmpty()) {
            return $this->error('Chat not found.', 400);
        }

        foreach ($chats as $chat) {
            $isChatNotDeleted = Chat::where('id', $chat->id)
                ->where('delete_convo', 0)->first();

            if (!$isChatNotDeleted) {
                if ($chat->delete_convo != $user->id) {
                    Chat::destroy($chat->id);
                }
            } else {
                $removeChat = Chat::find($chat->id);
                $removeChat->delete_convo = $user->id;
                $removeChat->save();
            }
        }


        $check = ChatDelete::where("receiver_id", $request->receiver_id)
            ->where('user_id', $user->id)->first();

        if ($check) {
            return $this->error('Already deleted.', 400);
        }

        $delete = new ChatDelete;
        $delete->user_id = $user->id;
        $delete->receiver_id = $request->receiver_id;
        $delete->save();

        return $this->success('Chats deleted successfully!');
    }

    /** Notifications */

    public function notifications()
    {
        $user = Auth::user();

        // $notifications = Notification::where('receiver_id',$user->id)->get();
        $notifications = Notification::where('receiver_id', $user->id)->with('user:id,full_name,profile_image')->get();

        if ($notifications->count() > 0) {

            return response()->json([
                "status"    =>  1,
                "message"   =>  "Notifications Found",
                "data"      =>  $notifications
            ], 200);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Notifications Not Found"
            ], 400);
        }
    }

    public function notifications_settings(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "notification" => "required|in:0,1"
        );
        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 0,
                    'message' => $validator->errors()->all()[0]
                ],
                400
            );
        } else {

            $update = User::where('id', $user->id)->update(['notification' => $request->notification]);
            if ($update) {

                return response()->json([
                    'status' => 1,
                    'message' => 'Notification Updated',
                    "data" => new AuthResource(User::find($user->id))
                ], 200);
            }
        }
    }


    /** Add Notification */

    public function add_notification($sender_id, $receiver_id, $type, $post_id, $message)
    {
        $check = User::where('id', $receiver_id)->first();
        if ($check) {
            $notification = new Notification;
            $notification->sender_id = $sender_id;
            $notification->receiver_id = $receiver_id;
            $notification->post_id = $post_id;
            $notification->type = $type;
            $notification->message = $message;
            $notification->save();

            //Push Notification
            $firebase = User::where('id', $receiver_id)->first();
            // $firebaseToken = User::where('id',$receiver_id)->first()->device_token;

            if ($firebase->notification == 1) {
                $firebaseToken = $firebase->device_token;

                // $SERVER_API_KEY = 'AAAAZ3ZrAcE:APA91bFonoDQW__pkxUiPynIyh4cVDRNTCEMYM_PLup_5hDV2KC6exmSeVm1GR1FKr9W8XG8-X8usF8I7tI0EX-ukFoCbvYINBMhLnalth0VBS5NLfHn89qX4o4Xpo2YT5h1URU0GHgl';

                // $data = [
                //     "to" => $firebaseToken->device_token,
                //     "data"=>[
                //         "title" => 'Sweet Dreams',
                //         "type" => $type,
                //         "body" =>$message ,
                //     ],
                //     "notification"=>[
                //         "title" => 'Sweet Dreams',
                //         "type" => $type,
                //         "body" =>$message ,
                //     ]
                // ];
                // $dataString = json_encode($data);
                // $headers = [
                //     'Authorization: key=' . $SERVER_API_KEY,
                //     'Content-Type: application/json',
                // ];
                // $ch = curl_init();
                // curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                // curl_setopt($ch, CURLOPT_POST, true);
                // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                // $response = curl_exec($ch);

                $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/serviceAccountKey.json'));
                $messaging = $factory->createMessaging();

                $firebaseNotification = FirebaseNotification::create('Sweet Dreams', $message);

                $data = [
                    'type' => $type,
                    'body' => $message,
                    'sound' => 'default', // Add the sound property directly here
                ];
                if ($firebaseToken != null) {
                    $message = CloudMessage::withTarget('token', $firebaseToken)
                        ->withNotification($firebaseNotification)
                        ->withData($data);

                    try {
                        $response = $messaging->send($message);
                        Log::info('Push notification sent successfully', ['response' => $response]); // Log the response
                    } catch (InvalidMessage $e) {
                        Log::error('InvalidMessage Exception: ' . $e->getMessage()); // Log the error
                    } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                        Log::error('NotFound Exception: ' . $e->getMessage()); // Log NotFound error
                    } catch (\Exception $e) {
                        Log::error('Exception: ' . $e->getMessage()); // Log any other errors
                    }
                }
            }
        }
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::all();

        if ($subscriptions) {

            return response()->json([
                "status"    =>  1,
                "message"   =>  "Subscriptions",
                "data"      =>  $subscriptions
            ], 200);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Subscriptions Not Found"
            ], 400);
        }
    }


    //FAQs
    public function faqs()
    {
        $faqs = FAQ::get();
        if (count($faqs) > 0) {
            return $this->success('FAQs found successfully', $faqs);
        } else {
            return $this->error('Not found.', 400);
        }
    }

    //Follow Unfollow User

    public function followUser(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "follow_id" => "required|exists:users,id"
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }

        $check = Follower::where('user_id', $user->id)
            ->where('follower_id', $request->follow_id)->whereIn('status', ['accepted', 'rejected'])->first();
        if ($check) {
            Follower::where('user_id', $user->id)->where('follower_id', $request->follow_id)->delete();
            return $this->success('Unfollowed successfully.');
        } else {
            $check = Follower::where('user_id', $user->id)
                ->where('follower_id', $request->follow_id)->where('status', 'pending')->first();
            if ($check) {
                return $this->success('Already sent request.');
            }

            $follow = new Follower;
            $follow->user_id = $user->id;
            $follow->follower_id = $request->follow_id;
            $follow->save();

            //Notification
            $sender_id = $user->id;
            $receiver_id = $request->follow_id;
            $type = "Send-Request";
            $post_id = $follow->id;
            $message = $user->full_name . " send you a friend request. ";

            if ($sender_id != $receiver_id) {
                $notify = new CoreController;
                $notify->add_notification($sender_id, $receiver_id, $type, $post_id, $message);
            }

            return $this->success('Follow request successfully.');
        }
    }

    public function updatefollowUser(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "request_id" => "required|exists:followers,id",
            "status" => "required|in:accepted,rejected"
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }
        if ($request->status == 'rejected') {
            $req = Follower::where('id', $request->request_id)->delete();
        } else {
            $req = Follower::where('id', $request->request_id)->update(["status" => $request->status]);
        }

        if ($req) {
            $delete_noti = Notification::where('post_id', $request->request_id)->where('type', 'Send-Request')->delete();
            return $this->success('Request ' . $request->status . ' successfully.');
        }
    }

    public function searchFollower(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        $name = $request->name;

        // Search for users by full name
        $follow = User::where('full_name', 'LIKE', '%' . $name . '%')->get();

        $follow_user = [];

        if ($follow->count() > 0) {
            foreach ($follow as $f) {
                // Check if the found user is in the authenticated user's follower list
                $check_follower = Follower::where('user_id', $user->id)
                    ->where('follower_id', $f->id)
                    ->where('status', 'accepted')
                    ->first();

                if ($check_follower) {
                    // Add the user's details to the follow_user array if they are a follower
                    $follow_user[] = [
                        'id' => $f->id,
                        'full_name' => $f->full_name,
                        'email' => $f->email,
                        'profile_image' => $f->profile_image
                    ];
                }
            }

            // Return the filtered users with their details as a response
            return $this->success('Follow request successfully.', $follow_user);
        } else {
            return $this->error('Not found.', 400);
        }
    }

    public function listofFollowers(Request $request)
    {
        $user = Auth::user();
        $user_id = $request->user_id;
        $follower = Follower::with('user')->where('follower_id', $user_id)->where('status', 'accepted')->get();
        if (count($follower) > 0) {
            return $this->success('List of followers found.', $follower);
        } else {
            return $this->error('Not found.', 400);
        }
    }


    public function listofFollowing(Request $request)
    {
        $user = Auth::user();
        $user_id = $request->user_id;
        $follower = Follower::with('follower')->where('user_id', $user_id)->where('status', 'accepted')->get();
        if (count($follower) > 0) {
            return $this->success('List of followings found.', $follower);
        } else {
            return $this->error('Not found.', 400);
        }
    }


    public function buySubscription(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            'receipt' => "required",
            'type' => 'required|in:dream_dictionary,dream_stickers,dream_connection', //1, 3 , 6 months
            'source' => 'required|in:google,apple', //google or apple
        );

        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }

        $check = BuySubscription::where('type', $request->type)
            ->where('user_id', $user->id)
            ->where('source', $request->source)
            ->where('receipt', $request->receipt)
            ->first();
        if ($check) {
            $data = [
                "data" => $check,
                // "user" => new UserResource($user)
                "user" => $user
            ];

            $user["user_id"] = $user->id;

            return response()->json([
                "status" => 0,
                "message" => "You already availed this subscription.",
                "data" => $user
            ], 400);
        }

        $check2 = BuySubscription::where('user_id', $user->id)->where('source', $request->source)->first();
        if ($check2) {
            $del = BuySubscription::where('user_id', $user->id)->where('source', $request->source)->delete();
        }

        $receipt = $request->receipt;


        if ($request->source == 'apple') {
            // $this->check_ios($request->receipt);
            $auth_receipt = $this->check_ios($receipt);

            // dd($auth_receipt);
        } elseif ($request->source == 'google') {
            // $auth_receipt = $this->check_android_session($receipt, $request->type,$request->package_name,$request->purchase_token);

            // $auth_receipt = $this->check_android($receipt, $request->type,$request->package_name,$request->purchase_token);
            $auth_receipt = $receipt;
        }

        $expiry_date = Carbon::now()->addMonths(1);

        //Check subscription type
        // if($request->type == 'monthly'){ // monthly
        //     $expiry_date = Carbon::now()->addMonths(1);
        // }
        // elseif($request->type == 'quarterly'){ // 3 months
        //     $expiry_date = Carbon::now()->addMonths(3);
        // }
        // elseif($request->type == 'half_yearly'){ // 6 months
        //     $expiry_date = Carbon::now()->addMonths(6);
        // }
        // elseif($request->type == 'yearly'){ // 12 months
        //     $expiry_date = Carbon::now()->addMonths(12);
        // }
        // else{
        //     $expiry_date = Carbon::now(); // Default
        // }

        $subscription = new BuySubscription;
        $subscription->user_id = $user->id;
        $subscription->type = $request->type;
        $subscription->expiry_date = $expiry_date;
        // $subscription->receipt = json_encode($request->receipt);
        $subscription->receipt = $request->receipt;
        $subscription->source = $request->source;

        if ($subscription->save()) {
            $user->isSubscribed = 1;
            $user->save();
            // $user = User::where('id',$user->id)->update(['isSubscribed' => 1]);

            $data = [
                "receipt_data" => ($auth_receipt) ? $auth_receipt : null,
                "data" => $subscription,
                // "user" => new UserResource($user)
                "user" => Auth::user()
            ];

            $user["user_id"] = $user->id;
            return response()->json([
                "status" => 1,
                "message" => "Subscription availed successfully.",
                "data" => $user
            ], 200);
        } else {
            return $this->error('Error in Subscription', 400);
        }
    }

    public function check_ios($receipt)
    {
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION); // Or iTunesValidator::ENDPOINT_SANDBOX if sandbox testing

        $receiptBase64Data = $receipt;

        try {
            $response = $validator->setReceiptData($receiptBase64Data)->validate();
            // $sharedSecret = '1234...'; // Generated in iTunes Connect's In-App Purchase menu
            // $response = $validator->setSharedSecret($sharedSecret)->setReceiptData($receiptBase64Data)->validate(); // use setSharedSecret() if for recurring subscriptions
        } catch (\Exception $e) {
            echo 'got error = ' . $e->getMessage() . PHP_EOL;
        }

        if ($response->isValid()) {
            // echo 'Receipt is valid.' . PHP_EOL;
            // echo 'Receipt data = ' . print_r($response->getReceipt()) . PHP_EOL;

            return $response->getReceipt();

            foreach ($response->getPurchases() as $purchase) {

                echo 'getProductId: ' . $purchase->getProductId() . PHP_EOL;
                echo 'getTransactionId: ' . $purchase->getTransactionId() . PHP_EOL;

                if ($purchase->getPurchaseDate() != null) {
                    echo 'getPurchaseDate: ' . $purchase->getPurchaseDate()->toIso8601String() . PHP_EOL;
                }
            }
        } else {
            // echo 'Receipt is not valid.' . PHP_EOL;
            // echo 'Receipt result code = ' . $response->getResultCode() . PHP_EOL;
            return $response->getResultCode();
        }
        // dd($response);
    }


   
}
