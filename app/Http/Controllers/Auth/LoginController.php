<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
    * Where to redirect users after login.
    *
    * @var string
    */
    // protected $redirectTo = '/home';
    protected $redirectTo = '/admin';

    /**
    * Create a new controller instance.
    *
    * @return void
    */

    public function __construct() {
        $this->middleware( 'guest' )->except( 'logout' );
        $this->middleware( 'auth' )->only( 'logout' );
    }

    public function login( \Illuminate\Http\Request $request ) {
        // Validate the login request
        $this->validateLogin( $request );

        // Check if too many login attempts have been made
        if ( $this->hasTooManyLoginAttempts( $request ) ) {
            $this->fireLockoutEvent( $request );
            return $this->sendLockoutResponse( $request );
        }

        // Check if the user credentials are valid
        if ( $this->guard()->validate( $this->credentials( $request ) ) ) {
            $user = $this->guard()->getLastAttempted();

            // Ensure the user is active and has the 'admin' user type
            if ( $user->is_active && $user->user_type === 'admin' ) {
                if ( $this->attemptLogin( $request ) ) {
                    // Send the normal successful login response
                    return $this->sendLoginResponse( $request );
                }
            } else {
                // Increment failed login attempts and send appropriate error message
                $this->incrementLoginAttempts( $request );

                return redirect()
                ->back()
                ->withInput( $request->only( $this->username(), 'remember' ) )
                ->withErrors( [
                    'login' => !$user->is_active ? 'Your account is inactive.' : 'Only admin users can log in.'
                ] );
            }
        }

        // Increment login attempts and send failed login response if credentials are invalid
        $this->incrementLoginAttempts( $request );
        return $this->sendFailedLoginResponse( $request );
    }
}
