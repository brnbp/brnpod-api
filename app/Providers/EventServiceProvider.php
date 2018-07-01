<?php
namespace App\Providers;

use App\Events\ContentRated;
use App\Events\EpisodeCreated;
use App\Events\UserListeningEpisode;
use App\Events\UserRatedEpisode;
use App\Listeners\RecalculateRating;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ContentRated::class => [
            RecalculateRating::class,
        ],
        EpisodeCreated::class => [
        ],
        UserListeningEpisode::class => [

        ],
        UserRatedEpisode::class => [

        ],
    ];
}
