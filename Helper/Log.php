<?php

class Log
{
    const prefix = 'MoovaModule::';
    public static function info($message)
    {
        $initLog = \Configuration::get('MOOVA_DEBUG', false);
        if ($initLog) {
            \PrestaShopLogger::addLog(self::prefix . $message);
        }
    }

    public static function error($message)
    {
        $initLog = Configuration::get('MOOVA_DEBUG', false);
        if ($initLog) {
            PrestaShopLogger::addLog(self::prefix . $message, 4);
        }
    }
}
