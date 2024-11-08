<?php

namespace App\Http\Traits;

use Carbon\Carbon;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Auth;

/*
|--------------------------------------------------------------------------
| Api Responser Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponser
{
	/**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     * @param  string  $message
     * @param  int|null  $code
     * @return \Illuminate\Http\JsonResponse
     */
	protected function success(string $message = null, $data = null, int $code = 200)
	{
		$user = Auth::user();
		if($user->language == 'Spanish')
        {
            $message = $this->changeLanguage($message);
        }

		if(!empty($data)){
			return response()->json([
				'status' => 1,
				'message' => $message,
				'data' => $data
			], $code);
		}
		else{
			return response()->json([
				'status' => 1,
				'message' => $message
			], $code);
		}
	}

	/**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|string|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
	protected function error(string $message = null, int $code, $data = null)
	{
		// $user = Auth::user();
		// if($user->language == 'Spanish')
  //       {
  //           $message = $this->changeLanguage($message);
  //       }
		return response()->json([
			'status' => 0,
			'message' => $message
		], $code);
	}

    protected function successDataResponse($message, $data, $code = 200)
    {
        return response()->json([
            'status'  => 1,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    protected function successResponse($message = null, $code = 200)
    {
        return response()->json([
            'status'  => 1,
            'message' => $message
        ], $code);
    }


    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'  => 0,
            'message' => $message
        ], $code);
    }

	private function changeLanguage($message)
    {

        $translator = new GoogleTranslate();

            $translator->setSource('auto');

            $translator->setTarget('es');

            $first_message = $translator->translate($message);

            return $first_message;

    }
}
