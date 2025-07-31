<?php

/**
 * Скрипт для обновления namespace в перемещенных файлах
 */

$mappings = [
    // DTOs
    'app/Domain/User/DTOs' => 'App\\Domain\\User\\DTOs',
    'app/Domain/Booking/DTOs' => 'App\\Domain\\Booking\\DTOs',
    'app/Domain/Master/DTOs' => 'App\\Domain\\Master\\DTOs',
    'app/Domain/Payment/DTOs' => 'App\\Domain\\Payment\\DTOs',
    'app/Domain/Review/DTOs' => 'App\\Domain\\Review\\DTOs',
    'app/Domain/Notification/DTOs' => 'App\\Domain\\Notification\\DTOs',
    'app/Domain/Ad/DTOs' => 'App\\Domain\\Ad\\DTOs',
    'app/Domain/Media/DTOs' => 'App\\Domain\\Media\\DTOs',
    
    // Repositories
    'app/Domain/User/Repositories' => 'App\\Domain\\User\\Repositories',
    'app/Domain/Booking/Repositories' => 'App\\Domain\\Booking\\Repositories',
    'app/Domain/Master/Repositories' => 'App\\Domain\\Master\\Repositories',
    'app/Domain/Payment/Repositories' => 'App\\Domain\\Payment\\Repositories',
    'app/Domain/Review/Repositories' => 'App\\Domain\\Review\\Repositories',
    'app/Domain/Notification/Repositories' => 'App\\Domain\\Notification\\Repositories',
    'app/Domain/Ad/Repositories' => 'App\\Domain\\Ad\\Repositories',
    'app/Domain/Media/Repositories' => 'App\\Domain\\Media\\Repositories',
    
    // Actions
    'app/Domain/User/Actions' => 'App\\Domain\\User\\Actions',
    'app/Domain/Booking/Actions' => 'App\\Domain\\Booking\\Actions',
    'app/Domain/Master/Actions' => 'App\\Domain\\Master\\Actions',
    'app/Domain/Payment/Actions' => 'App\\Domain\\Payment\\Actions',
    'app/Domain/Review/Actions' => 'App\\Domain\\Review\\Actions',
    'app/Domain/Ad/Actions' => 'App\\Domain\\Ad\\Actions',
];

foreach ($mappings as $directory => $namespace) {
    if (!is_dir($directory)) {
        continue;
    }
    
    $files = glob($directory . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Обновляем namespace
        $content = preg_replace(
            '/namespace\s+App\\\\(DTOs|Repositories|Actions);/i',
            'namespace ' . $namespace . ';',
            $content
        );
        
        // Обновляем импорты
        $oldNamespaces = ['App\\DTOs', 'App\\Repositories', 'App\\Actions'];
        foreach ($oldNamespaces as $oldNs) {
            // Находим все импорты из старого namespace
            if (preg_match_all('/use\s+' . preg_quote($oldNs, '/') . '\\\\(\w+);/i', $content, $matches)) {
                foreach ($matches[1] as $className) {
                    // Определяем новый namespace для класса
                    $newNs = findNewNamespace($className, $mappings);
                    if ($newNs) {
                        $content = str_replace(
                            'use ' . $oldNs . '\\' . $className . ';',
                            'use ' . $newNs . '\\' . $className . ';',
                            $content
                        );
                    }
                }
            }
        }
        
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

// Создаем alias файл для обратной совместимости
$aliasContent = "<?php\n\n";
$aliasContent .= "// Aliases для обратной совместимости при рефакторинге\n\n";

// DTOs aliases
$dtoFiles = glob('app/Domain/*/DTOs/*.php');
foreach ($dtoFiles as $file) {
    $className = basename($file, '.php');
    $content = file_get_contents($file);
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        $newNamespace = $matches[1];
        $aliasContent .= "class_alias('$newNamespace\\$className', 'App\\DTOs\\$className');\n";
    }
}

// Repositories aliases
$repoFiles = glob('app/Domain/*/Repositories/*.php');
foreach ($repoFiles as $file) {
    $className = basename($file, '.php');
    $content = file_get_contents($file);
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        $newNamespace = $matches[1];
        $aliasContent .= "class_alias('$newNamespace\\$className', 'App\\Repositories\\$className');\n";
    }
}

// Actions aliases
$actionFiles = glob('app/Domain/*/Actions/*.php');
foreach ($actionFiles as $file) {
    $className = basename($file, '.php');
    $content = file_get_contents($file);
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        $newNamespace = $matches[1];
        $aliasContent .= "class_alias('$newNamespace\\$className', 'App\\Actions\\$className');\n";
    }
}

file_put_contents('app/Support/compatibility-aliases.php', $aliasContent);
echo "\nCreated compatibility aliases file\n";

// Обновляем composer.json для автозагрузки aliases
$composer = json_decode(file_get_contents('composer.json'), true);
if (!isset($composer['autoload']['files'])) {
    $composer['autoload']['files'] = [];
}
if (!in_array('app/Support/compatibility-aliases.php', $composer['autoload']['files'])) {
    $composer['autoload']['files'][] = 'app/Support/compatibility-aliases.php';
    file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated composer.json\n";
}

function findNewNamespace($className, $mappings) {
    foreach ($mappings as $dir => $ns) {
        if (file_exists($dir . '/' . $className . '.php')) {
            return $ns;
        }
    }
    return null;
}

echo "\nNamespace update completed!\n";
echo "Run 'composer dump-autoload' to refresh autoloading\n";