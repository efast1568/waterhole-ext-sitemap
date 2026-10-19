<?php

namespace Flectar\Sitemap\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Waterhole\Models\Channel;
use Waterhole\Models\Page;
use Waterhole\Models\Post;

class SitemapController extends Controller
{
    protected $cacheTime = 86400;

    protected $perPage = 5000;

    public function index()
    {
        return $this->respond("index", function () {
            $sitemaps = [
                route("waterhole-sitemap.pages"),
                route("waterhole-sitemap.channels"),
            ];

            $postPages = $this->pageCount($this->postsQuery()->count());

            $userPages = $this->pageCount(
                $this->postsQuery()->distinct()->count("user_id"),
            );

            for ($page = 1; $page <= $postPages; $page++) {
                $sitemaps[] = $this->pageRoute("posts", $page);
            }

            for ($page = 1; $page <= $userPages; $page++) {
                $sitemaps[] = $this->pageRoute("users", $page);
            }

            return view("waterhole-sitemap::sitemapindex", [
                "sitemaps" => $sitemaps,
            ])->render();
        });
    }

    public function pages()
    {
        return $this->respond("pages", function () {
            $home = $this->formatUrl(
                route("waterhole.home"),
                $this->postsQuery()->max("last_activity_at"),
                "daily",
                "1.0",
            );

            $pages = $this->listed(Page::query())
                ->orderBy("id")
                ->get()
                ->map(
                    fn(Page $page) => $this->formatUrl(
                        $page->url,
                        null,
                        "monthly",
                        "0.7",
                    ),
                );

            return $this->urlset([$home, ...$pages]);
        });
    }

    public function channels()
    {
        return $this->respond("channels", function () {
            $activity = $this->postsQuery()
                ->toBase()
                ->groupBy("channel_id")
                ->selectRaw("channel_id, max(last_activity_at) as lastmod")
                ->pluck("lastmod", "channel_id");

            $channels = $this->listed(Channel::query())
                ->orderBy("id")
                ->get()
                ->map(
                    fn(Channel $channel) => $this->formatUrl(
                        $channel->url,
                        $activity[$channel->id] ?? null,
                        "weekly",
                        "0.8",
                    ),
                );

            return $this->urlset($channels->all());
        });
    }

    public function posts(int $page = 1)
    {
        return $this->respond("posts.$page", function () use ($page) {
            $posts = $this->postsQuery()
                ->select([
                    "id",
                    "slug",
                    "created_at",
                    "edited_at",
                    "last_activity_at",
                ])
                ->orderBy("id")
                ->forPage($page, $this->perPage)
                ->get();

            if ($posts->isEmpty() && $page > 1) {
                return null;
            }

            return $this->urlset(
                $posts
                    ->map(
                        fn(Post $post) => $this->formatUrl(
                            $post->url,
                            collect([
                                $post->created_at,
                                $post->edited_at,
                                $post->last_activity_at,
                            ])
                                ->filter()
                                ->max(),
                            "monthly",
                            "0.6",
                        ),
                    )
                    ->all(),
            );
        });
    }

    public function users(int $page = 1)
    {
        return $this->respond("users.$page", function () use ($page) {
            // Only users with at least one public post have a profile worth
            // indexing; their latest post is when that profile last changed.
            $users = $this->postsQuery()
                ->toBase()
                ->whereNotNull("user_id")
                ->groupBy("user_id")
                ->selectRaw("user_id, max(created_at) as lastmod")
                ->orderBy("user_id")
                ->forPage($page, $this->perPage)
                ->get();

            if ($users->isEmpty() && $page > 1) {
                return null;
            }

            return $this->urlset(
                $users
                    ->map(
                        fn($user) => $this->formatUrl(
                            route("waterhole.user.posts", [
                                "user" => $user->user_id,
                            ]),
                            $user->lastmod,
                            "weekly",
                            "0.5",
                        ),
                    )
                    ->all(),
            );
        });
    }

    /**
     * Posts that a guest can see in channels that are listed in the forum
     * structure. Waterhole marks posts in unlisted channels as noindex.
     */
    protected function postsQuery(): Builder
    {
        return Post::query()
            ->visible(null)
            ->whereIn(
                "channel_id",
                $this->listed(Channel::query())->select("id"),
            );
    }

    /**
     * Restrict a structurable model query to records that a guest can view
     * and that are listed in the forum structure.
     *
     * The query is scoped to a guest explicitly rather than to the current
     * user, because the result is cached and served to everyone.
     */
    protected function listed(Builder $query): Builder
    {
        $model = $query->getModel();

        return $query
            ->withoutGlobalScope("visible")
            ->whereKey($model::allPermitted(null))
            ->whereHas(
                "structure",
                fn($query) => $query
                    ->withoutGlobalScope("hasVisibleContent")
                    ->where("is_listed", true),
            );
    }

    protected function respond(string $key, callable $callback)
    {
        // URLs are generated from the current request, so cache them per host.
        $key = "waterhole-sitemap." . md5(request()->root()) . ".$key";

        $xml = Cache::get($key);

        if ($xml === null) {
            $xml = $callback() ?? false;

            Cache::put($key, $xml, $this->cacheTime);
        }

        abort_if($xml === false, 404);

        return response($xml, 200, [
            "Content-Type" => "application/xml; charset=UTF-8",
        ]);
    }

    protected function urlset(array $urls): string
    {
        return view("waterhole-sitemap::index", ["urls" => $urls])->render();
    }

    protected function formatUrl($loc, $lastmod, $changefreq, $priority)
    {
        return [
            "loc" => $loc,
            "lastmod" => $lastmod
                ? Date::parse($lastmod)->toAtomString()
                : null,
            "changefreq" => $changefreq,
            "priority" => $priority,
        ];
    }

    protected function pageCount(int $total): int
    {
        return max(1, (int) ceil($total / $this->perPage));
    }

    protected function pageRoute(string $sitemap, int $page): string
    {
        return route(
            "waterhole-sitemap.$sitemap",
            $page > 1 ? ["page" => $page] : [],
        );
    }
}
