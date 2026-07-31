<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Mail\ForgotPassword;
use App\Enums\WalletPointsDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Events\SignUpBonusPointsEvent;
use App\Http\Traits\WalletPointsTrait;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Exceptions\ExceptionHandler;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Auth", description="Authentication & tokens")
 */
class AuthController extends Controller
{
    use WalletPointsTrait;

    /**
     * @OA\Post(
     *   path="/api/login",
     *   tags={"Auth"},
     *   summary="Login (consumer)",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"email","password"},
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function login(Request $request)
    {
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $verifyResult = $this->verifyLogin($request);

            // Check if verifyLogin returned an error response
            if (is_array($verifyResult) && isset($verifyResult['success']) && !$verifyResult['success']) {
                return response()->json($verifyResult, 400);
            }

            $user = $verifyResult;

            if (!Hash::check($request->password, $user->password) || !$user->hasRole(RoleEnum::CONSUMER)) {
                return response()->json([
                    'message' => 'The entered credentials are incorrect. Please try again!',
                    'success' => false
                ], 400);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->tokens()->update([
                'role_type' => $user->getRoleNames()->first()
            ]);

            return [
                'access_token' => $token,
                'permissions'  => $user->getAllPermissions(),
                'success' => true
            ];

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyVendor(User $vendor)
    {
        if (Helpers::isMultiVendorEnable()) {
            if ($vendor?->store?->first()?->is_approved) {
                return true;
            }

            throw new Exception('Please await store approval before logging in.', 403);
        }

        throw new Exception('The multi-vendor feature is currently deactivated.', 403);
    }

    /**
     * @OA\Post(
     *   path="/api/backend/login",
     *   tags={"Auth"},
     *   summary="Login (backend)",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"email","password"},
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function backendLogin(Request $request)
    {
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $verifyResult = $this->verifyLogin($request);

            // Check if verifyLogin returned an error response
            if (is_array($verifyResult) && isset($verifyResult['success']) && !$verifyResult['success']) {
                return response()->json($verifyResult, 400);
            }

            $user = $verifyResult;

            if (!Hash::check($request->password, $user->password) || $user->hasRole(RoleEnum::CONSUMER)) {
                return response()->json([
                    'message' => 'The entered backend credentials are incorrect. Please try again!',
                    'success' => false
                ], 400);
            }

            $expiresAt = now()->addHours(6); // Admin session/token valid for 6 hours
            $token = $user->createToken('admin_auth', ['*'], $expiresAt)->plainTextToken;
            if ($user->hasRole(RoleEnum::VENDOR)) {
                $this->verifyVendor($user);
            }

            $user->tokens()->update([
                'role_type' => $user->getRoleNames()->first()
            ]);

            return [
                'access_token' => $token,
                'permissions'  => $user->getAllPermissions(),
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyLogin(Request $request)
    {
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $validator = Validator::make($request->all(),[
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                // Return validation error as array instead of throwing exception
                return [
                    'message' => $validator->messages()->first(),
                    'success' => false
                ];
            }

            $rawEmail = (string) $request->email;
            $normalizedEmail = mb_strtolower(trim($rawEmail));
            // Remove any whitespace characters (including unicode/zero-width/nbsp) to avoid hidden-character mismatches
            $normalizedEmail = preg_replace('/\s+/u', '', $normalizedEmail);


            $user = User::whereEmail($normalizedEmail)->first();

            // Return user-friendly error instead of throwing exception (avoids logging)
            if (!$user) {
                return [
                    'message' => 'There is no account linked to the given email.',
                    'success' => false
                ];
            }

            // Return user-friendly error instead of throwing exception (avoids logging)
            if (!$user->status) {
                return [
                    'message' => 'You cannot log in with a disabled account.',
                    'success' => false
                ];
            }

            return $user;

        } catch (Exception $e) {
            // Only log actual system errors, not user errors
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/register",
     *   tags={"Auth"},
     *   summary="Register",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"name","email","password","password_confirmation","country_code","phone"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string"),
     *         @OA\Property(property="country_code", type="string"),
     *         @OA\Property(property="phone", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function register(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $validator = Validator::make($request->all(),[
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
                'country_code' => 'required',
                'phone' => 'required|min:9|unique:users,phone,NULL,id,deleted_at,NULL',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user = User::create([
                'name' => $request->name,
                'company_name' => $request->company_name ?? null,
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
                'country_code' => $request->country_code,
                'phone'  => (string) $request->phone,
            ]);

            $user->assignRole(RoleEnum::CONSUMER);
            if (Helpers::pointIsEnable()) {
                $settings = Helpers::getSettings();
                $signUpPoints = $settings['wallet_points']['signup_points'];
                $this->creditPoints($user->id, $signUpPoints, WalletPointsDetail::SIGN_UP_BONUS);
                event(new SignUpBonusPointsEvent($user));
                $user->point;
            }

            if (Helpers::walletIsEnable()) {
                $user->wallet()->create();
                $user->wallet;
            }

            DB::commit();
            return [
                'access_token' =>  $user->createToken('auth_token')->plainTextToken,
                'permissions'  =>  $user->getPermissionNames(),
                'success' => true
            ];

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/forgot-password",
     *   tags={"Auth"},
     *   summary="Forgot password",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"email"},
     *         @OA\Property(property="email", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function forgotPassword(Request $request)
    {
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $validator = Validator::make($request->all(),[
                'email' => 'required|email|exists:users',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $token = rand(11111, 99999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );

            Mail::to($request->email)->send(new ForgotPassword($token));
            Password::sendResetLink($request->only('email'));

            return [
                'message' => "We have e-mailed verification code in registered mail!",
                'success' => true
            ];

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/verify-token",
     *   tags={"Auth"},
     *   summary="Verify token",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"token","email"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="email", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function verifyToken(Request $request)
    {
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $validator = Validator::make($request->all(),[
                'token' => 'required',
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user =  DB::table('password_reset_tokens')
                    ->where('token',$request->token)
                    ->where('email',$request->email)
                    ->where('created_at','>',Carbon::now()->subHours(1))
                    ->first();

            if (!$user) {
                throw new Exception('The provided email or token is not recognized.', 400);
            }

            return [
                'message' => "Verification token has been successfully verified.",
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/update-password",
     *   tags={"Auth"},
     *   summary="Update password (token flow)",
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"token","email","password","password_confirmation"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function updatePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->has('email')) {
                $request->merge(['email' => strtolower(trim($request->email))]);
            }

            $validator = Validator::make($request->all(),[
                'token' => 'required',
                'email' => 'required|email|max:255|exists:users',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required'
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user =  DB::table('password_reset_tokens')
                ->where('token',$request->token)
                ->where('email',$request->email)
                ->where('created_at','>',Carbon::now()->subHours(1))
                ->first();

            if (!$user) {
                throw new Exception('The provided email or token is not recognized.', 400);
            }

            User::whereEmail($request->email)
                ->update(['password' => Hash::make($request->password)]);

            DB::table('password_reset_tokens')->where('email',$request->email)->delete();
            DB::commit();

            return [
                'message' => "Your password has been successfully changed!",
                'success' => true
            ];

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/logout",
     *   tags={"Auth"},
     *   summary="Logout (consumer)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function logout(Request $request)
    {
        try {

            $token = PersonalAccessToken::findToken($request->bearerToken());
            if(!$token) {
                throw new Exception('The provided access token is not valid. Please try again.', 400);
            }

            $token->delete();
            return [
                'message' => "You are all logged out! We hope to see you soon again.",
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * GET /api/email/verify/{id}/{hash}
     * Called by the React frontend after the user clicks the verification link in their email.
     * Validates the signed URL, marks the email as verified, and returns JSON.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        // Validate the signed URL — this prevents forgery
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.', 'success' => false], 403);
        }

        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Verification link has expired. Please request a new one.', 'success' => false], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.', 'success' => true, 'already_verified' => true]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully! You can now place bids.',
            'success' => true,
        ]);
    }

    /**
     * POST /api/email/resend-verification
     * Called by BidForm when user hasn't verified their email before bidding.
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address is already verified.',
                'success' => false,
            ], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent. Please check your inbox.',
            'success' => true,
        ]);
    }
}
