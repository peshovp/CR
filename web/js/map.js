jQuery(document).ready(function () { 
    getGeoJSONData();
    $('#updatemap').html("Last refresh: " +new Date());
 });

function showNotification(type, text) {
    new Noty({
        theme: 'mint',
        type: type, /*alert, information, error, warning, notification, success*/
        text: text,
        timeout: 3000,
        layout: "topCenter",
    }).show();
};

// Overlay for popup info
var container_popup = document.getElementById('popup');
var popup_content = document.getElementById('popup-content');
var popup = new ol.Overlay({
    element: container_popup,
    positioning: 'bottom-center',
    stopEvent: false
});

var geoJSONFormat = new ol.format.GeoJSON();
var projection = ol.proj.get('EPSG:4326');
var extent_mounts;

var osm = new ol.layer.Tile({
    title: "OSM map",
    type: 'base',
    visible: true,
    source: new ol.source.OSM()
});

var esri = new ol.layer.Tile({
    type: 'base',
    title: 'ESRI World Imagery',
    visible: false,
    source: new ol.source.XYZ({
      attributions: [
        new ol.Attribution({
          html: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        })
      ],
      url: 'http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
    })
});

var layers  = {
    "rover": new ol.layer.Vector({
        title: 'Rovers',
        source: new ol.source.Vector({})
        }),
    "stream": new ol.layer.Vector({
        title: 'Mountpoints',
        source: new ol.source.Vector({})
    })
};

var BaselayerGroup = new ol.layer.Group({
    title: 'Base Layers',
    layers: [osm, esri]
});

var layerGroup = new ol.layer.Group({
    title: 'Layers',
    layers: [layers["stream"], layers["rover"]]
});

var styles = {
    "true": new ol.style.Style({
            image: new ol.style.RegularShape({
                fill: new ol.style.Fill({color: '#a1cc3aB3'}), // 70% alpha
                stroke: new ol.style.Stroke({color: '#007a3dFF', width: 2}), // 100% alpha
                points: 3,
                radius: 10,
                angle: 0
        })
    }),
    "false": new ol.style.Style({
            image: new ol.style.RegularShape({
                fill: new ol.style.Fill({color: '#ff0033B3'}), // 70% alpha
        stroke: new ol.style.Stroke({color: '#99001eFF', width: 2}), // 100% alpha
                points: 3,
                radius: 10,
                angle: 0
        })
    }),
    "RTK": [ 
        new ol.style.Style({
            image: new ol.style.RegularShape({
                stroke: new ol.style.Stroke({color: 'black', width: 2}),
                points: 4,
                radius: 10,
                radius2: 0,
                angle: 0
            })
        }),
        new ol.style.Style({
            image: new ol.style.Circle({
                stroke: new ol.style.Stroke({color: 'black', width: 2}),
                radius: 4
        })})
    ],
    "Float RTK": [ 
        new ol.style.Style({
            image: new ol.style.RegularShape({
                stroke: new ol.style.Stroke({color: 'black', width: 2}), 
                points: 4,
                radius: 10,
                radius2: 0,
                angle: 0
            })
        }),
        new ol.style.Style({
            image: new ol.style.Circle({
                stroke: new ol.style.Stroke({color: 'black', width: 2}),
                radius: 9
        })})
    ],
    "other": [
        new ol.style.Style({
            image: new ol.style.RegularShape({
                stroke: new ol.style.Stroke({color: 'black', width: 2}),
                points: 4,
                radius: 10,
                radius2: 0,
                angle: Math.PI / 4
            })
        })
    ]
};

var map = new ol.Map({
    layers: [
        BaselayerGroup,
        layerGroup     
    ], 
    overlays: [popup],
    target: 'map',
    view: new ol.View({
      center: ol.proj.transform([-4.72, 41.66], 'EPSG:4326', 'EPSG:3857'),
      zoom: 2
    })  
});

var layerSwitcher = new ol.control.LayerSwitcher({
    tipLabel: 'Leyenda'
});
map.addControl(layerSwitcher);


function AsignStyle(feature, t) {
    if (t == "stream") {
        var status = feature.get('status').toString();
        return styles[status];
    } else {
        var quality = feature.get('quality');
        if (quality == "RTK"){
            return styles["RTK"]; 
        } else if (quality == "Float RTK"){
            return styles["Float RTK"]; 
        } else {
            return styles["other"];
        }  
    } 
} 

// Function: get geoJSON data from streams and users
function getGeoJSONData() {
    map.getOverlays().clear(); // Borramos los overlays
    map.addOverlay(popup); // Se añade el overlay donde irá el popover al pulsar sobre cada mountpoint
    popup.setPosition(undefined); 
    // AJAX
    $.getJSON("./generateJSON.php", function(data) {
        
        layers["stream"].getSource().clear();
        layers["rover"].getSource().clear();


        var features = geoJSONFormat.readFeatures(data, {
            featureProjection: 'EPSG:3857'
        });

        features.forEach( function(feature) { 
            type = feature.get('type');
            feature.setStyle(AsignStyle(feature,type));
            layers[type].getSource().addFeatures([feature]);
        }) 

        extent_mounts = layers["stream"].getSource().getExtent();
        map.getView().fit(extent_mounts, map.getSize());
        map.getView().setZoom(map.getView().getZoom() - 1);
    })
    .done(function() { 

    })
    .fail(function(jqXHR, textStatus, errorThrown) { 
    }); 

    return 1;
}

var allFeaturesAtPixel = [];
map.on('click', function (evt) {
    allFeaturesAtPixel = [];
    popup.setPosition(undefined);
    var place = null;

    map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
        allFeaturesAtPixel.push(feature);
        place = feature;
    });

    if (place) {
        var i;
        
        var offset_height = 120;
        var new_center_px = [evt.pixel[0]+150, evt.pixel[1] - offset_height];
        
        if (allFeaturesAtPixel.length == 1) {
            makePopup(allFeaturesAtPixel[0], new_center_px);
        } else {        
            showNotification("warning", "More than one item. Please make more zoom");   
        }
    } else {
        popup.setPosition(undefined);
    }
});


/**
    makePopupPopUp
**/
function makePopup(feature, center) {
    var geometry = feature.getGeometry();
    var coord = geometry.getCoordinates(); 
    popup.setPosition(coord);
    // center map
    var view = map.getView();
    view.setCenter(map.getCoordinateFromPixel(center));
    
    var content = '';
    if (feature.get('type') == "stream"){
        var previous = new Date(parseInt(feature.get('last_update'))*1000);
        var current = new Date();
        var last_update = timeDifference(current, previous);
        content = '' +
            // POPUP HTML CODE
            '<div class="panel panel-default panel-mapa-popup">' +
            '<div class="panel-heading">' +
            '<h3 class="panel-title"><i class="fa fa-tasks " title="Stream"></i> ' + feature.get('mountpoint') + '<span class=\"label '+ feature.get('status') +' pull-right\">status</span> </h3>' +
            '</div>' +
            '<div class="panel-body">' +
            '<table class="table borderless">'+
            '<tbody>'+
            '<tr><td class="title-info"><spam>Sats. GPS</spam> '+ feature.get('n_gps') +'</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '<tr><td class="title-info"><spam>Sats. GLO</spam> '+ feature.get('n_glo') +'</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '<tr><td class="title-info"><spam>Last Update</spam> '+ last_update +'</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '</tbody>'+
            '</table>'+
            '</div>' +
            //END POPUP
            '</div>';
    } else {
        var previous = new Date(parseInt(feature.get('last_update'))*1000);
        var current = new Date();
        var last_update = timeDifference(current, previous);
        content = '' +
            // POPUP HTML CODE
            '<div class="panel panel-default panel-mapa-popup">' +
            '<div class="panel-heading">' +
            '<h3 class="panel-title"><i class="fa fa-user " title="User"></i> ' + feature.get('username') +  '<span class=\"label pull-right\">Q: ' + feature.get('quality') + '</span> </h3>' +
            '</div>' +
            '<div class="panel-body">' +
            '<table class="table borderless">'+
            '<tbody>'+
            '<tr><td class="title-info"><spam>Sats. used</spam> ' + feature.get('sats_used') + '&nbsp;&nbsp;'+
            '<spam>Latency</spam> ' + feature.get('latency') + '</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '<tr><td class="title-info"><spam>Coords.</spam> ' + feature.get('coordinates') + '</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '<tr><td class="title-info"><spam>Nearest Station</spam> ' + feature.get('distance_near') + ' Km ('+feature.get('near_station')+')</td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '<tr><td class="title-info"><spam>Last Update</spam> ' + last_update + ' </td></tr>'+
            '<tr><td><span></span></td></tr>'+
            '</tbody>'+
            '</table>'+
            '</div>' +
            //END POPUP
            '</div>';
    }    
    popup_content.innerHTML = content;
};


function timeDifference(current, previous) {
    
    var msPerMinute = 60 * 1000;
    var msPerHour = msPerMinute * 60;
    var msPerDay = msPerHour * 24;
    var msPerMonth = msPerDay * 30;
    var msPerYear = msPerDay * 365;
    
    var elapsed = current - previous;
    
    if (elapsed < msPerMinute) {
         return Math.round(elapsed/1000) + ' seconds ago';   
    }
    
    else if (elapsed < msPerHour) {
         return Math.round(elapsed/msPerMinute) + ' minutes ago';   
    }
    
    else if (elapsed < msPerDay ) {
         return Math.round(elapsed/msPerHour ) + ' hours ago';   
    }

    else if (elapsed < msPerMonth) {
         return '~' + Math.round(elapsed/msPerDay) + ' days ago';   
    }
    
    else if (elapsed < msPerYear) {
         return '~ ' + Math.round(elapsed/msPerMonth) + ' months ago';   
    }
    
    else {
         return '~ ' + Math.round(elapsed/msPerYear ) + ' years ago';   
    }
}

setInterval(
    function() {
        getGeoJSONData();
        $('#updatemap').html("Last refresh: " +new Date());
    },30000
  ); // every 30 seconds, update the data map