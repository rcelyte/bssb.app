<?php

use app\AWS\GameSessionArnParser;
use PHPUnit\Framework\TestCase;

class GameSessionArnParserTest extends TestCase
{
    public function testTryParse_Invalid()
    {
        $this->assertNull(GameSessionArnParser::tryParse("notanurn"));
        $this->assertNull(GameSessionArnParser::tryParse("arn:aws:notgamelift:::"));
        $this->assertNull(GameSessionArnParser::tryParse("arn:aws:gamelift:us-west-2::notagamesession"));
    }

    public function testTryParse_Valid()
    {
        $sampleResourceId =
            "gamesession/fleet-2e923221-53b7-4e1c-86e8-c4d1d4684864/eu-central-1/1ce08ada6372ec4bb443c85042ddd4a4";

        $result = GameSessionArnParser::tryParse("arn:aws:gamelift:us-west-2::{$sampleResourceId}");

        $this->assertSame("aws", $result->awsPartition);
        $this->assertSame("gamelift", $result->awsService);
        $this->assertSame("us-west-2", $result->awsRegion);
        $this->assertSame($sampleResourceId, $result->awsResourceId);
        $this->assertSame("fleet-2e923221-53b7-4e1c-86e8-c4d1d4684864", $result->fleetId);
        $this->assertSame("eu-central-1", $result->fleetRegion);
        $this->assertSame("1ce08ada6372ec4bb443c85042ddd4a4", $result->gameSessionId);
    }
}