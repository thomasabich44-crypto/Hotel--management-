<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_administrator_with_a_hashed_password(): void
    {
        $password = $this->validPassword();
        $email = 'admin@example.test';

        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'Hotel Administrator')
            ->expectsQuestion('Administrator email', $email)
            ->expectsQuestion('Password (minimum 12 characters)', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutputToContain("Administrator account created for {$email}.")
            ->assertExitCode(0);

        $user = User::where('email', $email)->firstOrFail();

        $this->assertSame('administrator', $user->role);
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_it_rejects_a_duplicate_email_without_creating_an_account(): void
    {
        $email = 'existing@example.test';
        User::factory()->create(['email' => $email]);

        $password = $this->validPassword();

        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'Hotel Administrator')
            ->expectsQuestion('Administrator email', $email)
            ->expectsQuestion('Password (minimum 12 characters)', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutputToContain('The email has already been taken.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_password_confirmation_mismatch(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'Hotel Administrator')
            ->expectsQuestion('Administrator email', 'admin@example.test')
            ->expectsQuestion('Password (minimum 12 characters)', $this->validPassword())
            ->expectsQuestion('Confirm password', $this->validPassword())
            ->expectsOutputToContain('Passwords do not match.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_refuses_to_run_after_an_administrator_exists(): void
    {
        User::factory()->create(['role' => 'administrator']);

        $this->artisan('admin:create')
            ->expectsOutputToContain('An administrator account already exists.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    private function validPassword(): string
    {
        return Str::upper(Str::random(8)).Str::lower(Str::random(8)).random_int(1000, 9999);
    }
}
