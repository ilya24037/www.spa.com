# Migrate Cards Components to FSD Structure
# SPA Platform - FSD Migration

Write-Host "Migrating Cards components to FSD structure..." -ForegroundColor Green

$legacyPath = "C:\www.spa.com\resources\js\Components\Cards"
$fsdPath = "C:\www.spa.com\resources\js\src\entities\ad\ui\AdCard"

# ItemImage migration
Write-Host "Migrating ItemImage..." -ForegroundColor Yellow
$itemImageSource = Get-Content "$legacyPath\ItemImage.vue" -Raw
$itemImageTs = $itemImageSource -replace '<script setup>', '<script setup lang="ts">'
Set-Content "$fsdPath\ItemImage.vue" $itemImageTs

# ItemStats migration
Write-Host "Migrating ItemStats..." -ForegroundColor Yellow
$itemStatsSource = Get-Content "$legacyPath\ItemStats.vue" -Raw
$itemStatsTs = $itemStatsSource -replace '<script setup>', '<script setup lang="ts">'
Set-Content "$fsdPath\ItemStats.vue" $itemStatsTs

Write-Host "Cards migration completed!" -ForegroundColor Green

# Now remove legacy Cards directory
Write-Host "Removing legacy Cards directory..." -ForegroundColor Yellow
Remove-Item "$legacyPath" -Recurse -Force

# Update import paths in ItemCard
Write-Host "Updating import paths in ItemCard.vue..." -ForegroundColor Yellow
$itemCardPath = "$fsdPath\ItemCard.vue"
$itemCardContent = Get-Content $itemCardPath -Raw

# Replace legacy import paths with relative paths
$updatedContent = $itemCardContent -replace "import ItemImage from './ItemImage.vue'", "import ItemImage from './ItemImage.vue'"
$updatedContent = $updatedContent -replace "import ItemContent from './ItemContent.vue'", "import ItemContent from './ItemContent.vue'"
$updatedContent = $updatedContent -replace "import ItemStats from './ItemStats.vue'", "import ItemStats from './ItemStats.vue'"
$updatedContent = $updatedContent -replace "import ItemActions from './ItemActions.vue'", "import ItemActions from './ItemActions.vue'"

Set-Content $itemCardPath $updatedContent

Write-Host "All Cards components successfully migrated to FSD!" -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Test the build process" -ForegroundColor White
Write-Host "2. Check ItemCard functionality" -ForegroundColor White
Write-Host "3. Migrate Map components next" -ForegroundColor White