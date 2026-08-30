<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function show() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $user = User::where('username', $data['username'])
            ->where('password', $data['password'])
            ->first();

        if (!$user) return back()->withErrors(['username'=>'Invalid username or password.'])->withInput();

        $request->session()->regenerate();
        session(['user_id'=>$user->id,'user_name'=>$user->name ?: $user->username]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
