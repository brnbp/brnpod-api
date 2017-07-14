<?php
namespace App\Http\Controllers;

use App\Filter\Filter;
use App\Models\Episode;
use App\Models\User;
use App\Models\UserEpisode;
use App\Models\UserFeed;
use App\Repositories\EpisodesRepository;
use App\Repositories\FeedRepository;
use App\Repositories\UserEpisodesRepository;
use App\Repositories\UserFeedsRepository;
use App\Repositories\UserRepository;
use App\Transform\EpisodeTransformer;
use App\Transform\FeedTransformer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class UserEpisodesController extends ApiController
{
    private $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function one(User $username, Episode $episode)
    {
        $userFeed = UserFeedsRepository::idByEpisodeAndUser($episode->id, $username->id);

        $userEpisode = UserEpisodesRepository::first($userFeed, $episode->id);

        $episode = (new EpisodeTransformer)->transform($episode->toArray());
        $episode['paused_at'] = $userEpisode['paused_at'];

        return $this->responseData($episode);
    }

    /**
     * Get episodes from feedId
     * @param string $username
     * @param integer $feedId
     */
    public function show($username, $feedId)
    {
        $data = (new UserEpisodesRepository)
            ->retrieve($username, $feedId);

        $feed = (new FeedRepository)->first($feedId);
        $feed = (new FeedTransformer)->transform($feed);
        $feed['episodes'] =  $data;


        return $this->responseData($feed);
    }

    public function latests($username)
    {
        if ($this->filter->validateFilters() === false) {
            return $this->respondInvalidFilter();
        }

        $latestsEpisodes = (new UserEpisodesRepository)->latests($username, $this->filter);

        $response = $latestsEpisodes->map(function($episode) {
            $feed = (new FeedTransformer)->transform($episode);
            $ep = (new EpisodeTransformer)->transform($episode);
            $ep['paused_at'] = $episode['paused_at'];
            $feed['episode'] = $ep;
            unset($feed['episodes']);
            return $feed;
        });
        
        return $this->responseData($response);
    }
    
    public function listening($username)
    {
        $listening = (new UserEpisodesRepository)->listening($username);
        
        $response = $listening->map(function($episode) {
            $feed = (new FeedTransformer)->transform($episode);
            $ep = (new EpisodeTransformer)->transform($episode);
            $ep['paused_at'] = $episode['paused_at'];
            $feed['episode'] = $ep;
            unset($feed['episodes']);
            return $feed;
        });
    
    
        return $this->responseData($response);
    }

    public function attach($username)
    {
        $userId = UserRepository::getId($username);
        if (!$userId) {
            return $this->respondNotFound('user not found');
        }

        $content = Input::get('content');
        if (!$content) {
            return $this->respondBadRequest('payload not acceptable');
        }

        $userEpisodes = [];
        foreach ($content as $episode) {
            $userFeedId = UserFeedsRepository::idByEpisodeAndUser($episode['id'], $userId);
            if (!$userFeedId) {
                continue;
            }
            $userEpisodes[] = [
                'user_feed_id' => $userFeedId,
                'episode_id' => $episode['id'],
                'paused_at' => $episode['paused_at'],
            ];
        }

        if (!$userEpisodes) {
            return $this->respondBadRequest('User not follow feed from episodes passed');
        }

        Cache::forget('user_episodes_latests_' . $username);
        Cache::forget('user_episodes_' . $username);

        UserEpisodesRepository::batchCreate($userEpisodes);

        if (UserEpisodesRepository::hasEpisodes($userFeedId)) {
            UserFeedsRepository::markAllListened($userFeedId, false);
        }

        return $this->responseData(['created' => true]);
    }

    public function detach($username, $episodeId)
    {
        $userFeedId = UserFeedsRepository::idByEpisodeAndUsername($episodeId, $username);
        if (!$userFeedId) {
            return $this->respondNotFound('User not follow feed from episodes passed');
        }

        $deleted = UserEpisodesRepository::delete($userFeedId, $episodeId);

        if (UserEpisodesRepository::hasEpisodes($userFeedId) == false) {
            UserFeedsRepository::markAllListened($userFeedId);
        }

        Cache::forget('user_episodes_latests_' . $username);
        Cache::forget('user_episodes_' . $username);

        return  $deleted ?
            $this->respondSuccess(['removed' => true]) :
            $this->respondNotFound();
    }

    public function paused($username, $episodeId, $time)
    {
        $userFeedId = UserFeedsRepository::idByEpisodeAndUsername($episodeId, $username);

        UserEpisodesRepository::markAsPaused($userFeedId, $episodeId, $time);

        return $this->respondSuccess(['updated' => true]);
    }

    private function responseData($data)
    {
        return empty($data) ? $this->respondNotFound() : $this->respondSuccess($data);
    }
}
