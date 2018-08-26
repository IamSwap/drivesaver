<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/drive'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('google')->user();

        Auth::login($this->findOrCreate($user));

        return redirect('/dashboard');
    }

    /**
     * Find or create a new user.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function findOrCreate($user)
    {
        $dbUser = User::where('email', $user->email)->first();

        if ($dbUser) {
            $dbUser->update([
                'token' => $user->token,
                'refresh_token' => $user->refreshToken,
                'token_expires_in' => $user->expiresIn,
                'token_created' => time(),
            ]);

            return $dbUser;
        }

        return User::create([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'token_expires_in' => $user->expiresIn,
            'token_created' => time(),
            'avatar' => $user->getAvatar(),
        ]);
    }
}
