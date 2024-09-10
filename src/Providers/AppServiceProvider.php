<?php
namespace App\Providers;

class AppServiceProvider
{
    public function register()
    {
        // Înregistrarea serviciilor în container
        $GLOBALS['container']->bind('UserService', function () {
            return new \App\Services\UserService();
        });

        $GLOBALS['container']->bind('TranslationService', function () {
            return new \App\Services\TranslationService();
        });

        $GLOBALS['container']->bind('ThemeService', function () {
            return new \App\Services\ThemeService();
        });
    }
}
