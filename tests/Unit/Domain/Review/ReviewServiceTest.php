<?php

namespace Tests\Unit\Domain\Review;

use Tests\TestCase;
use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Models\Review;
use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Domain\Review\DTOs\UpdateReviewDTO;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;

class ReviewServiceTest extends TestCase
{
    use SafeRefreshDatabase;

    private ReviewService $reviewService;
    private User $reviewer;
    private User $reviewedUser;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reviewService = app(ReviewService::class);
        
        // Создаем тестовых пользователей
        $this->reviewer = User::factory()->create([
            'email' => 'reviewer@test.com',
            'name' => 'Reviewer'
        ]);
        
        $this->reviewedUser = User::factory()->create([
            'email' => 'reviewed@test.com',
            'name' => 'Reviewed User'
        ]);
        
        // Создаем тестовое объявление
        $this->ad = Ad::factory()->create([
            'user_id' => $this->reviewedUser->id,
            'title' => 'Test Ad',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_create_review_with_valid_data()
    {
        $dto = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 5,
            comment: 'Отличный сервис!',
            isAnonymous: false
        );

        $review = $this->reviewService->create($dto);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($this->reviewer->id, $review->user_id);
        $this->assertEquals($this->reviewedUser->id, $review->reviewable_user_id);
        $this->assertEquals($this->ad->id, $review->ad_id);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Отличный сервис!', $review->comment);
        $this->assertFalse($review->is_anonymous);
        $this->assertEquals('pending', $review->status);
    }

    /** @test */
    public function it_cannot_create_review_for_own_ad()
    {
        $this->expectException(ValidationException::class);

        $dto = new CreateReviewDTO(
            userId: $this->reviewedUser->id, // Тот же пользователь
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 5,
            comment: 'Самооценка',
            isAnonymous: false
        );

        $this->reviewService->create($dto);
    }

    /** @test */
    public function it_cannot_create_duplicate_review()
    {
        // Создаем первый отзыв
        $dto = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 5,
            comment: 'Первый отзыв',
            isAnonymous: false
        );

        $this->reviewService->create($dto);

        // Пытаемся создать дубликат
        $this->expectException(ValidationException::class);

        $dto2 = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 4,
            comment: 'Второй отзыв',
            isAnonymous: false
        );

        $this->reviewService->create($dto2);
    }

    /** @test */
    public function it_validates_rating_range()
    {
        $this->expectException(ValidationException::class);

        $dto = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 10, // Неверный рейтинг
            comment: 'Тест',
            isAnonymous: false
        );

        $this->reviewService->create($dto);
    }

    /** @test */
    public function it_can_update_own_review()
    {
        // Создаем отзыв
        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id,
            'rating' => 4,
            'comment' => 'Хорошо',
            'status' => 'approved'
        ]);

        $dto = new UpdateReviewDTO(
            rating: 5,
            comment: 'Отлично!',
            isAnonymous: true
        );

        $updated = $this->reviewService->update($review->id, $dto, $this->reviewer->id);

        $this->assertEquals(5, $updated->rating);
        $this->assertEquals('Отлично!', $updated->comment);
        $this->assertTrue($updated->is_anonymous);
    }

    /** @test */
    public function it_cannot_update_others_review()
    {
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id
        ]);

        $this->expectException(ValidationException::class);

        $dto = new UpdateReviewDTO(
            rating: 5,
            comment: 'Попытка изменить чужой отзыв'
        );

        $this->reviewService->update($review->id, $dto, $otherUser->id);
    }

    /** @test */
    public function it_can_delete_own_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id
        ]);

        $result = $this->reviewService->delete($review->id, $this->reviewer->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function it_cannot_delete_others_review()
    {
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id
        ]);

        $this->expectException(ValidationException::class);

        $this->reviewService->delete($review->id, $otherUser->id);
    }

    /** @test */
    public function it_can_get_reviews_for_user()
    {
        // Создаем несколько отзывов
        Review::factory()->count(3)->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'status' => 'approved'
        ]);

        Review::factory()->count(2)->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'status' => 'pending'
        ]);

        $approvedReviews = $this->reviewService->getByUser(
            $this->reviewedUser->id,
            ['status' => 'approved']
        );

        $this->assertCount(3, $approvedReviews);
        $this->assertTrue($approvedReviews->every(fn($r) => $r->status === 'approved'));
    }

    /** @test */
    public function it_can_moderate_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        $moderated = $this->reviewService->moderate($review->id, 'approved');

        $this->assertEquals('approved', $moderated->status);
    }

    /** @test */
    public function it_can_reject_review()
    {
        $review = Review::factory()->create([
            'user_id' => $this->reviewer->id,
            'reviewable_user_id' => $this->reviewedUser->id,
            'ad_id' => $this->ad->id,
            'status' => 'pending'
        ]);

        $rejected = $this->reviewService->moderate($review->id, 'rejected');

        $this->assertEquals('rejected', $rejected->status);
    }

    /** @test */
    public function it_calculates_average_rating_correctly()
    {
        // Создаем отзывы с разными рейтингами
        Review::factory()->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'rating' => 5,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'rating' => 4,
            'status' => 'approved'
        ]);

        Review::factory()->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'rating' => 3,
            'status' => 'approved'
        ]);

        // Неодобренный отзыв не должен учитываться
        Review::factory()->create([
            'reviewable_user_id' => $this->reviewedUser->id,
            'rating' => 1,
            'status' => 'pending'
        ]);

        $avgRating = $this->reviewService->getAverageRating($this->reviewedUser->id);

        $this->assertEquals(4.0, $avgRating);
    }

    /** @test */
    public function it_returns_zero_rating_when_no_reviews()
    {
        $avgRating = $this->reviewService->getAverageRating($this->reviewedUser->id);

        $this->assertEquals(0, $avgRating);
    }

    /** @test */
    public function it_validates_comment_length()
    {
        $this->expectException(ValidationException::class);

        $dto = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 5,
            comment: str_repeat('a', 1001), // Слишком длинный комментарий
            isAnonymous: false
        );

        $this->reviewService->create($dto);
    }

    /** @test */
    public function it_can_create_anonymous_review()
    {
        $dto = new CreateReviewDTO(
            userId: $this->reviewer->id,
            reviewableUserId: $this->reviewedUser->id,
            adId: $this->ad->id,
            rating: 4,
            comment: 'Анонимный отзыв',
            isAnonymous: true
        );

        $review = $this->reviewService->create($dto);

        $this->assertTrue($review->is_anonymous);
        $this->assertEquals($this->reviewer->id, $review->user_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}