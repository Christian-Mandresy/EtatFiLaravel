<?php

namespace App\Http\Controllers\API;

use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,saisie', // Ajoutez la validation du champ de sélection du rôle
            ]);

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $request->input('role'), // Enregistrez le rôle dans la base de données
            ]);

            $token = $user->createToken($user->email.'_token')->plainTextToken;

            return response()->json(['message' => 'User registered successfully', 'user' => $user, 'token' => $token]);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422); // Réponse avec le code HTTP 422 Unprocessable Entity
        }
    }

    public function login(Request $request)
    {
        $csrfTokenServer = csrf_token();

    // Affichez le token dans les logs de Laravel
    Log::info('CSRF Token from Server: ' . $csrfTokenServer);
        $credentials = $request->only('email', 'password', 'role'); // Incluez le champ 'role' dans les informations de connexion
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Vérifiez si le rôle choisi correspond au rôle de l'utilisateur
            if ($user->role === $credentials['role']) {
                // Ajoutez les informations sur le rôle à la charge utile du token
                $token = $user->createToken('auth_token', [$user->role])->plainTextToken;

                return response()->json(['token' => $token, 'role' => $user->role], 200);
            } else {
                // Si le rôle choisi ne correspond pas au rôle de l'utilisateur, retournez une réponse non autorisée
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $csrfTokenServer = csrf_token();
        // Affichez le token dans les logs de Laravel
        Log::info('CSRF Token from Server logout: ' . $csrfTokenServer);
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out'], 200);
    }

}
