<?php

namespace app\BeatSaber;

use app\Common\CString;

final class MasterServer
{
    public const OFFICIAL_HOSTNAME_OCULUS = "oculus.production.mp.beatsaber.com";
    public const OFFICIAL_HOSTNAME_STEAM = "steam.production.mp.beatsaber.com";
    public const OFFICIAL_HOSTNAME_PS4 = "ps4.production.mp.beatsaber.com";

    public const OFFICIAL_HOSTNAME_SUFFIX = "mp.beatsaber.com";

    public static function getHostnameIsOfficial(?string $masterServerHostname)
    {
        if (!$masterServerHostname) {
            // We assume it's a master server if the platform is null/empty
            // This is mostly because old versions of the mod don't send this field, and don't support custom masters
            return true;
        }

        if (CString::endsWith($masterServerHostname, self::OFFICIAL_HOSTNAME_SUFFIX)) {
            return true;
        }

        return false;
    }
}