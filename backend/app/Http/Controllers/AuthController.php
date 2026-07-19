<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
  public function login(Request $request)
  {
    $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
    $user = User::where('email', $data['email'])->first();
    if (!$user || !Hash::check($data['password'], $user->password))
      throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
    abort_unless($user->is_active, 403, 'Account is inactive.');
    $user->tokens()->delete();
    return response()->json(['token' => $user->createToken('react-web')->plainTextToken, 'user' => $user]);
  }
  public function me(Request $request)
  {
    return $request->user();
  }
  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()?->delete();
    return response()->json(['message' => 'Logged out']);
  }
}
