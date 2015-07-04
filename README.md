# [Grav Google maps Plugin][project]

This plugin generates Google map object(s) in HTML based on specific markers in the markdown document.

## About

`googlemaps` is a plugin for [**Grav**](http://getgrav.org).
It generates Google map(s) based on special marker(s) in a Markdown document. 

The marker is `[GOOGLEMAP:<tagid>]`. 
The `<tagid>` distinguishes different googlemap objects in a single HTML page from one other.
It links a `<tagid>.kmz` file, with a `<div id="<tagid>">` and its corresponding javascript.
See how this might look:

![Screenshot Google map Plugin](assets/screenshot.png "Google map preview screenshot")


## Installation and Updates

There's a manual install and update method by downloading
[this plugin](https://github.com/aptly-io/grav-plugin-googlemaps) 
and extracting all plugin files to

	</your/site>/grav/user/plugins/googlemaps


## Usage

The plugin comes with a sensible and self explanary default configuration:


### Config Defaults

```yaml
# Global plugin configurations

enabled: true                # Set to activate this plugin
built_in_css: true           # Use built-in CSS of the plugin
```

If you need to change any value,
then the best process is to copy a modified version of the
[googlemaps.yaml](googlemaps.yaml) file into your `users/config/plugins/` folder.
This will override the default settings.


## License

Copyright 2015 Francis Meyvis.

[Licensed](LICENSE) for use under the terms of the [MIT license][mit-license].





[project]: https://github.com/aptly-io/grav-plugin-googlemaps
[issues]: https://github.com/aptly-io/grav-plugin-googlemaps/issues "GitHub Issues for Grav Googlemaps Plugin"
[mit-license]: http://www.opensource.org/licenses/mit-license.php "MIT license"

