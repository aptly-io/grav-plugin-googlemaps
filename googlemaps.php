<?php

/*
 * Google maps v0.0.1
 *
 * This plugin inserts a google map into the resulting HTML document
 *
 * Licensed under MIT, see LICENSE.
 *
 * @package     GoogleMaps
 * @version     0.0.1
 * @link        <https://github.com/aptly-io/grav-plugin-googlemaps>
 * @author      Francis Meyvis <https://aptly.io/contact>
 * @copyright   2015, Francis Meyvis
 * @license     MIT <http://opensource.org/licenses/MIT>
 */

namespace Grav\Plugin;     // use this namespace to avoids bin/gpm fails

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

class GooglemapsPlugin extends Plugin {

    /** Return a list of subscribed events*/
    public static function getSubscribedEvents() {
        return [
            // onPluginsInitialized event
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }


    /** Initialize the plug-in*/
    public function onPluginsInitialized() {
        $this->log('GooglemapsPlugin.onPluginsInitialized');

        /* Found this undocumented & obscure snippet in many plug-ins. Backdoor?*/
        //if ($this->isAdmin()) {
        //    $this->active = false;
        //    return;
        //}

        if ($this->config->get('plugins.googlemaps.enabled')) {
            // if the plugin is activated, then subscribe to these additional events
            $this->enable([
                'onTwigTemplatePaths'    => ['onTwigTemplatePaths',    0],
                'onPageContentProcessed' => ['onPageContentProcessed', 0],
                'onTwigSiteVariables'    => ['onTwigSiteVariables',    0],
            ]);
        }
    }


    /** Register the enabled plugin's template PATH*/
    public function onTwigTemplatePaths() {
        $this->log('GooglemapsPlugin.onTwigTemplatePaths');

        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }


    /** Replace place markers [GOOGLEMAPS:<tagid>] with elements to render a google maps*/
    public function onPageContentProcessed(Event $event) {
        $this->log('GooglemapsPlugin.onPageContentProcessed');

        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled', true)) {
            // get current rendered content
            $content = $page->getRawContent();
            // replace marker(s) with Google map object(s)
            $page->setRawContent($this->process($content));
        }
    }


    /** Setup the necessary assets*/
    public function onTwigSiteVariables() {
        $this->log('GooglemapsPlugin.onTwigSiteVariables');

        $assets = $this->grav['assets'];
        if ($this->config->get('plugins.googlemaps.built_in_css')) {
            $assets->addCss('plugin://googlemaps/assets/css/googlemaps.css');
        }
        $assets->addJs('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false');
        $assets->addJs('plugin://googlemaps/assets/js/googlemaps.js');
    }


    /** Replace handler*/
    private function process($content) {
        $this->log('GooglemapsPlugin.process');

        $replacements = [];

        // Find all occurrences of GOOGLEMAP in content
        // ~ marks the start and end of the pattern, i is an option for caseless matching
        // The pattern to match is
        // - optional <p>
        // - possible white space
        // - [GOOGLEMAPS:
        // - some identification (called tagid)
        // - possible white space
        // - </p> if there was the opening <p>
        $regex = '~(<p>)?\s*\[(GOOGLEMAPS)\:(?P<tagid>[^\:\]]*)\]\s*(?(1)</p>)~i';

        if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $this->log('preg_match_all() = true');
            foreach ($matches as $match) {

                $this->log(serialize($match));

                $tagid = strtolower($match['tagid'][0]);
                $this->log("tagid: " . $tagid);

                // Render googlemaps through the twig

                $vars['googlemaps'] = [
                    'tagid' => $tagid
                ];                  // setup twig variables

                $twig = $this->grav['twig'];
                $googlemaps = $twig->processTemplate('partials/googlemaps' . TEMPLATE_EXT, $vars);

                // Save rendered Googlemap for later replacement
                $replacements[] = $googlemaps;
            }

            $content = preg_replace_callback(
                $regex,
                function($match) use ($replacements) {
                    static $i = 0;
                    return $replacements[$i++];
                },
                $content);
        }
        return $content;
    }


    private function log($msg) {
        //$this->grav['debugger']->addMessage($msg);
        //$this->grav['logger']->info($msg);
    }
}
