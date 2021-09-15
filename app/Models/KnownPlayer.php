<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class KnownPlayer extends Model
{
    public int $id;
    public string $userId;
    public string $userName;
    public ?string $platform;
    public ?string $platformId;
    public \DateTime $firstSeen;
    public \DateTime $lastSeen;
}