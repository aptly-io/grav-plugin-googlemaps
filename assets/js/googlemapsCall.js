
var io;
(function (io) {
    var aptly;
    (function (aptly) {
        var grav_plugin_googlemaps;
        (function (grav_plugin_googlemaps) {
        
google.maps.event.addDomListener(window, "load", function() {
    io.aptly.grav_plugin_googlemaps.initGoogleMaps("", 
                   mapOptions,
                   displayOptions,
                   controlStyle);
});
        })(grav_plugin_googlemaps = aptly.grav_plugin_googlemaps || (aptly.grav_plugin_googlemaps = {}));
    })(aptly = io.aptly || (io.aptly = {}));
})(io || (io = {}));
