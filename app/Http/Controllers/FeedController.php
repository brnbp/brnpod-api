<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use Illuminate\Http\Response;
use App\Jobs\RegisterEpisodesFeed;
use App\Http\Controllers\Controller;
use App\Services\Itunes\Finder as ItunesFinder;

class FeedController extends Controller
{
    /**
     * @var Feed
     */
    private $Feed;

    public function __construct()
    {
        $this->Feed = new Feed();
    }

    /**
     * Cria feed e adiciona em fila a importa��o de episodios
     * @param string $name nome do podcast a ser criado
     * @return Illuminate\Http\Response
     */
    public function create(string $name)
    {
        if ($this->createFeeds($name) == false) {
            return (new Response())->setStatusCode(404);
        }

        $feed = $this->Feed->getContent();

        if ($feed == false) {
            return (new Response())->setStatusCode(404);
        }

        $this->dispatch(new RegisterEpisodesFeed($feed));
    }

    /**
     * Salva o podcast pesquisado no banco
     * @param string $name nome do podcast
     * @return bool
     */
    private function createFeeds(string $name)
    {
        $results = (new ItunesFinder($name))
            ->all();

        if ($results == false) {
            return false;
        }

        $this->Feed->storage($results);

        return true;
    }
}
