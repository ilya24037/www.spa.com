<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all(['id', 'name', 'email']);
        
        if ($users->isEmpty()) {
            $this->info('No users found in database.');
            return;
        }
        
        $this->info('Users in database:');
        $this->table(
            ['ID', 'Name', 'Email'],
            $users->map(function ($user) {
                return [$user->id, $user->name, $user->email];
            })->toArray()
        );
    }
}
