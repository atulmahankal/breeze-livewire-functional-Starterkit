<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;    // Auth::check();   // Authorized request if User authenticated
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    // check request method
    $isUpdateMethod = $this->isMethod('put') || $this->isMethod('patch');

    return [
      'name' => [
        'required',
        'string',
        'max:255',
      ],
      'email' => [
        'required',
        'string',
        'lowercase',
        'email',
        'max:255',
        $isUpdateMethod
          // Retrieve the ID from the route, in this case 'users/{user}' or Route::resource('users', ...
          ? Rule::unique('users', 'email')->ignore($this->route()->parameters()['user'])
          : Rule::unique('users', 'email'),

      ],
      'password' => [
        $isUpdateMethod
          ? 'sometimes'
          : 'required',
        $isUpdateMethod
          ? ''
          : 'confirmed',
        'string',
        Rules\Password::defaults()
      ],
    ];
  }
}
