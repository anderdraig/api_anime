<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;



class GoogleController extends Controller
{
    //redirigir al usuario a google
    public function redirectToGoogle(){
        return Socialite::driver('google')->redirect();
    }

    //manejar el callback de google

    public function handleGoogleCallback(){

        try{
            $googleUser = Socialite::driver('google')->stateless()->user();

            $avartarURL = null;
            if($googleUser->getAvatar()){
                $avatar = Cloudinary::upload($googleUser->getAvatar())->getSecurePath();
                $avartarURL = $avatar;
            }

            //crear o actualizar el usuario en la base de datos 
            $user= User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id'=> $googleUser->getId(),
                    'avatar' => $avartarURL,
                    'password' => null, 
                ]
            );

            // Verificar si el usuario tiene un google_id (significa que está registrado con Google)
            if (!$user->google_id) {
                return response()->json(['error' => 'Este usuario no está registrado con Google'], 403);
            }

            //generar un tokent JWT para la sesion

            $token= JWTAuth::fromUser($user);
            return response()->json([
                'token' => $token,
                'user' => $user,
                'status' => 200
            ]);

        }catch(\Exception $e){
            return response()->json([
                'error' => 'Error al auntenticar con Google',
                'status' => 500
            ]);
        }
    }


}
