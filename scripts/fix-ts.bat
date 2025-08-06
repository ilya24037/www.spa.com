@echo off
chcp 65001 >nul
echo TypeScript Errors Fix Script
echo ============================

set /a filesFixed=0

echo.
echo 1. Fixing resolve() error...
powershell -Command "(Get-Content 'resources/js/src/entities/booking/model/bookingStore.ts' -Raw) -replace 'resolve\(\)', 'resolve(undefined)' | Set-Content 'resources/js/src/entities/booking/model/bookingStore.ts' -Encoding UTF8 -NoNewline"
if %errorlevel% equ 0 (
    echo Fixed: resolve^(^) error
    set /a filesFixed+=1
)

echo.
echo 2. Fixing Readonly error...
powershell -Command "(Get-Content 'resources/js/src/shared/ui/atoms/Input/Input.vue' -Raw) -replace ':Readonly=', ':readonly=' | Set-Content 'resources/js/src/shared/ui/atoms/Input/Input.vue' -Encoding UTF8 -NoNewline"
if %errorlevel% equ 0 (
    echo Fixed: Readonly error
    set /a filesFixed+=1
)

echo.
echo 3. Fixing modal variants...
powershell -Command "(Get-Content 'resources/js/src/shared/ui/molecules/Modal/AlertModal.vue' -Raw) -replace 'variant=\"info\"', 'variant=\"primary\"' | Set-Content 'resources/js/src/shared/ui/molecules/Modal/AlertModal.vue' -Encoding UTF8 -NoNewline"
powershell -Command "(Get-Content 'resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue' -Raw) -replace 'variant=\"info\"', 'variant=\"primary\"' | Set-Content 'resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue' -Encoding UTF8 -NoNewline"
echo Fixed: modal variants

echo.
echo 4. Fixing error types...
powershell -Command "(Get-Content 'resources/js/src/entities/service/model/serviceStore.ts' -Raw) -replace 'err\.message', '(err as Error).message' | Set-Content 'resources/js/src/entities/service/model/serviceStore.ts' -Encoding UTF8 -NoNewline"
powershell -Command "(Get-Content 'resources/js/src/entities/user/model/userStore.ts' -Raw) -replace 'err\.message', '(err as Error).message' | Set-Content 'resources/js/src/entities/user/model/userStore.ts' -Encoding UTF8 -NoNewline"
echo Fixed: error types

echo.
echo 5. Adding missing ref imports...
powershell -Command "$content = Get-Content 'resources/js/src/shared/ui/organisms/Cards/useCard.ts' -Raw; if ($content -like '*from ''vue''*' -and $content -notlike '*ref,*') { $content -replace 'import \{ ([^}]*) \} from ''vue''', 'import { ref, $1 } from ''vue''' | Set-Content 'resources/js/src/shared/ui/organisms/Cards/useCard.ts' -Encoding UTF8 -NoNewline; echo 'Added ref import' }"

echo.
echo 6. Adding route imports...
powershell -Command "$file = 'resources/js/src/features/auth/ui/AuthModal/AuthModal.vue'; $content = Get-Content $file -Raw; if ($content -notlike '*ziggy-js*') { $lines = $content -split \"`n\"; for ($i = 0; $i -lt $lines.Length; $i++) { if ($lines[$i] -like '*@inertiajs/vue3*') { $lines = $lines[0..$i] + \"import { route } from 'ziggy-js'\" + $lines[($i+1)..($lines.Length-1)]; break } }; ($lines -join \"`n\") | Set-Content $file -Encoding UTF8 -NoNewline; echo 'Added route to AuthModal' }"

powershell -Command "$file = 'resources/js/src/features/auth/ui/RegisterModal/RegisterModal.vue'; $content = Get-Content $file -Raw; if ($content -notlike '*ziggy-js*') { $lines = $content -split \"`n\"; for ($i = 0; $i -lt $lines.Length; $i++) { if ($lines[$i] -like '*@inertiajs/vue3*') { $lines = $lines[0..$i] + \"import { route } from 'ziggy-js'\" + $lines[($i+1)..($lines.Length-1)]; break } }; ($lines -join \"`n\") | Set-Content $file -Encoding UTF8 -NoNewline; echo 'Added route to RegisterModal' }"

echo.
echo Files processing completed
echo.
echo Running build check...
npm run build

echo.
echo Done!
pause