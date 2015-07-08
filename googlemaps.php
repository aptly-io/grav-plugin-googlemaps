<?php

/* Copyright 2015 Francis Meyvis*/

/**
 * Googlemaps a Grav plugin
 *
 * This plugin inserts a googlemaps object(s) into the resulting HTML document
 * using Google's google map API service.
 *
 * Borrows logic/inspiration from the Grav Toc plugin.
 *
 * Licensed under MIT, see LICENSE.
 *
 * @package     Googlemaps
 * @version     0.2.1
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

class GooglemapsPlugin extends Plugin
{

    /** Return a list of subscribed events*/
    public static function getSubscribedEvents()
    {
        return [
            // onPluginsInitialized event
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }


    /** Initialize the plug-in*/
    public function onPluginsInitialized()
    {
        /* Sommerregen explains this checks if the admin user is active.
         * If so, this plug-in disables itself.
         * rhukster mentions this is for speedup purposes related to the admin plugin
         */
        if ($this->isAdmin()) {
            $this->active = false;

        } else {

            if ($this->config->get('plugins.googlemaps.enabled')) {
                // if the plugin is activated, then subscribe to these additional events
                $this->enable([
                    'onTwigTemplatePaths'    => ['onTwigTemplatePaths',    0],
                    'onPageContentProcessed' => ['onPageContentProcessed', 0],
                    'onTwigSiteVariables'    => ['onTwigSiteVariables',    0],
                ]);
            }
        }
    }


    /** Register the enabled plugin's template PATH*/
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }


    /** Replace place markers [GOOGLEMAPS:<tagid>] with the googlemaps.html.twig to render a google maps*/
    public function onPageContentProcessed(Event $event)
    {
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled', true)) {
            // get current rendered content
            $content = $page->getRawContent();
            // replace marker(s) with Google map object(s)
            $page->setRawContent($this->process($content, $config));
        }
    }


    /** Setup the necessary assets*/
    public function onTwigSiteVariables()
    {
        $assets = $this->grav['assets'];
        $assets->addCss('plugin://googlemaps/assets/css/googlemaps.css');
        $assets->addJs('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false');
        $assets->addJs('plugin://googlemaps/assets/js/googlemaps.js');
    }


    /** Replace each marker, markers are found through a regex.*/
    private function process($content, $config)
    {
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
            foreach ($matches as $match) {

                $tagid = strtolower($match['tagid'][0]);

                // mandatory options for creating a google map object
                $mapOptions = [
                    // default center: oudegem :-)
                    'center'    => $config->value($tagid . ".center", "51.010009, 4.061270"),
                    'zoom'      => $config->value($tagid . ".zoom",   12),
                    'mapTypeId' => "google.maps.MapTypeId." . $config->value($tagid . ".type", "ROADMAP")
                ];

                // options for populating the map with KML, markers, info windows etc.

                $displayOptions = [
                    'kmlStatus' => $config->value($tagid . ".kmlStatus", "false")
                ];

                if ($config->get($tagid . ".kmlUrl")) {
                    $displayOptions['kmlUrl'] = $config->get($tagid . ".kmlUrl");
                }

                $markers = [];
                if ($config->get($tagid . ".markers")) {
                    foreach ($config->value($tagid . ".markers") as $marker) {
                        $fields = [
                            'location', // the marker's location
                            'title',    // the marker's title appearing when hovering over
                            'snippet',  // the marker's additional info when hovering over (not working as advertised Google!?!)
                            'zIndex',   // the marker's order among other markers
                            'timeout',  // the marker's dropdown timeout
                            'info',     // the info for the infowindow when clicking the marker
                            'icon',     // the picture URL for replacing the standard marker's look
                            'link'      // the target when clicking the marker
                        ];
                        $tmp = [];
                        foreach ($fields as $field) {
                            if (isset($marker[$field])) {
                                $tmp[$field] = $marker[$field];
                            }
                        }
                        $markers[] = $tmp;
                    }
                }
                $displayOptions['markers'] = $markers;

                $vars['googlemaps'] = [
                    'tagid'          => $tagid,          // identifies each googlemap object
                    'mapOptions'     => $mapOptions,
                    'displayOptions' => $displayOptions
                ];

                $twig = $this->grav['twig'];
                $googlemaps = $twig->processTemplate('partials/googlemaps' . TEMPLATE_EXT, $vars);

                // Save rendered Googlemap for later replacement
                $replacements[] = $googlemaps;
            }

            // all markers found, now replaces these with HTML and JS
            $content = preg_replace_callback(
                $regex,
                function($match) use ($replacements)
                {
                    static $i = 0;
                    return $replacements[$i++];
                },
                $content);
        }
        return $content;
    }
}
