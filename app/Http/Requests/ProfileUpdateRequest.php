<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $nombreChanged = $this->input('nombre') !== $user->nombre;
        $apellidoChanged = $this->input('apellido') !== $user->apellido;

        $passwordRules = ['nullable'];
        if ($nombreChanged || $apellidoChanged) {
            $passwordRules = ['required', 'current_password'];
        }

        return [
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique(User::class, 'email')->ignore($user->idUsuario, 'idUsuario'),
            ],
            'password' => $passwordRules,
        ];
    }
}
