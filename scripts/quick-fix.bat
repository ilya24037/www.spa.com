@echo off
chcp 65001 >nul
echo Quick TypeScript Fix
echo ===================

echo.
echo 1. Adding route imports...
echo import { route } from 'ziggy-js' >> temp_route.txt
powershell -Command "if (!(Select-String -Path 'resources/js/Pages/Auth/Login.vue' -Pattern 'ziggy-js' -Quiet)) { $content = Get-Content 'resources/js/Pages/Auth/Login.vue' -Raw; $lines = $content -split \"`n\"; for ($i = 0; $i -lt $lines.Length; $i++) { if ($lines[$i] -like '*@inertiajs/vue3*') { $lines = $lines[0..$i] + \"import { route } from 'ziggy-js'\" + $lines[($i+1)..($lines.Length-1)]; break } }; ($lines -join \"`n\") | Set-Content 'resources/js/Pages/Auth/Login.vue' -Encoding UTF8 -NoNewline }"
powershell -Command "if (!(Select-String -Path 'resources/js/Pages/Auth/Register.vue' -Pattern 'ziggy-js' -Quiet)) { $content = Get-Content 'resources/js/Pages/Auth/Register.vue' -Raw; $lines = $content -split \"`n\"; for ($i = 0; $i -lt $lines.Length; $i++) { if ($lines[$i] -like '*@inertiajs/vue3*') { $lines = $lines[0..$i] + \"import { route } from 'ziggy-js'\" + $lines[($i+1)..($lines.Length-1)]; break } }; ($lines -join \"`n\") | Set-Content 'resources/js/Pages/Auth/Register.vue' -Encoding UTF8 -NoNewline }"

echo 2. Fixing unused variables...
powershell -Command "(Get-Content 'resources/js/Pages/Dashboard.vue' -Raw) -replace 'const props = ', 'const _props = ' | Set-Content 'resources/js/Pages/Dashboard.vue' -Encoding UTF8 -NoNewline"
powershell -Command "(Get-Content 'resources/js/src/entities/master/ui/MasterCard/MasterCardList.vue' -Raw) -replace 'const props = ', 'const _props = ' | Set-Content 'resources/js/src/entities/master/ui/MasterCard/MasterCardList.vue' -Encoding UTF8 -NoNewline"

echo 3. Fixing simple type issues...
powershell -Command "(Get-Content 'resources/js/src/shared/composables/useDebounce.ts' -Raw) -replace 'import \{ ref, customRef, Ref \}', 'import { customRef, type Ref }' | Set-Content 'resources/js/src/shared/composables/useDebounce.ts' -Encoding UTF8 -NoNewline"

echo 4. Running build...
npm run build

pause