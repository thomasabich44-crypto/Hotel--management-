<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateHotelUser extends Command
{
    protected $signature = 'hotel:user';

    protected $description = 'Create an administrator or receptionist account';

    public function handle(): int
    {
        $name = $this->ask('Staff member name');
        $email = $this->ask('Staff member email');
        $role = $this->choice('Staff role', ['administrator', 'receptionist']);
        $password = $this->secret('Password (minimum 12 characters)');

        $validator = Validator::make(
            compact('name', 'email', 'role', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'role' => ['required', 'in:administrator,receptionist'],
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
            'role' => $role,
            'password' => Hash::make($password),
        ]);

        $this->info("Created {$role} account for {$email}.");

        return self::SUCCESS;
    }
}
