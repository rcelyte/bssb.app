<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class SystemConfig extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public ?string $serverMessage;

    // -----------------------------------------------------------------------------------------------------------------
    // Table

    public function getTableName(): string
    {
        return "system_config";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Instance

    private static ?SystemConfig $instance = null;

    public static function fetchInstance(): SystemConfig
    {
        if (!self::$instance) {
            self::$instance = SystemConfig::query()->querySingleModel();

            if (self::$instance === null) {
                self::$instance = new SystemConfig();
                self::$instance->id = 1;
                self::$instance->serverMessage = null;
                self::$instance->create();
            }
        }

        return self::$instance;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

    public function getCleanServerMessage(): ?string
    {
        return $this->serverMessage ? strip_tags($this->serverMessage) : null;
    }
}