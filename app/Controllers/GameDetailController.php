<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class GameDetailController
{
    const CACHE_KEY_PREFIX = "game_detail_page_";
    const CACHE_TTL = 60;

    public function getGameDetail(Request $request, string $hashId)
    {
        $id = HostedGame::hash2id($hashId);

        if (!$id) {
            // Not a valid hash id
            return new BadRequestResponse();
        }

        $resCacheKey = self::CACHE_KEY_PREFIX . $id;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $game = HostedGame::fetch($id);

        if (!$game) {
            // Not found, 404 redirect
            return new RedirectResponse('/', 404);
        }

        $level = null;

        if ($game->levelId) {
            $level = LevelRecord::query()
                ->where('level_id = ?', $game->levelId)
                ->querySingleModel();
        }

        $view = new View('game_detail.twig');
        $view->set('pageUrl', $game->getWebDetailUrl());
        $view->set('game', $game);
        $view->set('level', $level);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }
}