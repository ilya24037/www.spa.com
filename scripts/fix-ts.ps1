# TypeScript errors fix script
chcp 65001 > $null

Write-Host "TypeScript Errors Fix Script" -ForegroundColor Cyan

$filesFixed = 0

function Fix-File {
    param($FilePath, $Find, $Replace, $Description)
    
    if (Test-Path $FilePath) {
        $content = Get-Content $FilePath -Raw -Encoding UTF8
        if ($content -like "*$Find*") {
            $content = $content -replace [regex]::Escape($Find), $Replace
            Set-Content $FilePath -Value $content -Encoding UTF8 -NoNewline
            $script:filesFixed++
            Write-Host "Fixed: $Description in $FilePath" -ForegroundColor Green
        }
    }
}

Write-Host "1. Fixing ref imports..." -ForegroundColor Yellow

Fix-File "resources/js/src/shared/composables/useDebounce.ts" "import { ref, customRef, Ref }" "import { customRef, Ref }" "Remove duplicate ref"
Fix-File "resources/js/src/shared/composables/useLoadingState.ts" "import { ref, Ref }" "import { ref, type Ref }" "Fix Ref import"
Fix-File "resources/js/src/shared/composables/useLocalStorage.ts" "import { watch, Ref, ref }" "import { watch, ref, type Ref }" "Fix import order"

Write-Host "2. Fixing route imports..." -ForegroundColor Yellow

$routeFiles = @(
    "resources/js/src/features/auth/ui/AuthModal/AuthModal.vue",
    "resources/js/src/features/auth/ui/RegisterModal/RegisterModal.vue"
)

foreach ($file in $routeFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -notlike "*ziggy-js*") {
            $lines = $content -split "`n"
            for ($i = 0; $i -lt $lines.Length; $i++) {
                if ($lines[$i] -like "*@inertiajs/vue3*") {
                    $lines = $lines[0..$i] + "import { route } from 'ziggy-js'" + $lines[($i+1)..($lines.Length-1)]
                    break
                }
            }
            $newContent = $lines -join "`n"
            Set-Content $file -Value $newContent -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Host "Added route import to $file" -ForegroundColor Green
        }
    }
}

Write-Host "3. Fixing simple errors..." -ForegroundColor Yellow

Fix-File "resources/js/src/entities/booking/model/bookingStore.ts" "resolve()" "resolve(undefined)" "Fix resolve"
Fix-File "resources/js/src/shared/ui/atoms/Input/Input.vue" ":Readonly=" ":readonly=" "Fix Readonly"
Fix-File "resources/js/src/shared/ui/molecules/Modal/AlertModal.vue" 'variant="info"' 'variant="primary"' "Fix variant"
Fix-File "resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue" 'variant="info"' 'variant="primary"' "Fix variant"

Write-Host "4. Fixing error types..." -ForegroundColor Yellow

$errorFiles = @(
    "resources/js/src/entities/service/model/serviceStore.ts",
    "resources/js/src/entities/user/model/userStore.ts"
)

foreach ($file in $errorFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -like "*err.message*") {
            $content = $content -replace "err\.message", "(err as Error).message"
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Host "Fixed error type in $file" -ForegroundColor Green
        }
    }
}

Write-Host "5. Adding missing ref imports..." -ForegroundColor Yellow

$refFiles = @(
    "resources/js/src/shared/ui/organisms/Cards/useCard.ts"
)

foreach ($file in $refFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -like "*from 'vue'*" -and $content -notlike "*ref,*") {
            $content = $content -replace "import \{ ([^}]*) \} from 'vue'", "import { ref, `$1 } from 'vue'"
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Host "Added ref import to $file" -ForegroundColor Green
        }
    }
}

Write-Host ""
Write-Host "Files fixed: $filesFixed" -ForegroundColor Cyan

if ($filesFixed -gt 0) {
    Write-Host ""
    Write-Host "Running build check..." -ForegroundColor Yellow
    npm run build
} else {
    Write-Host "No files needed fixing" -ForegroundColor Blue
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green