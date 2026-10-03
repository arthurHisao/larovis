<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|unique:users|max:255',
            'password'  => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        $token = $user->createToken('larovis_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'user'    => $user,
            'token'   => $token
        ], 201);
    }

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     if (! $user || ! Hash::check($request->password, $user->password)) {
    //         throw ValidationException::withMessages([
    //             'email' => ['As credenciais fornecidas estão incorretas.'],
    //         ]);
    //     }

    //     // Deleta tokens antigos do usuário para evitar múltiplos tokens ativos (opcional)
    //     $user->tokens()->delete();

    //     // Gera um novo Bearer Token
    //     $token = $user->createToken('larovis_auth_token')->plainTextToken;

    //     return response()->json([
    //         'user' => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //             'avatar' => $user->avatar_url,
    //         ],
    //         'token' => $token,
    //     ]);
    // }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Tenta autenticar na guarda de sessão do Laravel
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // 2. CRUCIAL: Regenera a sessão para gravar o cookie 'laravel_session' no navegador
        $request->session()->regenerate();

        $user = Auth::user();

        // 3. Retorna apenas os dados do usuário (não precisa devolver token na resposta)
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['message' => 'Sessão encerrada com sucesso.']);
    }
}