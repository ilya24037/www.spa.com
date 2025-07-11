@echo off
echo ========== RESTORE laravel_auth ==========
set MYSQL_BIN="C:\Program Files\MySQL\MySQL Server 8.4\bin"
%MYSQL_BIN%\mysql.exe -u root -p -e "DROP DATABASE IF EXISTS laravel_auth; CREATE DATABASE laravel_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
%MYSQL_BIN%\mysql.exe -u root -p laravel_auth < "D:\backup\laravel_auth_backup.sql"
echo ========== DONE ==========
pause
