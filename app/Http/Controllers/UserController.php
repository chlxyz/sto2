<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function loginUi(){
        return view ('auth.login');
    }

    public function registerUi(){
        return view ('auth.register');
    }

    public function editProfileUi(){
        return view ('auth.editProfileForm');
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('loginForm')->with('error', 'Account not registered');
        }

        $credentials = $request->only('email', 'password');
        if(Auth::attempt($credentials)){
            if(Auth::user()->email=="admin@gmail.com"){
                return redirect()->route('admin.dashboard')->with('success', 'Login Success');
            } else {
                return redirect()->route('dashboard')->with('success', 'Login Success');
            }
        }else {
            return redirect()->route('loginForm')->with('error', 'Login Error');
        }
    }

    public function register(Request $request){
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $userExists = User::where('email', $request->email)->exists();

        if($userExists){
            return redirect()->route('registerForm')->with('error', 'Account is already registered');
        }

        $data = $request->only('name', 'email');
        $data['password'] = Hash::make($request->password);
        $user = User::create($data);

        if($user){
            return redirect()->route('loginForm')->with('success', 'Successfully registered');
        }else {
            return redirect()->route('registerForm')->with('error', 'Failed to register');
        }
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('loginForm')->with('success', 'Logged out successfully');
    }

    public function deleteAccount($id){
        $user = User::find($id);
        $user->delete();

        return redirect()->route('loginForm')->with('success', 'Account deleted');
    }

    public function showProfile($id){
        $user = User::findOrFail($id);

        return view ('auth.profile', compact('user'));
    }

    public function editProfileHandler(Request $request, $id) {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
    
        $user = User::findOrFail($id);
    
        $data = $request->only('name', 'email');
    
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }
    
        $user->update($data);

        if($user){
            return redirect()->route('editProfileForm', ['id'=>$id])->with('success', 'Successfully updated');
        }else {
            return redirect()->route('editProfileForm', ['id'=>$id])->with('error', 'Failed to update');
        }
    }
    
}
