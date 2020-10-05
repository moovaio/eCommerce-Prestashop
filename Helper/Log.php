<?php

class Log
{
    const PREFIX = 'MoovaModule::';
    public static function info($message)
    {
        $initLog = \Configuration::get('MOOVA_DEBUG', false);
        if ($initLog) {
            \PrestaShopLogger::addLog(self::PREFIX . $message);
        }
    }

    public static function error($message)
    {
        $initLog = Configuration::get('MOOVA_DEBUG', false);
        if ($initLog) {
            PrestaShopLogger::addLog(self::PREFIX . $message, 4);
        }
    }
}
