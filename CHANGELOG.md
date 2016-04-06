# v0.3.5
## 06/04/2016

1. [](#bugfix)
  * A user pointed out a conflict with the assets plugin
1. [](#improved)
  * Address the build_in_css warning in the admin pages


# v0.3.4
## 05/03/2016

1. [](#improved)
  * Use a normal or minified `googlemaps.js` depending on the `debugger.enabled` yaml flag 
  * Add language URL parameter only if language is known
  * Document the `assets.js('bottom')` issue on not uptodate themes


# v0.3.2
## 28/02/2016

1. [](#improved)
  * Use cache->delete() when available in grav (version > 1.0.10)
  * Minify support through gulp


# v0.3.1
## 21/02/2016

1. [](#improved)
    * Support the language= param


# v0.3.0
## 20/02/2016

1. [](#improved)
    * Updated the readme.md
    * Using typescript (and namespace to avoid cluttering the JS globals)
    * Smart `enabled: false` per page yaml configuration (emptying the cache and discarding markers)


# v0.2.3
## 08/02/2016

1. [](#bugfix)
    * https://github.com/aptly-io/grav-plugin-googlemaps/issues/1
1. [](#improved)
    * Smarter css style sheet
    * Smart/more efficient asset generation
    * Support additional Google map settings


# v0.2.2
## 07/08/2015

1. [](#bugfix)
    * Global gm_updateMaps() JS for SectionWidget

# v0.2.1
## 08/07/2015

3. [](#bugfix)
    * Changes according release guidelines
    * timeout and type fix
2. [](#new)
    * Add extra functionality for KML and markers.
    * Clean-up the code and update the documentation.
1. [](#new)
    * Initial release
