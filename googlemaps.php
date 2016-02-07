<?php

/* Copyright 2015, 2016 Francis Meyvis*/

/**
 * Googlemaps a Grav plugin
 *
 * This plugin inserts googlemap object(s) into the resulting HTML document
 * using Google's google map API service.
 *
 * Borrows inspiration from the Grav Toc plugin.
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
    /// marker options
    private static $MARKER_FIELD_NAMES = [
        'location', // the marker's location
        'title',    // the marker's title appearing when hovering over
        'zIndex',   // the marker's order among other markers
        'timeout',  // the marker's dropdown timeout
        'info',     // the info for the infowindow when clicking the marker
        'icon',     // the picture URL for replacing the standard marker's look
        'link'      // the target when clicking the marker
    ];

    /// additional options to override Google's default google map object appearance and behaviour
    private static $OPTIONAL_MAP_OPTIONS = [
        'backgroundColor',              // a background colour, pretty when zooming out
        'disableDefaultUI',             // false disables the default UI controls
        'disableDoubleClickZoom',       // false disables zoom/center on double click
        'draggable',                    // false disables dragging the map (associated customization options draggableCursor and draggingCursor are not supported)
        'draggableCursor',              // the name or url of the cursor to display when mousing over a draggable map
        'draggingCursor',               // the name or url of the cursor to display when the map is being dragged
        'keyboardShortcuts',            // false prevents map control from the keyboard, enabled by default
        'mapTypeControl',               // false disables the Map type control
        'mapTypeControlOptions',        // map type look and feel e.g. {style: 2, position: 11} for DROPDOWN_MENU at bottom center
        'maxZoom',                      // maximal zooming
        'minZoom',                      // mininmal zooming
        'scrollwheel',                  // false prevents zooming by scrollwheel
        'streetViewControl',            // false disables the street view peg man (avoid for maps without street road overlay)
        'streetViewControlOptions',     // the streetview look and feel e.g. { position: 11}
        'zoomControl',                  // false disables the zoom control
        'zoomControlOptions'            // the zoom control look and feel e.g. { position: 11}
    ];

    /// Manage our asset cache because Grav fails to do this properly
    protected $assetData;
    protected $assetId;


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
            // For speed-up when the admin plugin is active
            $this->active = false;
        } else {
            if ($this->config->get('plugins.googlemaps.enabled')) {
                // if the plugin is active globally, subscribe to additional events
                $this->enable([
                    'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
                    'onPageInitialized'   => ['onPageInitialized',   0]
                ]);
            }
        }
    }


    /// Register the enabled plugin's template PATH
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }
    
    
    /// Get googlemaps' asset from the cache for this page
    public function onPageInitialized()
    {
        // merge global with page specific googlemaps' yaml settings
        $defaults = (array) $this->config->get('plugins.googlemaps');
        $page = $this->grav['page'];
        if (isset($page->header()->googlemaps)) {
            $this->config->set('plugins.googlemaps', array_merge($defaults, $page->header()->googlemaps));
        }

        // if the plugin is active on this page
        if ($this->config->get('plugins.googlemaps.enabled', false)) {
            // subscribe to additional events
            $this->enable([
                'onPageContentProcessed' => ['onPageContentProcessed', 0],
                'onOutputGenerated'      => ['onOutputGenerated',      0]
            ]);

            // get this page's cached assets (if any)
            $cache = $this->grav['cache'];
            $this->assetId = md5('googlemaps' . $page->path() . $cache->getKey());
            $this->assetData = $cache->fetch($this->assetId);
            
            // set this page's assets from the cache (if any)
            $this->setAssetsFromData();
            $this->assetData = null; // avoid saving again in onOutputGenerated()
        }
    }


    /// Replace place markers [GOOGLEMAPS:<tagid>] with the googlemaps.html.twig to render a google maps
    public function onPageContentProcessed(Event $event)
    {
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        // get current rendered content
        $content = $page->getRawContent();
        // replace marker(s) with Google map object(s)
        $page->setRawContent($this->processMarkdownContent($content, $config));

        // set this page's assets that where dynamically generated
        $this->setAssetsFromData();
    }


    /// Save dynamically generated assets
    public function onOutputGenerated()
    {
        if (isset($this->assetData)) {
            $cache = $this->grav['cache'];
            $cache->save($this->assetId, $this->assetData);
        }
    }

        
    /// Add all the assets (from cache or dynamically generated) for this page
    private function setAssetsFromData()
    {
        if (is_array($this->assetData)) {
            $assets = $this->grav['assets'];
            foreach ($this->assetData as $item) {
                $assetType = $item['type'];
                if ($assetType == 'css') {
                    $assets->addCss($item['data']);
                } elseif ($assetType == 'js') {
                    $assets->addJs($item['data'], $item['prio'], true, null, $item['where']);
                } elseif ($assetType == 'inlinejs') {
                    $assets->addInlineJs($item['data'], $item['prio'], $item['where']);
                }
            }
        }
    }


    private function addAssetData($data, $type, $prio, $where)
    {
        $this->assetData[] = [
            'data'  => $data,
            'type'  => $type,
            'prio'  => $prio,
            'where' => $where
        ];
    }



    /// Setup the necessary css/js assets
    private function addAssets($config)
    {
        if ($config->get('built_in_css', false)) {
            $this->addAssetData('plugin://googlemaps/assets/css/googlemaps.css', 'css', null, null);
        }

        // need Google's library from the following URL
        $googleMapLibUri = 'https://maps.googleapis.com/maps/api/js?v=3';
        $apiKey = $config->get('apiKey', false);
        if (!is_bool($apiKey)) {
            $googleMapLibUri .= '&key=' . $apiKey;    // appends a Google's provided key if any
        }
        $this->addAssetData($googleMapLibUri, 'js', 3, 'bottom');
        $this->addAssetData('plugin://googlemaps/assets/js/googlemaps.js', 'js', 2, 'bottom');
    }


    /// Setup one marker based on the page's YAML information
    private function createMarker($configMarker)
    {
        $marker = [];
        foreach (GooglemapsPlugin::$MARKER_FIELD_NAMES as $field) {
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


    /// Setup the map's options based on the page's YAML information
    private function createMapOptions($tagid, $config)
    {
        // mandatory options for creating a google map object
        $mapOptions = [
            // default center: oudegem :-)
            'center'    => $config->value($tagid . ".center", "51.010009, 4.061270"),
            'zoom'      => $config->value($tagid . ".zoom", 12),
            // an array of possible values: HYBRID, ROADMAP, SATELLITE, TERRAIN
            'mapTypeId' => "google.maps.MapTypeId." . $config->value($tagid . ".type", "ROADMAP")
        ];

        // only take those options if specified in the header configuration
        foreach (GooglemapsPlugin::$OPTIONAL_MAP_OPTIONS as $field) {
            $idx = $tagid . "." . $field;
            if (isset($config[$idx])) {
                $mapOptions[$field] = $config[$idx];
            }
        }

        return $mapOptions;
    }

    
    /// Setup the twig variables to initialize a specific googlemap HTML object
    private function createGooglemapVars($tagid, $config)
    {
        // main options for creating a google map object
        $mapOptions = $this->createMapOptions($tagid, $config);
        
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
            'displayOptions' => $displayOptions,
            'controlStyle'   => $config->value($tagid . ".controlStyle", "")
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


    /// Replace each marker, markers are found based on reg. ex.
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
            $replacements = [];
            foreach ($matches as $match) {
                $tagid = strtolower($match['tagid'][0]);
                $vars = $this->createGooglemapVars($tagid, $config);

                $googlemapsCall = $twig->processTemplate('partials/googlemapsCall' . TEMPLATE_EXT, $vars);
                $this->addAssetData($googlemapsCall, 'inlinejs', 1, 'bottom');

                $googlemapsVars = $twig->processTemplate('partials/googlemaps' . TEMPLATE_EXT, $vars);
                $replacements[] = $googlemapsVars; // Save rendered Googlemap for later replacement
            }
            $content = $this->replaceMarkers($REGEX, $content, $replacements);
        }

        return $content;
    }
}
