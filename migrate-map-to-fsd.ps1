# Migrate Map Components to FSD Structure
# Move from Components/Map to src/features/map/ui/MapLegacy

Write-Host "Migrating Map components to FSD structure..." -ForegroundColor Green

$legacyMapPath = "C:\www.spa.com\resources\js\Components\Map"
$fsdMapPath = "C:\www.spa.com\resources\js\src\features\map\ui\MapLegacy"

# Check if legacy Map directory exists
if (Test-Path $legacyMapPath) {
    Write-Host "Found legacy Map directory, migrating..." -ForegroundColor Yellow
    
    # Copy components to FSD location
    $leafletMap = "$legacyMapPath\LeafletMap.vue"
    $realMap = "$legacyMapPath\RealMap.vue"
    
    if (Test-Path $leafletMap) {
        Write-Host "Copying LeafletMap.vue..." -ForegroundColor Yellow
        $leafletContent = Get-Content $leafletMap -Raw
        $leafletTs = $leafletContent -replace '<script setup>', '<script setup lang="ts">'
        Set-Content "$fsdMapPath\LeafletMap.vue" $leafletTs
    }
    
    if (Test-Path $realMap) {
        Write-Host "Copying RealMap.vue..." -ForegroundColor Yellow
        $realContent = Get-Content $realMap -Raw
        $realTs = $realContent -replace '<script setup>', '<script setup lang="ts">'
        Set-Content "$fsdMapPath\RealMap.vue" $realTs
    }
    
    # Remove legacy directory
    Write-Host "Removing legacy Map directory..." -ForegroundColor Yellow
    Remove-Item $legacyMapPath -Recurse -Force
    
    Write-Host "Map migration completed!" -ForegroundColor Green
} else {
    Write-Host "Legacy Map directory not found - components already in FSD!" -ForegroundColor Green
}

Write-Host "Map components are now in FSD structure!" -ForegroundColor Green