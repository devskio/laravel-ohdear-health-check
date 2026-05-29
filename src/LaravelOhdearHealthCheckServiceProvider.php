<?php

namespace Devskio\LaravelOhdearHealthCheck;

use Devskio\LaravelOhdearHealthCheck\Http\Middleware\ValidateHealthCheckSecret;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelOhdearHealthCheckServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ohdear-health-check')
            ->hasConfigFile('ohdear-health-check')
            ->hasRoute('web');
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware('ohdear-health-check.secret', ValidateHealthCheckSecret::class);
    }
}
