<?php

namespace App\Console\Commands;

use App\Services\InitialAdministratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'app:create-administrator
                            {--name= : Administrator full name}
                            {--email= : Administrator email address}';

    protected $description = 'Securely create an administrator account';

    public function handle(InitialAdministratorService $administrators): int
    {
        if ($administrators->hasAdministrator()) {
            $this->error('An administrator already exists. Initial administrator setup is closed.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->secret('Password (12+ characters with mixed case, number, and symbol)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'same:confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $administrators->create([
            'name' => $name,
            'email' => mb_strtolower($email),
            'password' => $password,
        ]);

        $this->info('Administrator account created successfully.');

        return self::SUCCESS;
    }
}
