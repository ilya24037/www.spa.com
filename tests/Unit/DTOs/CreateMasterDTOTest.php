<?php

namespace Tests\Unit\DTOs;

use Tests\TestCase;
use App\Domain\Master\DTOs\CreateMasterDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateMasterDTOTest extends TestCase
{
    /** @test */
    public function it_can_create_dto_from_array()
    {
        $data = [
            'user_id' => 1,
            'display_name' => 'Test Master',
            'bio' => 'Test bio with more than 50 characters to meet minimum requirements',
            'phone' => '+79123456789',
            'whatsapp' => '+79123456789',
            'telegram' => '@testmaster',
            'experience_years' => 5,
            'city' => 'Moscow',
            'district' => 'Center',
            'metro_station' => 'Red Square',
            'home_service' => true,
            'salon_service' => false,
            'age' => 25,
            'features' => ['feature1', 'feature2'],
            'services' => [1, 2, 3],
        ];

        $dto = CreateMasterDTO::fromArray($data);

        $this->assertEquals(1, $dto->user_id);
        $this->assertEquals('Test Master', $dto->display_name);
        $this->assertEquals('Moscow', $dto->city);
        $this->assertTrue($dto->home_service);
        $this->assertFalse($dto->salon_service);
        $this->assertEquals(25, $dto->age);
        $this->assertIsArray($dto->features);
        $this->assertCount(2, $dto->features);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: '',
            bio: '',
            phone: '',
            city: ''
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('display_name', $errors);
        $this->assertArrayHasKey('bio', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('city', $errors);
    }

    /** @test */
    public function it_validates_display_name_length()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'A',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow'
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('display_name', $errors);
        $this->assertStringContainsString('минимум 2 символа', $errors['display_name']);
    }

    /** @test */
    public function it_validates_bio_length()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Too short',
            phone: '+79123456789',
            city: 'Moscow'
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('bio', $errors);
        $this->assertStringContainsString('минимум 50 символов', $errors['bio']);
    }

    /** @test */
    public function it_validates_phone_format()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: 'invalid',
            city: 'Moscow'
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('phone', $errors);
        $this->assertStringContainsString('Некорректный формат', $errors['phone']);
    }

    /** @test */
    public function it_validates_service_type_selection()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: false,
            salon_service: false
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('service_type', $errors);
        $this->assertStringContainsString('хотя бы один тип услуг', $errors['service_type']);
    }

    /** @test */
    public function it_validates_salon_address_when_salon_service()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            salon_service: true,
            salon_address: null
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('salon_address', $errors);
        $this->assertStringContainsString('необходимо указать адрес', $errors['salon_address']);
    }

    /** @test */
    public function it_validates_age_range()
    {
        $tooYoung = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            age: 17
        );

        $errors = $tooYoung->validate();
        $this->assertArrayHasKey('age', $errors);

        $tooOld = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            age: 81
        );

        $errors = $tooOld->validate();
        $this->assertArrayHasKey('age', $errors);
    }

    /** @test */
    public function it_validates_experience_years()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            experience_years: -1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('experience_years', $errors);
        $this->assertStringContainsString('не может быть отрицательным', $errors['experience_years']);
    }

    /** @test */
    public function it_validates_avatar_file()
    {
        Storage::fake('public');

        // Invalid file type
        $invalidFile = UploadedFile::fake()->create('avatar.pdf', 100);
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            avatar: $invalidFile
        );

        $errors = $dto->validate();
        $this->assertArrayHasKey('avatar', $errors);

        // File too large
        $largeFile = UploadedFile::fake()->image('avatar.jpg')->size(6000); // 6MB
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            avatar: $largeFile
        );

        $errors = $dto->validate();
        $this->assertArrayHasKey('avatar', $errors);
        $this->assertStringContainsString('5MB', $errors['avatar']);
    }

    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        Storage::fake('public');
        
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Valid Name',
            bio: 'Valid bio with more than 50 characters to meet all requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            salon_service: true,
            salon_address: 'Test address',
            age: 25,
            experience_years: 3,
            avatar: UploadedFile::fake()->image('avatar.jpg')->size(1000)
        );

        $this->assertTrue($dto->isValid());
        $this->assertEmpty($dto->validate());
    }

    /** @test */
    public function it_converts_to_array()
    {
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Test Master',
            bio: 'Test bio',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals('Test Master', $array['display_name']);
        $this->assertEquals('Moscow', $array['city']);
        $this->assertTrue($array['home_service']);
        $this->assertNull($array['whatsapp']);
    }
}