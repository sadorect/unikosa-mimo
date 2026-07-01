<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        $validator = \Illuminate\Support\Facades\Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'graduating_set_id' => ['nullable', 'exists:sets,id'],
        ]);

        if ($validator->fails()) {
            throw \Illuminate\Validation\ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'phone' => $input['phone'] ?? null,
            'graduating_set_id' => $input['graduating_set_id'] ?? null,
            'status' => 'pending',
        ]);

        $user->assignRole('member');

        return $user;
    }
}
