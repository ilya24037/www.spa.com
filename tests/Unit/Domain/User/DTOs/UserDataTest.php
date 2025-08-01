<?php

namespace Tests\Unit\Domain\User\DTOs;

use Tests\TestCase;
use App\Domain\User\DTOs\UserData;
use App\Domain\User\DTOs\UserProfileData;
use App\Domain\User\DTOs\UserSettingsData;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class UserDataTest extends TestCase
{
    public function test_can_create_from_array()
    {
        $data = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'role' => UserRole::CLIENT->value,
            'status' => UserStatus::ACTIVE->value,
            'name' => 'Test User',
            'phone' => '+7 999 999 99 99',
        ];

        $userDto = UserData::fromArray($data);

        $this->assertEquals(1, $userDto->id);
        $this->assertEquals('test@example.com', $userDto->email);
        $this->assertEquals('hashed_password', $userDto->password);
        $this->assertEquals(UserRole::CLIENT, $userDto->role);
        $this->assertEquals(UserStatus::ACTIVE, $userDto->status);
        $this->assertEquals('Test User', $userDto->name);
        $this->assertEquals('+7 999 999 99 99', $userDto->phone);
    }

    public function test_can_convert_to_array()
    {
        $userDto = new UserData(
            id: 1,
            email: 'test@example.com',
            password: 'hashed_password',
            role: UserRole::MASTER,
            status: UserStatus::PENDING,
            name: 'Test Master',
            phone: null,
            avatarUrl: null,
            emailVerifiedAt: null,
            profile: null,
            settings: null,
            metadata: []
        );

        $array = $userDto->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('hashed_password', $array['password']);
        $this->assertEquals(UserRole::MASTER->value, $array['role']);
        $this->assertEquals(UserStatus::PENDING->value, $array['status']);
        $this->assertEquals('Test Master', $array['name']);
        $this->assertArrayNotHasKey('phone', $array); // null values are filtered
    }

    public function test_can_handle_nested_dtos()
    {
        $data = [
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT->value,
            'status' => UserStatus::ACTIVE->value,
            'profile' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'city' => 'Moscow',
            ],
            'settings' => [
                'email_notifications' => true,
                'sms_notifications' => false,
                'theme' => 'dark',
            ],
        ];

        $userDto = UserData::fromArray($data);

        $this->assertInstanceOf(UserProfileData::class, $userDto->profile);
        $this->assertEquals('John', $userDto->profile->firstName);
        $this->assertEquals('Doe', $userDto->profile->lastName);
        $this->assertEquals('Moscow', $userDto->profile->city);

        $this->assertInstanceOf(UserSettingsData::class, $userDto->settings);
        $this->assertTrue($userDto->settings->emailNotifications);
        $this->assertFalse($userDto->settings->smsNotifications);
        $this->assertEquals('dark', $userDto->settings->theme);
    }

    public function test_uses_default_values()
    {
        $data = [
            'email' => 'test@example.com',
        ];

        $userDto = UserData::fromArray($data);

        $this->assertNull($userDto->id);
        $this->assertEquals(UserRole::CLIENT, $userDto->role); // default
        $this->assertEquals(UserStatus::PENDING, $userDto->status); // default
        $this->assertNull($userDto->name);
        $this->assertEquals([], $userDto->metadata); // default empty array
    }
}