<?php

namespace Tests\Feature\Review;

use Tests\TestCase;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Review\Models\Review;
use Tests\Traits\SafeRefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ReviewApiTest extends TestCase
{
    use SafeRefreshDatabase;

    private User $authUser;
    private User $targetUser;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем пользователей для тестов
        $this->authUser = User::factory()->create([
            'email' => 'auth@test.com',
            'name' => 'Auth User'
        ]);

        $this->targetUser = User::factory()->create([
            'email' => 'target@test.com',
            'name' => 'Target User'
        ]);

        // Создаем объявление
        $this->ad = Ad::factory()->create([
            'user_id' => $this->targetUser->id,
            'title' => 'Test Ad',
            'status' => 'active'
        ]);

        // Авторизуем пользователя
        Sanctum::actingAs($this->authUser);
    }

    /** @test */
    public function it_can_get_list_of_reviews()
    {
        // Создаем несколько отзывов
        Review::factory()->count(5)->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'approved'
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'reviewable_user_id',
                        'ad_id',
                        'rating',
                        'comment',
                        'is_anonymous',
                        'status',
                        'created_at',
                        'user'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_can_filter_reviews_by_status()
    {
        Review::factory()->count(3)->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'approved'
        ]);

        Review::factory()->count(2)->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id . '&status=approved');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_create_review()
    {
        $data = [
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 5,
            'comment' => 'Отличный сервис!',
            'is_anonymous' => false
        ];

        $response = $this->postJson('/api/reviews', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'reviewable_user_id',
                    'ad_id',
                    'rating',
                    'comment',
                    'is_anonymous',
                    'status'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $this->authUser->id,
                    'reviewable_user_id' => $this->targetUser->id,
                    'rating' => 5,
                    'comment' => 'Отличный сервис!',
                    'status' => 'pending'
                ]
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'rating' => 5
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->postJson('/api/reviews', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reviewable_user_id', 'ad_id', 'rating']);
    }

    /** @test */
    public function it_validates_rating_range()
    {
        $data = [
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 10, // Неверный рейтинг
            'comment' => 'Тест'
        ];

        $response = $this->postJson('/api/reviews', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function it_prevents_self_review()
    {
        $ownAd = Ad::factory()->create([
            'user_id' => $this->authUser->id
        ]);

        $data = [
            'reviewable_user_id' => $this->authUser->id,
            'ad_id' => $ownAd->id,
            'rating' => 5,
            'comment' => 'Самооценка'
        ];

        $response = $this->postJson('/api/reviews', $data);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Вы не можете оставить отзыв на собственное объявление'
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_review()
    {
        // Создаем первый отзыв
        Review::factory()->create([
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id
        ]);

        // Пытаемся создать дубликат
        $data = [
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 4,
            'comment' => 'Повторный отзыв'
        ];

        $response = $this->postJson('/api/reviews', $data);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Вы уже оставляли отзыв на это объявление'
            ]);
    }

    /** @test */
    public function it_can_update_own_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 3,
            'comment' => 'Нормально'
        ]);

        $data = [
            'rating' => 5,
            'comment' => 'Изменил мнение - отлично!',
            'is_anonymous' => true
        ];

        $response = $this->putJson('/api/reviews/' . $review->id, $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $review->id,
                    'rating' => 5,
                    'comment' => 'Изменил мнение - отлично!',
                    'is_anonymous' => true
                ]
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Изменил мнение - отлично!'
        ]);
    }

    /** @test */
    public function it_cannot_update_others_review()
    {
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $otherUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id
        ]);

        $data = [
            'rating' => 1,
            'comment' => 'Попытка изменить чужой отзыв'
        ];

        $response = $this->putJson('/api/reviews/' . $review->id, $data);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_own_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id
        ]);

        $response = $this->deleteJson('/api/reviews/' . $review->id);

        $response->assertStatus(204);

        $this->assertSoftDeleted('reviews', [
            'id' => $review->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_others_review()
    {
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $otherUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id
        ]);

        $response = $this->deleteJson('/api/reviews/' . $review->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_to_create_review()
    {
        // Разлогиниваемся
        auth()->logout();

        $data = [
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 5,
            'comment' => 'Тест без авторизации'
        ];

        $response = $this->postJson('/api/reviews', $data);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_paginates_reviews()
    {
        Review::factory()->count(25)->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'approved'
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id . '&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);

        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function it_sorts_reviews_by_date()
    {
        $oldReview = Review::factory()->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'approved',
            'created_at' => now()->subDays(5)
        ]);

        $newReview = Review::factory()->create([
            'reviewable_user_id' => $this->targetUser->id,
            'status' => 'approved',
            'created_at' => now()
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id . '&sort=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals($newReview->id, $data[0]['id']);
        $this->assertEquals($oldReview->id, $data[1]['id']);
    }

    /** @test */
    public function it_includes_user_info_with_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'is_anonymous' => false
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.user.id', $this->authUser->id)
            ->assertJsonPath('data.0.user.name', $this->authUser->name);
    }

    /** @test */
    public function it_hides_user_info_for_anonymous_reviews()
    {
        $review = Review::factory()->create([
            'user_id' => $this->authUser->id,
            'reviewable_user_id' => $this->targetUser->id,
            'ad_id' => $this->ad->id,
            'is_anonymous' => true
        ]);

        $response = $this->getJson('/api/reviews?user_id=' . $this->targetUser->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.user', null);
    }

    /** @test */
    public function it_can_get_review_statistics()
    {
        // Создаем отзывы с разными рейтингами
        Review::factory()->create([
            'reviewable_user_id' => $this->targetUser->id,
            'rating' => 5,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->targetUser->id,
            'rating' => 4,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->targetUser->id,
            'rating' => 5,
            'status' => 'approved'
        ]);

        $response = $this->getJson('/api/reviews/stats/' . $this->targetUser->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_reviews',
                    'average_rating',
                    'rating_distribution'
                ]
            ])
            ->assertJson([
                'data' => [
                    'total_reviews' => 3,
                    'average_rating' => 4.7
                ]
            ]);
    }
}