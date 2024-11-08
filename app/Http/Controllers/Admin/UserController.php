<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index() {
        $users = User::where('user_type','!=','admin')->where('user_type','!=','staff')->get();
        // dd( $users );
        return view( 'pages.users.index', compact( 'users' ) );
    }

    public function updateStatus($id){
        $user =  User::findOrFail($id);
        if($user->is_blocked == 1){
            $user->is_blocked = 0;
        }else{
            $user->is_blocked = 1;
        }
        $user->save();
        return redirect()->route( 'admin.users' )->with( 'flash_message', 'User status updated successfully.' );

    }

    public function verifyUser($id){
        $user =  User::findOrFail($id);
        if($user->is_verified == 1){
            $user->is_verified = 0;
        }else{
            $user->is_verified = 1;
        }
        $user->save();
        return redirect()->route( 'admin.users' )->with( 'flash_message', 'User verification updated successfully.' );

    }
}
