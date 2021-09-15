<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class HostedGameAnnouncer extends Model
{
    public int $id;
    public int $playerId;
    public int $gameId;
    public \DateTime $lastAnnounce;

    // -----------------------------------------------------------------------------------------------------------------

    public static function createOrUpdate(string $userId, string $userName, int $gameId): HostedGameAnnouncer
    {
        $now = new \DateTime('now');

        // --------------------------------
        // Create/update the player record

        $player = KnownPlayer::query()
            ->where('user_id = ?', $userId)
            ->andWhere('user_name = ?', $userName)
            ->querySingleModel();

        $isNewPlayer = false;

        if (!$player) {
            $player = new KnownPlayer();
            $player->userId = $userId;
            $player->userName = $userName;

            $isNewPlayer = true;
        }

        $player->lastSeen = $now;
        $player->save();

        // -----------------------------------
        // Create/update the announcer record

        $announcer = null;

        if (!$isNewPlayer) {
            $announcer = HostedGameAnnouncer::query()
                ->where('player_id = ?', $player->id)
                ->andWhere('game_id = ?', $gameId)
                ->querySingleModel();
        }

        if (!$announcer) {
            $announcer = new HostedGameAnnouncer();
            $announcer->playerId = $player->id;
            $announcer->gameId = $gameId;
        }

        $announcer->lastAnnounce = $now;
        $announcer->save();
        
        return $announcer;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function fetchPlayer(): KnownPlayer
    {
        return KnownPlayer::fetch($this->playerId);
    }

    public function fetchGame(): HostedGame
    {
        return HostedGame::fetch($this->gameId);
    }
}