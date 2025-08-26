<?php

namespace Tests\Feature\Review;

use Tests\TestCase;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private User $masterUser;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем администратора
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin'
        ]);

        // Обычный пользователь
        $this->user = User::factory()->create([
            'email' => 'user@test.com'
        ]);

        // Пользователь-мастер
        $this->masterUser = User::factory()->create([
            'email' => 'master@test.com'
        ]);

        // Объявление мастера
        $this->ad = Ad::factory()->create([
            'user_id' => $this->masterUser->id,
            'title' => 'Test Service',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function admin_can_approve_review()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending',
            'rating' => 5,
            'comment' => 'Отличный сервис!'
        ]);

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'approved'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $review->id,
                    'status' => 'approved'
                ]
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function admin_can_reject_review()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending',
            'comment' => 'Неуместный контент'
        ]);

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'rejected',
            'rejection_reason' => 'Нарушение правил платформы'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $review->id,
                    'status' => 'rejected'
                ]
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'rejected'
        ]);
    }

    /** @test */
    public function regular_user_cannot_moderate_reviews()
    {
        Sanctum::actingAs($this->user);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'approved'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_shows_only_approved_reviews_to_public()
    {
        // Создаем отзывы с разными статусами
        $approved = Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'approved',
            'rating' => 5
        ]);

        $pending = Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'pending',
            'rating' => 4
        ]);

        $rejected = Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'rejected',
            'rating' => 3
        ]);

        // Неавторизованный запрос
        $response = $this->getJson('/api/reviews?user_id=' . $this->masterUser->id);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($approved->id, $data[0]['id']);
    }

    /** @test */
    public function admin_can_see_all_reviews()
    {
        Sanctum::actingAs($this->admin);

        // Создаем отзывы с разными статусами
        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'pending'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'status' => 'rejected'
        ]);

        $response = $this->getJson('/api/admin/reviews?user_id=' . $this->masterUser->id);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function it_can_get_pending_reviews_for_moderation()
    {
        Sanctum::actingAs($this->admin);

        Review::factory()->count(5)->create([
            'status' => 'pending'
        ]);

        Review::factory()->count(3)->create([
            'status' => 'approved'
        ]);

        $response = $this->getJson('/api/admin/reviews?status=pending');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(5, $data);
        
        foreach ($data as $review) {
            $this->assertEquals('pending', $review['status']);
        }
    }

    /** @test */
    public function it_validates_moderation_status()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'status' => 'pending'
        ]);

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_logs_moderation_actions()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'approved'
        ]);

        $response->assertStatus(200);

        // Проверяем что действие залогировано
        $this->assertDatabaseHas('review_moderation_logs', [
            'review_id' => $review->id,
            'moderator_id' => $this->admin->id,
            'old_status' => 'pending',
            'new_status' => 'approved'
        ]);
    }

    /** @test */
    public function it_sends_notification_on_approval()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        // Мокаем уведомления
        \Notification::fake();

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'approved'
        ]);

        $response->assertStatus(200);

        // Проверяем что уведомления отправлены
        \Notification::assertSentTo(
            $this->user,
            \App\Domain\Review\Notifications\ReviewApprovedNotification::class
        );

        \Notification::assertSentTo(
            $this->masterUser,
            \App\Domain\Review\Notifications\NewReviewNotification::class
        );
    }

    /** @test */
    public function it_sends_notification_on_rejection()
    {
        Sanctum::actingAs($this->admin);

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_user_id' => $this->masterUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        \Notification::fake();

        $response = $this->patchJson('/api/admin/reviews/' . $review->id . '/moderate', [
            'status' => 'rejected',
            'rejection_reason' => 'Неуместный контент'
        ]);

        $response->assertStatus(200);

        \Notification::assertSentTo(
            $this->user,
            \App\Domain\Review\Notifications\ReviewRejectedNotification::class
        );
    }

    /** @test */
    public function it_can_bulk_approve_reviews()
    {
        Sanctum::actingAs($this->admin);

        $reviews = Review::factory()->count(3)->create([
            'status' => 'pending'
        ]);

        $reviewIds = $reviews->pluck('id')->toArray();

        $response = $this->patchJson('/api/admin/reviews/bulk-moderate', [
            'review_ids' => $reviewIds,
            'status' => 'approved'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Reviews moderated successfully',
                'count' => 3
            ]);

        foreach ($reviewIds as $reviewId) {
            $this->assertDatabaseHas('reviews', [
                'id' => $reviewId,
                'status' => 'approved'
            ]);
        }
    }

    /** @test */
    public function it_excludes_rejected_reviews_from_rating_calculation()
    {
        // Одобренные отзывы
        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'rating' => 5,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'rating' => 4,
            'status' => 'approved'
        ]);

        // Отклоненный отзыв не должен влиять на рейтинг
        Review::factory()->create([
            'reviewable_user_id' => $this->masterUser->id,
            'rating' => 1,
            'status' => 'rejected'
        ]);

        $response = $this->getJson('/api/reviews/stats/' . $this->masterUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_reviews' => 2,
                    'average_rating' => 4.5
                ]
            ]);
    }
}