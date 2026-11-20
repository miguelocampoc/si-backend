<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ResetUserPassword;
use App\Actions\Auth\SendPasswordResetLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request, SendPasswordResetLink $sendPasswordResetLink): JsonResponse
    {
        $sendPasswordResetLink->handle($request->validated('email'));

        return response()->json([
            'message' => trans('passwords.sent'),
        ]);
    }

    public function reset(ResetPasswordRequest $request, ResetUserPassword $resetUserPassword): JsonResponse
    {
        $resetUserPassword->handle($request->validated());

        return response()->json([
            'message' => trans('passwords.reset'),
        ]);
    }
}
