@echo off
echo Creating domain structure according to refactoring map...

REM Create Domain structure
mkdir app\Domain\User\Models 2>nul
mkdir app\Domain\User\Services 2>nul
mkdir app\Domain\User\Repositories 2>nul
mkdir app\Domain\User\DTOs 2>nul
mkdir app\Domain\User\Actions 2>nul
mkdir app\Domain\User\Traits 2>nul

mkdir app\Domain\Master\Models 2>nul
mkdir app\Domain\Master\Services 2>nul
mkdir app\Domain\Master\Repositories 2>nul
mkdir app\Domain\Master\DTOs 2>nul
mkdir app\Domain\Master\Actions 2>nul
mkdir app\Domain\Master\Traits 2>nul

mkdir app\Domain\Ad\Models 2>nul
mkdir app\Domain\Ad\Services 2>nul
mkdir app\Domain\Ad\Repositories 2>nul
mkdir app\Domain\Ad\DTOs 2>nul
mkdir app\Domain\Ad\Actions 2>nul

mkdir app\Domain\Media\Models 2>nul
mkdir app\Domain\Media\Services 2>nul
mkdir app\Domain\Media\Repositories 2>nul
mkdir app\Domain\Media\DTOs 2>nul

mkdir app\Domain\Review\Models 2>nul
mkdir app\Domain\Review\Services 2>nul
mkdir app\Domain\Review\Repositories 2>nul
mkdir app\Domain\Review\DTOs 2>nul
mkdir app\Domain\Review\Actions 2>nul

mkdir app\Domain\Payment\Models 2>nul
mkdir app\Domain\Payment\Services 2>nul
mkdir app\Domain\Payment\Repositories 2>nul
mkdir app\Domain\Payment\DTOs 2>nul
mkdir app\Domain\Payment\Actions 2>nul

mkdir app\Domain\Notification\Models 2>nul
mkdir app\Domain\Notification\Services 2>nul
mkdir app\Domain\Notification\Repositories 2>nul
mkdir app\Domain\Notification\DTOs 2>nul

mkdir app\Domain\Service\Models 2>nul
mkdir app\Domain\Service\Repositories 2>nul

REM Create Application structure
mkdir app\Application\Http\Controllers\Profile 2>nul
mkdir app\Application\Http\Controllers\Ad 2>nul
mkdir app\Application\Http\Controllers\Booking 2>nul
mkdir app\Application\Http\Middleware 2>nul
mkdir app\Application\Http\Requests 2>nul
mkdir app\Application\Exceptions 2>nul

REM Create Infrastructure structure
mkdir app\Infrastructure\Analysis\AiContext 2>nul
mkdir app\Infrastructure\External 2>nul
mkdir app\Infrastructure\Cache 2>nul

REM Create Support structure
mkdir app\Support\Helpers 2>nul
mkdir app\Support\Traits 2>nul

echo Domain structure created successfully!