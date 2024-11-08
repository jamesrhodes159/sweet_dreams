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

class StaffController extends Controller
{
    public function index() {
        $staffs = User::where(function($query) {
                        $query->where('user_type', '=', 'admin')
                              ->orWhere('user_type', '=', 'staff');
                    })
                    ->where('id', '!=', Auth::user()->id)
                    ->get();

        // dd($staffs);  // Debugging output
        return view('pages.staffs.index', compact('staffs'));
    }

    public function create() {

        return view( 'pages.staffs.create' );
    }

    public function updateStatus($id){
        $user =  User::findOrFail($id);
        if($user->is_active == 1){
            $user->is_active = 0;
        }else{
            $user->is_active = 1;
        }
        $user->save();
        return redirect()->route( 'admin.staffs' )->with( 'flash_message', 'Staff status updated successfully.' );

    }

    public function store(Request $request)
    {
        // Validate the form inputs
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_type' => 'required|in:staff,admin',
            'password' => 'required|string|min:8',
            'is_active' => 'required|boolean',
        ]);

        // Prepare the data for storing
        $data = [
            'full_name' => $request->full_name,
            'email' => $request->email,
            'user_type' => $request->user_type,
            'password' => bcrypt($request->password), // Hash the password
            'is_active' => $request->is_active,
        ];

    // Handle profile image upload if exists
    if ($request->hasFile('profile_image')) {
        $imagePath = $request->file('profile_image')->store('staff_profiles', 'public');
        $data['profile_image'] = $imagePath;
    }

    // Create new staff member in the database
    $user = User::create($data);

    // Check if the user was successfully created
    if ($user) {
        return redirect()->route('admin.staffs')->with('flash_message', 'Staff member created successfully.');
    } else {
        return redirect()->back()->with('flash_error', 'Failed to create staff member.');
    }
}
}
