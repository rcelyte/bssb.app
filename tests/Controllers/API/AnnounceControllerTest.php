<?php

namespace Controllers\API;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Controllers\API\AnnounceController;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockJsonRequest;

class AnnounceControllerTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    public static function setUpBeforeClass(): void
    {
        self::tearDownAfterClass();
    }

    public static function tearDownAfterClass(): void
    {
        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%"')
            ->execute();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tests actual

    private static ?HostedGame $fullAnnounceTestResult;

    public function testFullAnnounce()
    {
        // -------------------------------------------------------------------------------------------------------------
        // Create request

        $request = new MockJsonRequest([
            'ServerCode' => 'ABC12',
            'GameName' => 'My Game',
            'OwnerId' => 'unit_test_testFullAnnounce',
            'OwnerName' => 'My Name',
            'PlayerCount' => 3,
            'PlayerLimit' => 5,
            'IsModded' => true,
            'LobbyState' => MultiplayerLobbyState::GameRunning,
            'LevelId' => "custom_level_6D4021498979AB7C07D430C488C24DE45EEDADB4",
            'SongName' => '"It\'s a me, Mario!" - Super Mario 64',
            'SongAuthor' => "GilvaSunner",
            'Difficulty' => LevelDifficulty::Easy,
            'Platform' => ModPlatformId::STEAM,
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM,
            'MasterServerPort' => 2328
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        // -------------------------------------------------------------------------------------------------------------
        // Test basic response

        $response = (new AnnounceController())->announce($request);

        $this->assertSame(200, $response->code);
        $this->assertStringStartsWith("application/json", $response->headers["content-type"]);

        $responseJson = json_decode($response->body, true);

        $this->assertSame("ok", $responseJson['result']);
        $this->assertIsNumeric($announceId = $responseJson['id']);

        // -------------------------------------------------------------------------------------------------------------
        // Test data written to db

        $announce = HostedGame::fetch($announceId);

        $this->assertSame("ABC12", $announce->serverCode);
        $this->assertSame("My Game", $announce->gameName);
        $this->assertSame("unit_test_testFullAnnounce", $announce->ownerId);
        $this->assertSame("My Name", $announce->ownerName);
        $this->assertSame(3, $announce->playerCount);
        $this->assertSame(5, $announce->playerLimit);
        $this->assertSame(true, $announce->isModded);
        $this->assertSame(3, $announce->lobbyState);
        $this->assertSame("custom_level_6D4021498979AB7C07D430C488C24DE45EEDADB4", $announce->levelId);
        $this->assertSame('"It\'s a me, Mario!" - Super Mario 64', $announce->songName);
        $this->assertSame("GilvaSunner", $announce->songAuthor);        $this->assertTrue($announce->isModded);
        $this->assertSame(0, $announce->difficulty);
        $this->assertSame("steam", $announce->platform);
        $this->assertSame("steam.production.mp.beatsaber.com", $announce->masterServerHost);
        $this->assertSame(2328, $announce->masterServerPort);

        self::$fullAnnounceTestResult = $announce;
    }

    /**
     * @depends testFullAnnounce
     */
    public function testReplaceFullAnnounce()
    {
        // -------------------------------------------------------------------------------------------------------------
        // Create request

        $request = new MockJsonRequest([
            'ServerCode' => 'ABC12',
            'GameName' => 'My Game But Newer',
            'OwnerId' => 'unit_test_testFullAnnounce',
            'OwnerName' => 'My Name',
            'PlayerCount' => 2,
            'PlayerLimit' => 5,
            'IsModded' => true,
            'LobbyState' => MultiplayerLobbyState::LobbySetup,
            'Difficulty' => LevelDifficulty::Easy,
            'Platform' => ModPlatformId::STEAM,
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM,
            'MasterServerPort' => 2328,
            'LevelId' => null
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        // -------------------------------------------------------------------------------------------------------------
        // Test updated object

        $response = (new AnnounceController())->announce($request);
        $responseJson = json_decode($response->body, true);

        $this->assertSame("ok", $responseJson['result']);
        $this->assertSame(self::$fullAnnounceTestResult->id, $responseJson['id'],
            "The previously created announce should be replaced/updated, keeping its original id.");

        $updatedResult = HostedGame::fetch(self::$fullAnnounceTestResult->id);

        $this->assertSame("My Game But Newer", $updatedResult->gameName,
            "Game data should update when replacing the announce");
        $this->assertSame(self::$fullAnnounceTestResult->levelId, $updatedResult->levelId,
            "Extra data like level id should not be removed on update, even if NULL in update request");
    }

    public static MockJsonRequest $minimalAnnounceRequest;

    /**
     * @depends testFullAnnounce
     */
    public function testMinimalAnnounce()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testMinimalAnnounce'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        self::$minimalAnnounceRequest = $request;

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(200, $response->code);

        $responseJson = json_decode($response->body, true);
        $this->assertSame("ok", $responseJson['result']);

        $announceId = $responseJson['id'];
        $announce = HostedGame::fetch($announceId);

        $this->assertSame("Untitled Beat Game", $announce->gameName);
        $this->assertSame("Unknown", $announce->ownerName);
        $this->assertSame(1, $announce->playerCount);
        $this->assertSame(5, $announce->playerLimit);
        $this->assertFalse($announce->isModded);
        $this->assertSame(MultiplayerLobbyState::None, $announce->lobbyState);
        $this->assertNull($announce->levelId);
        $this->assertNull($announce->songName);
        $this->assertNull($announce->songAuthor);
        $this->assertNull($announce->difficulty);
        $this->assertSame("unknown", $announce->platform);
        $this->assertNull($announce->masterServerHost);
        $this->assertNull($announce->masterServerPort);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceAutomaticallyInfersPlatform()
    {
        $fnTestRequestPlatform = function (?string $masterServerHost, ?string $platform): ?string
        {
            $request = new MockJsonRequest([
                'ServerCode' => '12345',
                'OwnerId' => 'unit_test_testAnnounceAutomaticallyInfersPlatform',
                'MasterServerHost' => $masterServerHost,
                'Platform' => $platform
            ]);
            $request->method = "POST";
            $request->path = "/api/v1/announce";

            $response = (new AnnounceController())->announce($request);
            $this->assertSame(200, $response->code, "Sanity check: announce should return 200 OK");

            $json = json_decode($response->body, true);
            return HostedGame::fetch($json['id'])->platform;
        };

        $this->assertSame("unknown", $fnTestRequestPlatform(null, null),
            "Announce with neither platform nor master server should result in unknown platform");
        $this->assertSame("steam", $fnTestRequestPlatform(null, "steam"),
            "Announce with no master server should simply apply platform value");
        $this->assertSame("oculus", $fnTestRequestPlatform("oculus.production.mp.beatsaber.com", "steam"),
            "Announce with no specific master server should automatically set platform value, regardless of platform in request (oculus)");
        $this->assertSame("steam", $fnTestRequestPlatform("steam.production.mp.beatsaber.com", null),
            "Announce with no specific master server should automatically set platform value, regardless of platform in request (steam)");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceAutomaticallyInfersModded()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'LevelId' => 'custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7',
            'SongName' => 'Song',
            'SongAuthor' => 'Artist',
            'IsModded' => false,
            'OwnerId' => 'unit_test_testAnnounceAutomaticallyInfersModded'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(200, $response->code, "Sanity check: announce should return 200 OK");

        $json = json_decode($response->body, true);

        $game = HostedGame::fetch($json['id']);
        $this->assertTrue($game->isModded, "`custom_level_` prefix should force `IsModded` to true");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Test validations / rejections

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceRejectsNonModRequests()
    {
        $request = clone self::$minimalAnnounceRequest;
        unset($request->headers["x-bssb"]);
        unset($request->headers["user-agent"]);
        $this->assertFalse($request->getIsValidModClientRequest(),
            "Sanity check: test request should no longer be considered a valid mod client request");

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(400, $response->code);
    }

    public function testRejectsInvalidServerCodes()
    {
        $fnCreateRequest = function (string $serverCode): MockJsonRequest
        {
            $request = new MockJsonRequest([
                'ServerCode' => $serverCode,
                'OwnerId' => 'unit_test_testRejectsInvalidServerCodes'
            ]);
            $request->method = "POST";
            $request->path = "/api/v1/announce";
            return $request;
        };

        $this->assertSame(200, ((new AnnounceController())->announce($fnCreateRequest("12345")))->code,
            "5 digit server code should be accepted");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("1234")))->code,
            "4 digit server code should be rejected");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("123456")))->code,
            "6 digit server code should be rejected");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("áéáóç")))->code,
            "non-alphanumeric server code should be rejected");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testRejectsServerMessageOwnerId()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['OwnerId'] = "SERVER_MESSAGE";

        $this->assertSame(400, ((new AnnounceController())->announce($request))->code,
            "SERVER_MESSAGE as OwnerId should be rejected");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceCleansLevelId()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'LevelId' => 'custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C_71e5 (Moudoku ga Osou - Tootie)',
            'SongName' => 'Moudoku ga Osou',
            'SongAuthor' => 'Tootie',
            'IsModded' => true,
            'OwnerId' => 'unit_test_testAnnounceCleansLevelId'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);

        $game = HostedGame::fetch($json['id']);
        $this->assertSame("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C", $game->levelId);
    }
}
