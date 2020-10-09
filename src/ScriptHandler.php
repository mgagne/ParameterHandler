<?php

namespace Inap\ParameterHandler;

use Composer\Script\Event;

class ScriptHandler
{
    public static function buildParameters(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        $config = $extras['inap-parameters'] ?? array();

        if (!is_array($config)) {
            throw new \InvalidArgumentException('The extra.inap-parameters setting must be an array.');
        }

        if (empty($config['definitions-file'])) {
            $config['definitions-file'] = 'parameters.yml';
        }

        $processor = new Processor($event->getIO(), $config);
        $processor->process();
    }
}
