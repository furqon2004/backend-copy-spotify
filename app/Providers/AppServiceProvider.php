<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use App\Repositories\Interfaces\EloquentRepositoryInterface;
use App\Repositories\Interfaces\SongRepositoryInterface;
use App\Repositories\SongRepository;
use App\Repositories\Interfaces\ArtistRepositoryInterface;
use App\Repositories\ArtistRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding Repository untuk performa data besar
        $this->app->bind(EloquentRepositoryInterface::class, SongRepository::class);
        $this->app->bind(SongRepositoryInterface::class, SongRepository::class);
        $this->app->bind(ArtistRepositoryInterface::class, ArtistRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Konfigurasi Scramble untuk mendukung Bearer Token (Input Token)
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
    }
}