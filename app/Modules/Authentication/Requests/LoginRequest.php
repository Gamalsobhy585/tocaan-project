<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        "email" =>
         [
            "required",
            "email",
            function ($attribute, $value, $fail) {
                $exists = DB::table('users')->where('email', $value)->exists()
                        || DB::table('dashboard_users')->where('email', $value)->exists();

                if (!$exists) {
                    $fail(__('messages.login.email_not_found'));
                }
            },
        ],
                    "password" => "required|string",
        ];
    }
}
