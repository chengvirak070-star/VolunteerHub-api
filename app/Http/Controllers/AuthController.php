<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationStatus;
use App\Enums\Role;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            // Organizers get an organization that waits for admin approval.
            if ($user->role === Role::Organizer) {
                $user->organization()->create([
                    'name' => $data['organization_name'],
                    'status' => OrganizationStatus::Pending,
                ]);
            }

            return $user;
        });

        return $this->tokenResponse($user, $request->userAgent(), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', mb_strtolower($request->string('email')))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ]);
        }

        if ($user->isBlocked()) {
            return response()->json(['message' => 'Your account has been blocked.'], 403);
        }

        return $this->tokenResponse($user, $request->input('device_name') ?? $request->userAgent());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }

    public function me(Request $request): UserResource
    {
        $user = User::withProfileStats()->findOrFail($request->user()->id);

        return new UserResource($user);
    }

    private function tokenResponse(User $user, ?string $device, int $status = 200): JsonResponse
    {
        $token = $user->createToken(mb_substr($device ?: 'web', 0, 100))->plainTextToken;
        $user = User::withProfileStats()->find($user->id);
        Auth::setUser($user); // so UserResource treats this user as "me" (shows email, is_me)

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ], $status);
    }
}
