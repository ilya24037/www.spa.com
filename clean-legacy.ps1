# clean-legacy.ps1
$files = @(
    'resources\js\Components\Cards\Card.vue',
    'resources\js\Components\Features\MasterShow\components\BookingWidget.vue',
    'resources\js\Components\Features\MasterShow\components\MasterInfo.vue',
    'resources\js\Components\Features\MasterShow\index.vue',
    'resources\js\Components\Features\PhotoUploader\index.vue',
    'resources\js\Components\Features\Services\index.vue',
    'resources\js\Components\Masters\MasterDescription\index.vue',
    'resources\js\Components\Masters\MasterDetails\index.vue',
    'resources\js\Components\Masters\MasterHeader\index.vue',
    'resources\js\Components\Media\MediaGallery\index.vue',
    'resources\js\Components\Media\MediaUploader\index.vue',
    'resources\js\Components\Modals\BookingModal.vue',
    'resources\js\Components\UI\Forms\InputError.vue',
    'resources\js\Components\UI\Forms\InputLabel.vue',
    'resources\js\Components\UI\Forms\PrimaryButton.vue',
    'resources\js\Components\UI\Forms\SecondaryButton.vue',
    'resources\js\Components\UI\Forms\TextInput.vue'
)

$deleted = 0
foreach ($file in $files) {
    if (Test-Path $file) {
        Remove-Item -Path $file -Force
        $deleted++
        Write-Host "Deleted: $file"
    }
}
Write-Host "Total deleted: $deleted files"