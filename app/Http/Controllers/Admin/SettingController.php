<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller {
    public function index() {
        $settings = Setting::first();
        // dd( $settings );
        return view( 'pages.settings.index', compact( 'settings' ) );
    }

    public function store( Request $request ) {
        // Validate the request data
        $request->validate( [
            'points_per_comment' => 'required|integer',
            'points_per_review' => 'required|integer',
            'points_per_post' => 'required|integer',
            'points_per_chat_message' => 'required|integer',
        ] );

        // Retrieve the first setting or create a new one if it doesn't exist
    $settings = Setting::first();

    if (!$settings) {
        // If no settings record exists, create a new instance
        $settings = new Setting();
    }

    // Update the settings with the provided values
    $settings->points_per_comment = $request->input('points_per_comment');
    $settings->points_per_review = $request->input('points_per_review');
    $settings->points_per_post = $request->input('points_per_post');
    $settings->points_per_chat_message = $request->input('points_per_chat_message');

    // Save the settings
    $settings->save();

    // Redirect back with success message
    return redirect()->back()->with('flash_message', 'Settings updated successfully.' );
    }
}
