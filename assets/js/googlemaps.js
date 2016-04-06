/* Copyright 2015, 2016 Francis Meyvis */
/* v0.3.5 Glue for the Grav googlemaps plugin and Google Map's API*/
/// <reference path="typings/googlemaps/google.maps.d.ts" />
var io;
(function (io) {
    var aptly;
    (function (aptly) {
        var grav_plugin_googlemaps;
        (function (grav_plugin_googlemaps) {
            var gm_maps = []; // Holds each google map object with their center and zoom
            function initGoogleMaps(tagId, mapOptions, displayOptions, controlStyle) {
                // for the old style googlemap version <3.22, set controlStyle to the string azteca
                google.maps.controlStyle = controlStyle;
                // convert the formatted JS string to google.maps.LatLng JS object
                var values = mapOptions.center.split(",");
                mapOptions.center = new google.maps.LatLng(values[0], values[1]);
                var map = new google.maps.Map(document.getElementById(tagId), mapOptions);
                if (displayOptions.hasOwnProperty("kmlUrl")) {
                    /* Adding a KML layer
                     * For the rich information inside KML see https://developers.google.com/maps/tutorials/kml/ */
                    var overlayOptions = {
                        suppressInfoWindows: true,
                        preserveViewport: false,
                        map: map
                    };
                    var overlay = new google.maps.KmlLayer(displayOptions.kmlUrl, overlayOptions);
                    overlay.setMap(map);
                    // support clicking on the KLM data elements
                    google.maps.event.addListener(overlay, "click", function (event) {
                        var kmlInfoDiv = document.getElementById(tagId + "_KmlInfo");
                        kmlInfoDiv.innerHTML = event.featureData.infoWindowHtml;
                    });
                    // for debugging purposes, feedback from the fetching & processing of the KML data
                    if (true === displayOptions.kmlStatus) {
                        google.maps.event.addListener(overlay, "status_changed", function () {
                            document.getElementById(tagId + "_KmlStatus").innerHTML = overlay.getStatus().toLocaleString();
                        });
                    }
                }
                if (displayOptions.hasOwnProperty("markers")) {
                    // Adding markers
                    var markerIdx;
                    for (markerIdx in displayOptions.markers) {
                        setMarker(map, displayOptions.markers[markerIdx]);
                    }
                }
                // Make sure to re-center correctly when resizing
                google.maps.event.addDomListener(window, "resize", function () {
                    var center = map.getCenter();
                    google.maps.event.trigger(map, "resize");
                    map.setCenter(center);
                });
                // remember the object for use in gm_updateMaps() at any time later
                gm_maps.push({ 'map': map, 'center': map.getCenter(), 'zoom': map.getZoom() });
            }
            grav_plugin_googlemaps.initGoogleMaps = initGoogleMaps;
            /**
             * Update the displaying of a Google Map object
             *
             * When a hidden google.maps.Map object becomes visible, it needs a refresh.
             * This function is called as a hack from the SectionWidget plugin
             * As it's unknown which map to update, do all of them!
             */
            function gm_updateMaps() {
                for (var i = 0; i < gm_maps.length; ++i) {
                    google.maps.event.trigger(gm_maps[i].map, 'resize');
                    gm_maps[i].map.setCenter(gm_maps[i].center);
                    gm_maps[i].map.setZoom(gm_maps[i].zoom);
                }
            }
            /*
             * Inspiration taken from:
             * - http://gmap-tutorial-101.appspot.com/mapsapi101/2
             * - https://developers.google.com/maps/documentation/javascript/examples/marker-animations-iteration
             */
            function setMarker(map, markerData) {
                var values = markerData.location.split(",");
                var markerOptions = {
                    position: new google.maps.LatLng(values[0], values[1]),
                    animation: google.maps.Animation.DROP
                };
                // add a title to the marker (shown when hovering over)
                if (markerData.hasOwnProperty("title")) {
                    markerOptions.title = markerData.title;
                }
                // z-position the marker to the other markers
                if (markerData.hasOwnProperty("zIndex")) {
                    markerOptions.zIndex = parseInt(markerData.zIndex);
                }
                // the marker is non-standard Google marker; an image (pointed to by its URL)
                if (markerData.hasOwnProperty("icon")) {
                    var image = {
                        url: markerData.icon
                    };
                    markerOptions.icon = image;
                }
                var marker = new google.maps.Marker(markerOptions);
                if (markerData.hasOwnProperty("info")) {
                    // show popup info window when clicking the marker
                    var infoWindowOptions = {
                        content: markerData.info
                    };
                    var infoWindow = new google.maps.InfoWindow(infoWindowOptions);
                    google.maps.event.addListener(marker, "click", function (e) {
                        infoWindow.open(map, marker);
                    });
                }
                else if (markerData.hasOwnProperty("link")) {
                    // navigate to the link when clicking the marker
                    google.maps.event.addListener(marker, 'click', function (e) {
                        window.location.href = markerData.link;
                    });
                }
                var timeout = 0; // default is to drop down immediately
                if (markerData.hasOwnProperty("timeout")) {
                    // drop down the marker after a small delay
                    // this gives nice effect when multiple markers drop at different moments
                    timeout = parseInt(markerData.timeout);
                }
                window.setTimeout(function () {
                    marker.setMap(map);
                }, timeout);
            }
        })(grav_plugin_googlemaps = aptly.grav_plugin_googlemaps || (aptly.grav_plugin_googlemaps = {}));
    })(aptly = io.aptly || (io.aptly = {}));
})(io || (io = {}));
