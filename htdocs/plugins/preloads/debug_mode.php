<?php


class icms_DebugEventHandler
{
    public static function setup()
    {
        icms_Event::attach('icms', 'loadService', [__CLASS__, 'loadService']);
    }

    /**
     * Called after the kernel initializes a service
     * @return	void
     */
    public static function loadService($params, $event)
    {
        switch ($params['name']) {
        case "config":
            global $xoopsOption, $icmsConfig;
            if (!isset($xoopsOption['nodebug']) || !$xoopsOption['nodebug']) {
                if ($icmsConfig['debug_mode'] == 1 || $icmsConfig['debug_mode'] == 2) {
                    error_reporting(E_ALL);
                    icms::$logger->enableRendering();
                    icms::$logger->usePopup = ($icmsConfig['debug_mode'] == 2);
                    if (icms::$db) {
                        icms_Event::attach('icms_db_IConnection', 'prepare', [__CLASS__, 'prepareQuery']);
                        icms_Event::attach('icms_db_IConnection', 'execute', [__CLASS__, 'executeQuery']);
                    }
                } else {
                    error_reporting(0);
                    icms::$logger->activated = false;
                }
            }
            break;
        }
    }
    
    /**
     * Adds the prepared sql statement to the debug console
     *
     * @param	array	$params
     * @param	unknown	$event
     */
    public static function prepareQuery($params, $event)
    {
        icms::$logger->addQuery('prepare: ' . $params['sql']);
    }
    
    /**
     * Adds the query to the debug console for statements or queries that are executed
     *
     * @param array $params
     * @param unknown $event
     * @return	void
     */
    public static function executeQuery($params, $event)
    {
        icms::$logger->addQuery('execute: ' . $params['sql'], $params['error'], $params['errorno']);
    }
}

icms_DebugEventHandler::setup();
