<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create the first administrator account';

    public function handle(): int
    {
        if (User::query()->where('role', 'administrator')->exists()) {
            $this->error('An administrator account already exists.');

            return self::FAILURE;
        }

        $name = $this->ask('Administrator name');
        $email = $this->ask('Administrator email');
        $password = $this->secret('Password (minimum 12 characters)');
        $passwordConfirmation = $this->secret('Confirm password');

        if (! is_string($password) || ! is_string($passwordConfirmation) || ! hash_equals($password, $passwordConfirmation)) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'administrator',
        ]);

        $this->info("Administrator account created for {$email}.");

        return self::SUCCESS;
    }
}
