<?php

namespace Biblia\Bible;

/**
 * Gating de funciones por licencia — ToS API.Bible:
 * §9.1  TTS efímero solo para Public Domain / CC que lo permiten.
 * §9.9  Correspondencia electrónica (mailto/push) solo PD / CC BY / CC BY-SA.
 * Se aplica a cualquier versión (local o servida por API).
 */
final class VersionLicense
{
    private const OPENISH = ['public_domain', 'cc-by-4.0', 'cc-by-sa-4.0'];
    // 'free-distribution' (v1602p, eBible.org) permite audio efímero; no es
    // contenido API.Bible así que §9.1 no la restringe — pero para
    // correspondencia electrónica mantenemos la lista conservadora del ToS.
    private const TTS_OK = ['public_domain', 'cc-by-4.0', 'cc-by-sa-4.0', 'free-distribution'];

    public static function ttsOk(array $version): bool
    {
        return in_array($version['license'] ?? '', self::TTS_OK, true);
    }

    /** El mailto de anotaciones solo manda referencias (no texto), pero el
     *  usuario podría copiarlo — el gate cubre el caso futuro. */
    public static function correspondenceOk(array $version): bool
    {
        return in_array($version['license'] ?? '', self::OPENISH, true);
    }

    /** ¿Se sirve vía API.Bible en runtime? */
    public static function viaApi(array $version): bool
    {
        return !empty($version['api_bible_id'] ?? null);
    }
}
