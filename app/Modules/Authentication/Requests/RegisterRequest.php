<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            "name" => "required|string",
            "email" => "required|string|email|unique:users,email",
            "password" => "required|string|confirmed",
      
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => __('messages.validation.name_required'),
            'email.required' => __('messages.validation.email_required'),
            'email.email' => __('messages.validation.email_email'),
            'email.unique' => __('messages.validation.email_unique'),
            'password.required' => __('messages.validation.password_required'),
            'password.confirmed' => __('messages.validation.password_confirmed'),
        ];
    }

   
}
