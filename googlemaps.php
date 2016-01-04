<?php

/* Copyright 2015, 2016 Francis Meyvis*/

/**
 * Googlemaps a Grav plugin
 *
 * This plugin inserts googlemap object(s) into the resulting HTML document
 * using Google's google map API service.
 *
 * Borrows logic/inspiration from the Grav Toc plugin.
 *
 * Licensed under MIT, see LICENSE.
 *
 * @package     Googlemaps
 * @version     0.2.3
 * @link        <https://github.com/aptly-io/grav-plugin-googlemaps>
 * @author      Francis Meyvis <https://aptly.io/contact>
 * @copyright   2015, Francis Meyvis
 * @license     MIT <http://opensource.org/licenses/MIT>
 */

// use following namespace to avoids bin/gpm fails
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class GooglemapsPlugin extends Plugin
{
    // note: snippet, for a hovering message, does not work as advertised ... Google!?!
    private static $FIELD_NAMES = [
        'location', // the marker's location
        'title',    // the marker's title appearing when hovering over
        'snippet',  // the marker's additional info when hovering over
        'zIndex',   // the marker's order among other markers
        'timeout',  // the marker's dropdown timeout
        'info',     // the info for the infowindow when clicking the marker
        'icon',     // the picture URL for replacing the standard marker's look
        'link'      // the target when clicking the marker
    ];
    
    private static $GOOGLEMAPS_CONTAINER = 'googlemaps_container';
    
    /// Return a list of subscribed events
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }


    /// Initialize the plug-in
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            // For speed-up while the admin plugin is active
            $this->active = false;
        } else {
            if ($this->config->get('plugins.googlemaps.enabled')) {
                // if activated subscribe to additional events
                $this->enable([
                    'onTwigTemplatePaths'    => ['onTwigTemplatePaths',    0],
                    'onPageContentProcessed' => ['onPageContentProcessed', 0],
                    'onPageContentFromCache' => ['onPageContentFromCache', 0],
                ]);
            }
        }
    }


    /// Register the enabled plugin's template PATH
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }


    /// Replace place markers [GOOGLEMAPS:<tagid>] with the googlemaps.html.twig to render a google maps
    public function onPageContentProcessed(Event $event)
    {
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled', true)) {
            // get current rendered content
            $content = $page->getRawContent();
            // replace marker(s) with Google map object(s)
            $page->setRawContent($this->processMarkdownContent($content, $config));            
        }
    }
    
    
    /// Look for hints in the page's cached content to see if it requires googlemap assets
    public function onPageContentFromCache(Event $event)
    {
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled', true)) {
            $this->processCacheContent($page->getRawContent(), $config);
        }
    }
    
    
    /// Setup the necessary css/js assets
    private function addAssets($config)
    {
        $assets = $this->grav['assets'];
        if ($config->get('built_in_css', false)) {
            $assets->addCss('plugin://googlemaps/assets/css/googlemaps.css');
        }
        $assets->addJs('https://maps.googleapis.com/maps/api/js?v=3', 3, true, null, 'bottom');
        $assets->addJs('plugin://googlemaps/assets/js/googlemaps.js', 2, true, null, 'bottom');
    }
    
    
    /// Setup one marker based on the page's YAML information
    private function createMarker($configMarker)
    {
        $marker = [];
        foreach (GooglemapsPlugin::$FIELD_NAMES as $field) {
            if (isset($configMarker[$field])) {
                $marker[$field] = $configMarker[$field];
            }
        }

        return $marker;
    }

    
    /// Setup a markers array to initialize a specific googlemap HTML object
    private function createMarkers($tagid, $config)
    {
        $markers = [];
        if ($config->get($tagid . ".markers")) {
            foreach ($config->value($tagid . ".markers") as $marker) {
                $markers[] = $this->createMarker($marker);
            }
        }
        return $markers;
    }
    
    
    /// Setup the twig variables to initialize a specific googlemap HTML object
    private function createGooglemapVars($tagid, $config)
    {
        // mandatory options for creating a google map object
        $mapOptions = [
            // default center: oudegem :-)
            'center'    => $config->value($tagid . ".center", "51.010009, 4.061270"),
            'zoom'      => $config->value($tagid . ".zoom", 12),
            'mapTypeId' => "google.maps.MapTypeId." . $config->value($tagid . ".type", "ROADMAP")
        ];

        // options for populating the map with KML, markers, info windows etc.
        $displayOptions = [
            'kmlStatus' => $config->value($tagid . ".kmlStatus", "false")
        ];

        if ($config->get($tagid . ".kmlUrl")) {
            $displayOptions['kmlUrl'] = $config->get($tagid . ".kmlUrl");
        }

        $displayOptions['markers'] = $this->createMarkers($tagid, $config);

        $vars['googlemaps'] = [
            'tagid'          => $tagid,          // identifies each googlemap object
            'mapOptions'     => $mapOptions,
            'displayOptions' => $displayOptions
        ];

        return $vars;
    }
    
    
    /// Replace all found markers with HTML and JS
    private function replaceMarkers($regex, $content, $markers)
    {
        return \preg_replace_callback(
            $regex,
            function ($matches) use ($markers) {
                static $idx = 0;
                return $markers[$idx++];
            },
            $content
        ); 
    }
    
    
    /// Replace each marker, markers are found through a regex.
    private function processMarkdownContent($content, $config)
    {
        // Find all occurrences of GOOGLEMAP in content
        // ~ marks the start and end of the pattern, i is an option for caseless matching
        // The pattern to match is
        // - optional <p>
        // - possible white space
        // - [GOOGLEMAPS:
        // - some identification (called tagid in documentation and source)
        // - possible white space
        // - </p> if there was the opening <p>
        static $REGEX = '~(<p>)?\s*\[(GOOGLEMAPS)\:(?P<tagid>[^\:\]]*)\]\s*(?(1)</p>)~i';

        $matches = false;
        if (\preg_match_all($REGEX, $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            // Found markers to replace; add necessary css and javascript to the document
            
            $this->addAssets($config);
            
            $twig = $this->grav['twig'];
            $assets = $this->grav['assets'];
            
            $replacements = [];
            foreach ($matches as $match) {
                $tagid = strtolower($match['tagid'][0]);
                $vars = $this->createGooglemapVars($tagid, $config);
                
                $googlemapsCall = $twig->processTemplate('partials/googlemapsCall' . TEMPLATE_EXT, $vars);
                $assets->addInlineJs($googlemapsCall, 1, 'bottom');

                $googlemapsVars = $twig->processTemplate('partials/googlemaps' . TEMPLATE_EXT, $vars);
                $replacements[] = $googlemapsVars; // Save rendered Googlemap for later replacement
            }
            $content = $this->replaceMarkers($REGEX, $content, $replacements);
        }

        return $content;
    }
    
    
    /// If hints are found, only then inserts assets for this cached content
    private function processCacheContent($content, $config)
    {      
        // Look for this type of hints: <div id="<tagid>" class="googlemaps" ></div>
        static $REGEX = '~<div\s*id="(?P<tagid>[^"\]\[\:]*)"\s+class="googlemaps"\s*\>\s*\<\/div\>~i';

        $matches = false;
        if (\preg_match_all($REGEX, $content, $matches, PREG_SET_ORDER)) {
            // Found tagids; add necessary css and javascript to the document
            
            $this->addAssets($config);
            
            $assets = $this->grav['assets'];
            $twig = $this->grav['twig'];

            foreach ($matches as $match) {
                $tagid = strtolower($match[1]);
                $vars = $this->createGooglemapVars($tagid, $config);
                $googlemapsCall = $twig->processTemplate('partials/googlemapsCall' . TEMPLATE_EXT, $vars);
                $assets->addInlineJs($googlemapsCall, 1, 'bottom');
            }
        }
    }
}
