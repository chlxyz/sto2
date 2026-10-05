<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // The {id} in a profile/delete URL is always the caller's own id. Resolving
    // ownership here means a mismatch 404s instead of touching another row.
    private function selfOrFail($id): User
    {
        $user = Auth::user();

        abort_unless($user && (int) $user->id === (int) $id, 404);

        return $user;
    }

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
            if(Auth::user()->isAdmin()){
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $data = $request->only('name', 'email');
        $data['password'] = $request->password;
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
        $user = $this->selfOrFail($id);
        $user->delete();

        Auth::logout();

        return redirect()->route('loginForm')->with('success', 'Account deleted');
    }

    public function showProfile($id){
        $user = $this->selfOrFail($id);

        return view ('auth.profile', compact('user'));
    }

    public function editProfileHandler(Request $request, $id) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$id,
            'password' => 'required|string|min:8',
        ]);

        $user = $this->selfOrFail($id);

        $data = $request->only('name', 'email');

        // User has a 'hashed' cast on password, so don't Hash::make it again here.
        $data['password'] = $request->password;

        $user->update($data);

        if($user){
            return redirect()->route('editProfileForm', ['id'=>$id])->with('success', 'Successfully updated');
        }else {
            return redirect()->route('editProfileForm', ['id'=>$id])->with('error', 'Failed to update');
        }
    }
    
}
