<?php

namespace thepixelage\fresh;

use Craft;

/**
 * Class Plugin
 *
 * @package thepixelage\fresh
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();

        self::$plugin = $this;
    }

}
