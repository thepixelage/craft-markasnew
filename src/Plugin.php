<?php

namespace thepixelage\fresh;

/**
 * Class Plugin
 *
 * @package thepixelage\fresh
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public function init()
    {
        parent::init();

        self::$plugin = $this;
    }

}
