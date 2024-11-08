<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\AuthResource;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\Post;
use App\Models\Follower;

class AuthController extends Controller
{
    use ApiResponser;


    /** Register user */

    public function signup(Request $request)
    {
        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'password.regex' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable',
            'email' => 'required|unique:users|email|max:255',
            'phone_number' => 'numeric',
            'password' => 'required|min:8|max:255|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'password_confirmation'=> 'required|min:8',
            'profile_image' => 'nullable',
        ],$customMessages);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $otp = 123456;//random_int(100000, 999999);

        $user = new User;
        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->password = Hash::make($request->password);
        $user->otp = $otp;

        // if($request->hasFile('profile_image')){
        //     $imageName = time().'.'.$request->profile_image->getClientOriginalExtension();
        //     $request->profile_image->move(public_path('/uploadedimages'), $imageName);
        //     $file_path = asset('uploadedimages')."/".$imageName;

        //     $user->profile_image = $file_path;
        // }

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path = Storage::url($profile_image);
            $user->profile_image = $path;
            // $completeProfile['profile_image'] = $path;
        }

        if($user->save()){

            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
            $customers_create = $stripe->customers->create([
                'name'  => $user->full_name,
                'email' => $user->email
            ]);

            User::whereId($user->id)->update(['customer_id' => $customers_create->id]);

            $details = [
                'subject' => 'Verify your email',
                'email' => $request->email,
                'otp' => $otp,
                'view' => 'emails.verify-email'
            ];

            // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

            $data = [
                'email' => $request->email,
                'password' => $request->password
            ];

            auth()->attempt($data);

            // $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 1,
                'message' => "OTP verification code has been sent to your email address.",
                // 'token' => $token,
                'data' => [
                    'user_id' => $user->id
                ]

            ], 200);
        }
        else{
            return $this->error('Sign Up Not Processed', 400);
        }
    }


    /** OTP verify */

    public function verification(Request $request)
    {
        $controls=$request->all();
        $rules=array(
            "otp"=>"required|numeric",
            "user_id"=>"required|exists:users,id",
            'type' => 'required|in:forgot,verification'
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'numeric' => 'The :attribute must be numeric',
            'exists' => 'The :attribute does not exist',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }
        $user=User::where([['id','=',$request->user_id],['otp','=',$request->otp]])->first();

        if($user){
            if($request->type == "forgot"){
                User::where('id', $request->id)->update(['device_token'=>$request->device_token,'is_forgot' => "1","otp"=>null]);
                $check_otp = DB::table("password_resets")
                    ->where(["token" => $request->otp, "email" => $request->email])
                    ->first();

                if ($check_otp) {
                    $totalDuration = Carbon::parse($check_otp->created_at)->diffInHours(
                        Carbon::now()
                    );
                    if ($totalDuration > 1) {
                        return response()->json([
                            "status" => 0,
                            "message" => "OTP Expired",
                        ]);
                    }
                    return response()->json([
                        "status" => 1,
                        "message" => "OTP Verified Successfully",
                    ]);
                }
            }elseif($request->type == "verification"){
                User::where('id', $request->id)->update(['device_token'=>$request->device_token,'is_signup' => "1","otp"=>null]);
            }

            Auth::loginUsingId($user->id, true);
            $token = $user->createToken('authToken')->plainTextToken;
            $user->email_verified_at = Carbon::now();
            $user->account_verified = 1;
            $user->save();
            $user["user_id"] = $user->id;

            return response()->json([
                'status'=>1,
                'message'=>'Account validation completed. You can login now! (web) / OTP verified  (phone).',
                'token'=>$token,
                'data'=> new AuthResource($user),

            ],200);
        }
        else{
            return response()->json([
                'status'=>0,
                'message'=>'Invalid OTP verification code'
            ],400);
        }
    }


    /** Resend code */

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $user = User::where(['id' => $request->user_id])->first();

        if(!empty($user)){
            $otp = 123456;//random_int(100000, 999999);

            User::whereId($user->id)->update(['otp' => $otp]);

            $details = [
                'subject' => 'Verify your email',
                'email' => $request->email,
                'otp' => $otp,
                'view' => 'emails.verify-email'
            ];

            // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

            // return $this->success('We have resend  OTP verification code at your email address');
            return response()->json([
                "status" => 1,
                "message" => 'We have resend  OTP verification code at your email address'
            ],200);
        }
        else{
            return $this->error('User not found.', 404);
        }
    }


    /** Login */

    public function login(Request $request)
    {

        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'exists' => 'Invalid Email Address',
            'password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'The password must be at least 8 characters and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'The password must be at least 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email|max:255',
            'password' => 'required|min:8|max:255',
            'device_type' => 'required',
            'device_token' => 'required'
        ],$customMessages);


        if ($validator->fails()) {
            // return $this->error($validator->errors()->all()[0], 400);
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0]
            ],400);
        }

        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        $user = User::where('email', $request->email)->first();

        if(!empty($user)){

            if(Hash::check($request->password, $user->password)){
                if (auth()->attempt($data)) {

                    if(auth()->user()->account_verified == 0){

                        $otp = 123456;//random_int(100000, 999999);

                        User::whereId($user->id)->update(['otp' => $otp]);

                        $details = [
                            'subject' => 'Verify your email',
                            'email' => $request->email,
                            'otp' => $otp,
                            'view' => 'emails.verify-email'
                        ];

                        // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

                        return response()->json([
                            'status' => 1,
                            'message' => 'Please verify your account, OTP successfully sent to your email address',
                            // 'data' => auth()->user(),
                            'data' => ['user_id' => $user->id, 'is_verified' => $user->account_verified]
                        ], 200);
                    }
                    else{

                        User::whereId(auth()->user()->id)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token]);
                        $user->tokens()->delete();
                        $token = $user->createToken('LaravelAuthToken')->plainTextToken;
                        $user->device_type=$request->device_type;
                        $user->device_token=$request->device_token;
                        // $user->api_token = $token;
                        $user->save();
                        $user["user_id"] = $user->id;

                        return response()->json([
                            'status' => 1,
                            'message' => 'User successfully logged in',
                            'token' => $token,
                            // 'data' => auth()->user(),
                            'data' => new AuthResource($user)
                        ], 200);
                    }
                }
                else {
                    return $this->error('Unauthorised', 401);
                }
            }
            else{
                return $this->error('Password is incorrect', 400);
            }
        }
        else{
            return $this->error('Email is incorrect', 400);
        }
    }


    /** Forgot password */

    public function forgotPassword(Request $request)
    {

        $controls=$request->all();
        $rules=array(
            'email'=>'required|email|exists:users,email'
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }
        else{
            $user = User::where('email',$request->email)->first();
            if(!empty($user)){
                // $token = rand(100000,999999);
                DB::table('password_resets')->where(['email'=>$request->email])->delete();
                $token = 123456;
                DB::table('password_resets')->insert([
                    'email'=>$user->email,
                    'token'=> $token,
                    'created_at'=>Carbon::now()
                ]);
                // $user->notify(new PasswordResetNotification($token));
                return response()->json([
                    'status'=>1,
                    'message'=>'OTP verification code has been sent to your email address',
                    'data'=> ['user_id' => $user->id]
                ],200);
            }
            else{
                return response()->json([
                    'status'=>0,
                    // 'message'=>'Your Account Is Not Verified Please Verify Your Account...!'
                    'message'=>'User Not Found'
                ],400);
            }
        }
    }

    /** Forgot password resend OTP */

    public function forgotPasswordResendOtp(Request $request){
        $controls=$request->all();
        $rules=array(
            "email"=>"required|exists:users,email"
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $user = User::where('email',$request->email)->first();

        // $token = rand(100000,999999);
        $token = 123456;
        DB::table('password_resets')->where(['email'=>$request->email])->delete();
        DB::table('password_resets')->insert([
            'email'=>$user->email,
            'token'=> $token,
            'created_at'=>Carbon::now()
        ]);
        // $user->notify(new PasswordResetNotification($token));

        return response()->json(['status'=>1,'message'=>'We have resend  OTP verification code at your email address'],200);

    }


    /** Reset password */

    public function resetPassword(Request $request)
    {
        $customMessages = [
            'email.email' => 'Invalid email address',
            'required' => ":attribute can't be empty",
            'password.regex' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:8|max:255|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/'
        ], $customMessages);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $check_otp = DB::table('password_resets')->where(['email'=>$request->email])->first();
        if($check_otp){
            $user = User::where('email',$check_otp->email)->first();
            $user->password = bcrypt($request->password);
            $user->save();
            DB::table('password_resets')->where(['email'=>$request->email])->delete();
            return response()->json(['status'=>1,'message'=>"Password updated successfully"],200);
        }
        else{
            return response()->json(['status'=>0,'message'=>"User Not Found"],400);
        }
    }


    /** Change password */
    public function changePassword(Request $request)
    {
        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'new_password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'new_password.min' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'new_password.max' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.min' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.max' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.required' => ':attribute field is required',
            'confirm_password.same' => 'New Password and Confirm Password must be same',

        ];

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:255|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'confirm_password' => 'required|same:new_password'
        ],$customMessages);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        if($request->old_password == $request->new_password)
        {
            return $this->error("Old password and New password can't be same", 400);
        }

        if(Hash::check($request->old_password, auth()->user()->password)){
            $update_password = $request->user()->update(['password' => Hash::make($request->new_password)]);
            if($update_password){
                return $this->success('Password updated successfully.');
            }
            else{
                return $this->error('Something went wrong.', 400);
            }
        }
        else{
            return $this->error('Old Password Is Incorrect.', 400);
        }
    }

    /** Logout */

    public function logout(Request $request)
    {
        $user_id = auth()->user()->id;
        $user_obj = User::whereId($user_id)->count();

        if($user_obj > 0){
            // $sentum_delete = $request->user()->currentAccessToken()->delete();
            $sentum_delete = $request->user()->tokens()->delete();
            if($sentum_delete){
                $update_user = User::whereId($user_id)->update(['device_type' => null, 'device_token' => null]);
                if($update_user){
                    return $this->success('User logout successfully.');
                }else{
                    $this->error('Sorry there is some problem while updating user data.', 400);
                }
            }else{
                $this->error('Sorry there is some problem while deleting old token.', 400);
            }
        }
        else{
            return $this->error('User not found', 404);
        }
    }


    /** Social login */

    public function socialAuth(Request $request)
    {
        $controls=$request->all();
        $rules=array(
            'access_token'=>'required',
            'provider'=>'required|in:facebook,google,apple,phone',
            'device_type' => 'nullable',
            'device_token' => 'nullable',
            'email' => 'nullable',
            'name' => 'nullable',
            'phone_number' => 'nullable'
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute already exists',
            'exists' => 'The :attribute does not exist',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        // $auth = app('firebase.auth');
        // Retrieve the Firebase credential's token
        $user_social_token = $request->access_token;

        // try {
        //     $verifiedIdToken = $auth->verifyIdToken($idTokenString);

        //     $user_social_token = $verifiedIdToken->claims()->get('sub');
        //     $get_user = $auth->getUser($user_social_token);

        //dd($get_user);

        $user = User::where('user_social_token',$user_social_token)->where('user_social_type',$request->provider)->first();
        if(!$user){


            // $check = User::where('email',$request->email)->first();
            // if($check)
            // {
            //    // if($check->user_social_type == null)
            //    // {
            //        return response()->json([
            //            'status'=>0,
            //            'message'=>"This Email Already Exists"
            //        ],400);
            //    // }
            // }


            $user = new User();

            // if($get_user->email == null){
            //     foreach($get_user->providerData as $dat){
            //         $user->email = $dat->email;
            //     }
            // }
            // else{
            //     $user->email = $get_user->email;
            // }
            // $user->otp = null;
            $user->full_name = ($request->name)?$request->name:null;
            $user->email = ($request->email)?$request->email:null;
            $user->email_verified_at = Carbon::now();
            $user->phone_number = $request->phone_number;
            $user->device_type = $request->device_type;
            $user->device_token= $request->device_token;
            $user->account_verified = 1;

            $user->is_social = 1;
            $user->user_social_token = $user_social_token;
            $user->user_social_type = $request->provider;
            // $user->profile_image = null;
            // $user->is_profile_complete = 0;
            // $user->api_token = null;

            // $user->is_card = 1;



            $user->save();
        }

        $user->tokens()->delete();
        $token =$user->createToken('authToken')->plainTextToken;
        // $user->api_token = $token;
        $user->save();
        return response()->json([
            'status'=>1,
            'message'=>'Login successfully',
            'data'=>new AuthResource($user),
            'token'=>$token
        ],200);


        // } catch (\InvalidArgumentException $e) { // If the token has the wrong format
        //     return response()->json(['status'=>0,
        //         'message' => 'Unauthorized - Can\'t parse the token: ' . $e->getMessage()
        //     ],401);

        // } catch (InvalidToken $e) { // If the token is invalid (expired ...)
        //     return response()->json([
        //         'status'=>0,
        //         'message' => 'Unauthorized - Token is invalid: ' . $e->getMessage()
        //     ],401);
        // }
    }

    /** User profile */

    public function userProfile(Request $request){

        // $user_id =  auth()->user()->id;

        $user_id = $request->user_id;

        $user_profile = User::where("id","=",$user_id)->first();

        $post = Post::where('user_id',$user_id)->count();
        $following = Follower::where('user_id',$user_id)->where('status','accepted')->count();
        $followers = Follower::where('follower_id',$user_id)->where('status','accepted')->count();

        $check = Follower::where('user_id',auth()->user()->id)->where('follower_id',$request->user_id)->first();

        $isFollowed = 0;
        if($check)
        {
            if($check->status == 'pending')
            {
                $isFollowed = 1;
            }
            elseif($check->status == 'accepted')
            {
                $isFollowed = 2;
            }
        }

        $data = [
            "user" => new AuthResource($user_profile),
            "post" => $post,
            "followers" => $followers,
            "following" => $following,
            'isFollowed' => $isFollowed
        ];

        if(!empty($user_id)){
            return response()->json([
                'status' => 1,
                'message' =>'user profile',
                'data' => $data
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' =>'user profile not found...!',
            'data' => $user_profile,
        ]);
    }


    /** Complete profile */


    public function completeProfile(Request $request)
    {
        $userId = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable',
            'dob' => 'nullable',
            'profile_image' => 'nullable',
            'bio' => 'nullable',
            'language' => 'nullable'
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        if($request->hasFile('profile_image')){
            $uploadFolder = 'profile_image';

            $image = $request->profile_image;
            // $submition_data['profile_image']  = $image->store($uploadFolder, 'public');
        // }

        // if($request->hasFile('profile_image')){
        //     $profile_image = $request->profile_image->store('public/profile_image');
        //     $path = Storage::url($profile_image);
        //     // $completeProfile['profile_image'] = $path;

            $submition_data = $request->all();
            $submition_data['profile_image'] = $image->store($uploadFolder, 'public');
            $submition_data['is_profile_complete'] = '1';
        }

        // if ($request->hasFile('profile_image')) {
        //     $imageName = time() . '.' . $request->profile_image->getClientOriginalExtension();
        //     $request->profile_image->move(public_path('/uploadedimages'), $imageName);
        //     $file_path = asset('uploadedimages') . "/" . $imageName;
        //     //$user->profile_image=$imageName;

        //     $submition_data = $request->all();
        //     $submition_data['profile_image'] = $file_path;
        //     $submition_data['is_profile_complete'] = '1';
        // }

        else {
            $submition_data = $request->all();
            $submition_data['is_profile_complete'] = '1';
        }

        $update_user = User::whereId($userId)->update($submition_data);

        if ($update_user) {
            return $this->success('Profile complete successfully.', new AuthResource(User::find($userId)));
        } else {
            $this->error('Sorry there is some problem while updating profile data.', 400);
        }
    }

    /** Select Language */

    public function selectLanguage(Request $request)
    {
        $user = auth()->user();

        $rules = array(
            'language' => 'required|in:English,Spanish'
        );


        $submition_data = $request->all();

        $customMessages = [
            'required' => 'The :attribute field is required.'
        ];

        $validator = Validator::make($submition_data,$rules,$customMessages);

        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $update_language = User::where('id', $user->id)->first();
        $update_language->language = $request->language;
        $update_language->update();

        $first_message = 'Language updated successfully';

        $second_message = 'Sorry there is some problem while updating the language';

        if($user && $update_language->language === 'Spanish'){

            $translator = new GoogleTranslate();

            $translator->setSource('auto');

            $translator->setTarget('es');

            $first_message = $translator->translate($first_message);

            $second_message = $translator->translate($second_message);

        }

        if ($update_language) {

            return response()->json([
                'status' => 1,
                'message' =>$first_message,
                'data'=> User::where('id', $user->id)->first()
            ]);

        } else {

            return response()->json([
                'status' => 0,
                'message' =>$second_message
            ]);

        }
    }

    public function deleteAccount()
    {
        $user = Auth::user();

        $delete = User::where('id',$user->id)->delete();

        if($delete)
        {
            return $this->success('Account deleted successfully.');
        }
        else
        {
            return $this->error('Account not deleted',400);
        }
    }

    public function becomeVerified(Request $request)
    {
        $userId = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'legal_name' => 'required',
            'driver_license' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $user = User::find($userId);

        if($request->hasFile('driver_license')){
            $uploadFolder = 'driver_license';

            $image = $request->driver_license;

            $user->legal_name = $request->legal_name;
            $user->driver_license = $image->store($uploadFolder, 'public');
            $user->save();

            return $this->success('User verified successfully.',new AuthResource($user));

        }
        else
        {
            return $this->error('Something went wrong',400);
        }
    }

}
