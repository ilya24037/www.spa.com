# Cleanup Legacy Components Script
# SPA Platform - FSD Migration

Write-Host "Cleaning up legacy components..." -ForegroundColor Green

$projectRoot = "C:\www.spa.com"
$componentsPath = "$projectRoot\resources\js\Components"

# Step 1: Remove Form/Sections with broken CheckboxGroup dependencies
Write-Host "Step 1: Removing Form/Sections with broken dependencies..." -ForegroundColor Yellow

$formSectionsToDelete = @(
    "ClientsSection.vue",
    "ContactsSection.vue", 
    "DescriptionSection.vue",
    "DetailsSection.vue",
    "ExperienceSection.vue",
    "FeaturesSection.vue",
    "GeoSection.vue", 
    "GeographySection.vue",
    "LocationSection.vue",
    "ParametersSection.vue",
    "PhotosSection.vue",
    "PriceListSection.vue",
    "PriceSection.vue",
    "PromoSection.vue",
    "ScheduleSection.vue",
    "ServiceProviderSection.vue",
    "SpecialtySection.vue",
    "TitleSection.vue",
    "VideosSection.vue",
    "WorkFormatSection.vue"
)

foreach ($file in $formSectionsToDelete) {
    $filePath = "$componentsPath\Form\Sections\$file"
    if (Test-Path $filePath) {
        Write-Host "  Deleting: $file" -ForegroundColor Red
        Remove-Item $filePath -Force
    } else {
        Write-Host "  File not found: $file" -ForegroundColor Yellow
    }
}

# Step 2: Remove unused Features components  
Write-Host "Step 2: Removing unused Features components..." -ForegroundColor Yellow

# Remove archive file
$archiveFile = "$componentsPath\Features\PhotoUploader\архив index.vue"
if (Test-Path $archiveFile) {
    Write-Host "  Deleting: архив index.vue" -ForegroundColor Red
    Remove-Item $archiveFile -Force
}

# Remove Services components
$serviceCategory = "$componentsPath\Features\Services\components\ServiceCategory.vue"
$serviceItem = "$componentsPath\Features\Services\components\ServiceItem.vue"
$servicesConfig = "$componentsPath\Features\Services\config\services.json"

if (Test-Path $serviceCategory) {
    Write-Host "  Deleting: ServiceCategory.vue" -ForegroundColor Red
    Remove-Item $serviceCategory -Force
}

if (Test-Path $serviceItem) {
    Write-Host "  Deleting: ServiceItem.vue" -ForegroundColor Red
    Remove-Item $serviceItem -Force
}

if (Test-Path $servicesConfig) {
    Write-Host "  Deleting: services.json" -ForegroundColor Red
    Remove-Item $servicesConfig -Force
}

# Remove empty directories
$servicesComponents = "$componentsPath\Features\Services\components"
$servicesConfig = "$componentsPath\Features\Services\config"
$servicesPath = "$componentsPath\Features\Services"

if (Test-Path $servicesComponents) {
    $isEmpty = (Get-ChildItem $servicesComponents | Measure-Object).Count -eq 0
    if ($isEmpty) {
        Remove-Item $servicesComponents -Force
        Write-Host "  Removed empty components directory" -ForegroundColor Red
    }
}

if (Test-Path $servicesConfig) {
    $isEmpty = (Get-ChildItem $servicesConfig | Measure-Object).Count -eq 0
    if ($isEmpty) {
        Remove-Item $servicesConfig -Force
        Write-Host "  Removed empty config directory" -ForegroundColor Red
    }
}

if (Test-Path $servicesPath) {
    $isEmpty = (Get-ChildItem $servicesPath | Measure-Object).Count -eq 0
    if ($isEmpty) {
        Remove-Item $servicesPath -Force
        Write-Host "  Removed empty Services directory" -ForegroundColor Red
    }
}

# Step 3: Remove Masters components
Write-Host "Step 3: Removing Masters components..." -ForegroundColor Yellow

$mastersPath = "$componentsPath\Masters"
if (Test-Path $mastersPath) {
    Write-Host "  Removing Masters directory completely" -ForegroundColor Red
    Remove-Item $mastersPath -Recurse -Force
}

Write-Host "Cleanup completed!" -ForegroundColor Green
Write-Host "Statistics:" -ForegroundColor Cyan
Write-Host "  Form/Sections removed: $($formSectionsToDelete.Count)" -ForegroundColor White
Write-Host "  Features components removed: 4" -ForegroundColor White  
Write-Host "  Masters directory removed completely" -ForegroundColor White

Write-Host "PRESERVED for migration:" -ForegroundColor Yellow
Write-Host "  Cards/ components (used in FSD)" -ForegroundColor White
Write-Host "  Header/ and Footer/ (used in AppLayout)" -ForegroundColor White
Write-Host "  Map/ components (used in Pages)" -ForegroundColor White
Write-Host "  UI/ConfirmModal (used in Pages)" -ForegroundColor White
Write-Host "  Form/Sections/MediaSection and EducationSection (use FSD)" -ForegroundColor White
Write-Host "  Booking/Calendar (used in TimeSlotPicker)" -ForegroundColor White
Write-Host "  Features/MasterShow (has internal dependencies)" -ForegroundColor White
Write-Host "  Features/PhotoUploader/VideoUploader.vue (used in sections)" -ForegroundColor White