<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CwebAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('cweb.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'employee_number' => ['required', 'string'],
        ]);

        $user = User::where('employee_number', $data['employee_number'])->first();

        if (!$user) {
            return back()
                ->withErrors(['employee_number' => 'この社員番号は登録されていません。'])
                ->withInput();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('cweb.cases.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('cweb.login');
    }
}
