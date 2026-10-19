<?php

namespace Flectar\Sitemap;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Flectar\Sitemap\Http\Controllers\SitemapController;

class SitemapServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(
            __DIR__ . "/../resources/views",
            "waterhole-sitemap",
        );

        if (!$this->app->routesAreCached()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes()
    {
        // Sitemaps are public and identical for every visitor, so the routes
        // deliberately skip the session and authentication middleware.
        Route::domain(config("waterhole.system.domain"))
            ->prefix("sitemap")
            ->name("waterhole-sitemap.")
            ->controller(SitemapController::class)
            ->group(function () {
                Route::get("/", "index")->name("index");
                Route::get("/pages", "pages")->name("pages");
                Route::get("/channels", "channels")->name("channels");
                Route::get("/posts/{page?}", "posts")
                    ->where("page", "[1-9][0-9]{0,8}")
                    ->name("posts");
                Route::get("/users/{page?}", "users")
                    ->where("page", "[1-9][0-9]{0,8}")
                    ->name("users");
            });
    }
}
