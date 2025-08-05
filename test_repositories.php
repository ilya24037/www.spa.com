<?php
require_once 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    \ = app(App\Domain\User\Repositories\UserRepository::class);
    echo 'UserRepository создался успешно' . PHP_EOL;
    
    \ = app(App\Domain\User\Services\UserService::class);
    echo 'UserService создался успешно' . PHP_EOL;
    
    echo 'BaseRepository и BaseService работают\!' . PHP_EOL;
} catch (Exception \) {
    echo 'ОШИБКА: ' . \->getMessage() . PHP_EOL;
}
