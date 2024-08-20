<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class AuthController extends Controller
{
    public function logout(Request $request)
    {
         // Affichez le token dans les logs de Laravel
    Log::info('CSRF Token from Server logout: ' . $csrfTokenServer);
        $user = Auth::user();
       

        if ($user) {
            // Révoquer tous les jetons d'authentification de l'utilisateur
            $user->tokens()->delete();
            return response()->json(['message' => 'Déconnexion réussie.']);
        }

        return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
    }
}
