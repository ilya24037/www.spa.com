<?php

require_once "vendor/autoload.php";

$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo " ������ �������� ���� ������ �� KRAKOZYABRY...\n\n";

// 1. ��������� ���� ��������
echo "1?? �������� ���� ��������:\n";
$masters = \App\Domain\Master\Models\MasterProfile::all();
foreach ($masters as $master) {
    echo "������ {$master->id}: {$master->display_name}\n";
}
echo "\n";

// 2. ��������� ����������
echo "2?? �������� ����������:\n";
$ads = \App\Domain\Ad\Models\Ad::all();
foreach ($ads as $ad) {
    echo "���������� {$ad->id}: {$ad->title}\n";
}
echo "\n";

// 3. ��������� �������������
echo "3?? �������� �������������:\n";
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "������������ {$user->id}: {$user->name} ({$user->email})\n";
}
echo "\n";

echo "? �������� ���������!\n";
