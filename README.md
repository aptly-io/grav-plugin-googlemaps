# [Grav Google maps Plugin][project]

This plugin generates HTML Google map object(s) based on specific markers in the markdown document.

## About

`googlemaps` is a plugin for [**Grav**](http://getgrav.org).
This readme describes version 0.2.1.
The plugin recognizes special marker(s) in a Markdown document.
It replaces these with HTML Google map objects(s).
It borrows logic and inspiration from the
[Grav Toc plugin](https://github.com/sommerregen/grav-plugin-toc)
The Google map object is generated through
[Google's Google maps API](https://developers.google.com/maps/documentation/javascript/tutorial).

The marker's syntax is `[GOOGLEMAPS:<tagid>]`.
The `<tagid>` distinguishes different googlemaps objects in a single HTML page from one other.
With each `<tagid>` comes a [Grav page header]() settings to customize the googlemaps object.
See this screen dump how this might look:

<a name="screendump">
![Screen dump Google map Plugin](assets/screendump_annotated.png "Google map preview screenshot")
(it's taken from my [website](https://aptly.io/about/hiking))
</a>


## Issues

Please open a new [issue][issues]


## Installation and Updates

There's a manual install and update method by downloading
[this plugin](https://github.com/aptly-io/grav-plugin-googlemaps)
and extracting all plugin files to `</your/site>/grav/user/plugins/googlemaps`.


## Usage

The plugin comes with a sensible and self explanatory default plugin configuration.
Each page containing the `[GOOGLEMAPS:<tagid>]` marker can include  `<tagid>` specific configuration
to further customize the Google map with:
* [marker(s)](https://developers.google.com/maps/documentation/javascript/markers)
* [KML data layer](https://developers.google.com/maps/tutorials/kml/)

Each googlemaps object corresponds with a unique `<tagid>` to render correctly.
Special attention for modular pages, as these are joined into one single page,
make sure these `<tagid>` are all different as well.


### Operation of the plugin

1. The plugin searches the `[GOOGLEMAPS:<tagid>]` markers.
1. It extracts the configuration in the page header that corresponds with the <tagid>
1. _Each_ marker is replaced with
   - a `<div id="<tagid>"></div>`
   - a global JavaScript snippet that calls a helper function
     `initGoogleMaps(<tagid>, <configuration for the specific tagid>)`

The helper function uses the Google map API to do the real work:
instantiate the googlemaps object inside the foreseen `<div>` with matching configuration.


### Configuration Defaults

```yaml
# Global plugin configurations

enabled: true                # Set to activate this plugin
```

If you need to change any value,
then the best process is to copy a modified version of the
[googlemaps.yaml](googlemaps.yaml) file into your `users/config/plugins/` folder.
This overrides the default settings.


### Page settings

Let's use the earlier [screen dump](#screendump ) to describe the configuration

The markdown content contains some markers (with `tagid`s: `track_01` and `track_02`)
```markdown
#### A 15km trip
[GOOGLEMAPS:track_01]

To give you an idea of the scenery:

<a name="treasures">
![hidden treasures](trail_nextto_railway_scl10.png)
</a>

#### A 12km trail
[GOOGLEMAPS:track_02]
```

These `tagid` appear in the Grav page header under the `googlemaps` setting.
This allows customization as annotated in the earlier [screen dump](#screendump).
```yaml
googlemaps:
    track_01:
        center: 51.009314, 4.061254
        zoom: 12
        type: TERRAIN
        kmlUrl: https://aptly.io/user/pages/02.about/02.hiking/track_01.kmz
        kmlStatus: true
        markers:
            - location: 51.009358, 4.061578
              title: The local church
              zIndex: 1
              timeout: 1000
              info: <strong>The local church</strong>.<br/>Cleaned up in recent years!
            - location: 51.017227, 4.073198
              title: A secret passage
              zIndex: 2
              timeout: 2000
              icon:  https://aptly.io/user/pages/02.about/02.hiking/trail_nextto_railway_thumbnail.png
              link: "#treasures"
    track_02:
        center: 51.009314, 4.061254
        zoom: 12
        type: TERRAIN
        kmlUrl: https://aptly.io/user/pages/02.about/02.hiking/track_02.kmz
```

#### Explanation

* `center` defines the map's latitude/longitude center
* `zoom` is the map's size or zooming factor
* `type` holds the map's type. Possible values are `TERRAIN`, `ROADMAP`, `HYBRID` and `SATELLITE`
* `kmlUrl` points to KML data. It's rendered over the map.
  Here the KML is exported (as KMZ; a compressed KML format) from the
  [My Tracks](https://play.google.com/store/apps/details?id=com.google.android.maps.mytracks&hl=en)
  Android application and copied on the website.
* `kmlStatus` is false by default.
   Enabled it for clues in case the KML layer is not showing up properly.
* `markers` is an array of markers. Each marker support these settings:
  * `location` defines the marker's location on the map. It's the only mandatory setting.
  * `title` is text that shows up when hovering over the marker.
  * `zIndex` gives a z order value to the marker (making it appear before or after other markers)
  * `timeout` delays the drop down animation of the marker. Fancy when multiple markers have different delays.
  * `info` is the content of a pop-up when clicking the marker. It's mutual exclusive with the `link` option.
    As illustrated, Google map API supports HTML markup inside this info.
  * `icon` points to a custom marker image.
  * `link` holds the URL that's triggered when clicking the marker. It's mutual exclusive with the `info` option.
    Put page local references in quotes to avoid that the # is taken as a YAML comment.


## License

Copyright 2015 Francis Meyvis.

[Licensed](LICENSE) for use under the terms of the [MIT license][mit-license].





[project]: https://github.com/aptly-io/grav-plugin-googlemaps
[issues]: https://github.com/aptly-io/grav-plugin-googlemaps/issues "GitHub Issues for Grav Googlemaps Plugin"
[mit-license]: http://www.opensource.org/licenses/mit-license.php "MIT license"
