<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sticker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class StickerController extends Controller {
    public function index() {
        $stickers = Sticker::all();
        // dd( $stickers );
        return view( 'pages.stickers.index', compact( 'stickers' ) );
    }

    public function create() {
        return view( 'pages.stickers.create' );
    }

    // public function store( Request $request ) {

    //     if ( $request->hasFile( 'profile_image' ) ) {
    //         $profile_image = $request->profile_image->store( 'public/profile_image' );
    //         $path = Storage::url( $profile_image );
    //         $user->profile_image = $path;
    //         // $completeProfile[ 'profile_image' ] = $path;
    //     }
    // }

    public function store( Request $request ) {
        // Validate the form inputs
        $request->validate( [
            'sticker_name' => 'required|string|max:255',
            'sticker_image' => 'nullable|image|mimes:png|max:2048',
            'sticker_type' => 'required|string|in:free,premium,point_redeemed',
            'price' => 'nullable|numeric',
            'status' => 'required|boolean',
        ] );

        // Initialize sticker data
        $data = [
            'sticker_name' => $request->sticker_name,
            'sticker_type' => $request->sticker_type,
            'price' => $request->sticker_type == 'free' ? 0 : $request->price, // Set price to 0 if sticker type is free
            'is_active' => $request->status,
        ];

        // Handle file upload if a new image is uploaded
        if ( $request->hasFile( 'sticker_image' ) ) {
            // Store the image and get the file path
            // $profile_image = $request->sticker_image->store( 'public/profile_image' );
            $imagePath = $request->file( 'sticker_image' )->store( 'stickers', 'public' );
            $data[ 'sticker_image' ] = $imagePath;
        }

        // dd( $data );
        // // Store sticker data in the database ( create or update )
        // if ( isset( $settings ) ) {
        //     // If sticker already exists, update it
        //     $settings->update( $data );
        // } else {
        // Create new sticker
        Sticker::create( $data );
        // }

        // Redirect back with a success message
        return redirect()->route( 'admin.stickers' )->with( 'flash_message', 'Sticker saved successfully.' );
    }

    public function updateStatus($id){
        $sticker =  Sticker::findOrFail($id);
        if($sticker->is_active == 1){
            $sticker->is_active = 0;
        }else{
            $sticker->is_active = 1;
        }
        $sticker->save();
        return redirect()->route( 'admin.stickers' )->with( 'flash_message', 'Sticker status updated successfully.' );

    }
}
