/**
 * Created by franchan on 2/07/15.
 */

function initGoogleMaps(tagId) {
    var mapOptions = {
        center: new google.maps.LatLng(38,-79.5),
        zoom:3,
        mapTypeId: google.maps.MapTypeId.HYBRID /* SATELLITE, TERRAIN, ROADMAP*/
    }

    var map         = new google.maps.Map(document.getElementById(tagId), mapOptions);
    var overlay_url = "http://aptly.io/" + tagId + ".kmz";
    var overlay     = new google.maps.KmlLayer(overlay_url);

    overlay.setMap(map);

    google.maps.event.addListener(overlay,"status_changed", function() {
        document.getElementById(tagId + "Status").innerHTML = overlay.getStatus();
    });
}
