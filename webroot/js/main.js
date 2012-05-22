var options = {
	credentials:"Aps2uopHQbn7W2CfEa1I5HIbr30i4dMINXV5B8aTE9wA-dcMKHA0kjvYLeFkiECk",
	center: new Microsoft.Maps.Location(38.625, -90.23),
	mapTypeId: Microsoft.Maps.MapTypeId.road,
	// mapTypeId: Microsoft.Maps.MapTypeId.aerial,
	zoom: 13
}
var map = new Microsoft.Maps.Map(document.getElementById("mapDiv"), options);
var dataLayer = new Microsoft.Maps.EntityCollection();
var map_credentials = null;
var infoboxLayer = new Microsoft.Maps.EntityCollection();
var infobox = new Microsoft.Maps.Infobox(new Microsoft.Maps.Location(0, 0), { visible: false, offset: new Microsoft.Maps.Point(0, 20) });

$(document).ready(function() {
	map.getCredentials(MakeGeocodeRequest);
	map.entities.push(dataLayer);
	map.entities.push(infoboxLayer);
	infoboxLayer.push(infobox);
	Microsoft.Maps.Events.addHandler(map, 'viewchange', MakeGeocodeRequest);
	
	$('input[type=checkbox]').change(MakeGeocodeRequest);
});

function MakeGeocodeRequest(credentials) {
	if (credentials == null) { credentials = map_credentials; } else { map_credentials = credentials; }
	var map_rect = map.getBounds();
	dataLayer.clear();
	
	var plot_types = new Array();
	var i = 0;
	if($('#commercial').is(':checked')) {
		plot_types[i] = $('#commercial').val();
		i = i+1;
	}
	if($('#residential').is(':checked')) {
		plot_types[i] = $('#residential').val();
		i = i+1;
	}
	if($('#vacant').is(':checked')) {
		plot_types[i] = $('#vacant').val();
		i = i+1;
	}
	if($('#miscellaneous').is(':checked')) {
		plot_types[i] = $('#miscellaneous').val();
	}
	
	$.ajax({
		type: 'GET',
		url:"/data",
		data:{north:map_rect.getNorth(), south:map_rect.getSouth(), east:map_rect.getEast(), west:map_rect.getWest(), the_types:plot_types},
		success: function(the_data){
			$.each(the_data, function(index, value) {
				if(value.latitude!='' && value.latitude!=null && value.longitude!='' && value.longitude!=null) {
					GeocodeCallback(null,value);
				} else {
					if(value.street!='') {
						$.ajax({
							url:"http://ecn.dev.virtualearth.net/REST/v1/Locations/",
							dataType: "jsonp",
							data:{key:credentials,query:value.street+', '+value.city+', '+value.state+' '+value.zip,output:'json'},
							jsonp:"jsonp",
							success: function(result){
								GeocodeCallback(result,value);
							}
						});
					} else {
						$.ajax({
							type: 'POST',
							url: '/hide_plot/'+value.id,
						});
					}
				}
			});
		}
	});
}

function displayInfobox(e) {
	infobox.setLocation(e.target.getLocation());
	infobox.setOptions({ visible: true, title: e.target.Title, description: e.target.Description });
}

function hideInfobox(e) {
	pinInfobox.setOptions({ visible: false });
}

function GeocodeCallback(result, addy) {
	if(addy.longitude!='' && addy.longitude!=null && addy.latitude!='' && addy.latitude!=null) {
		var loc = new Microsoft.Maps.Location(addy.latitude, addy.longitude);
		var this_pin = new Microsoft.Maps.Pushpin(loc);
		description_text = 'Parcel ID: '+addy.parcel_id+'<br />Neighborhood: '+addy.neighborhood+'<br />Ward: '+addy.ward;
		if(addy.description!='') {
			description_text = description_text+'<br />Description: '+addy.description;
		}
		if(addy.usage!='') {
			description_text = description_text+'<br />Usage: '+addy.usage;
		}
		if(addy.lot_square_feet!='') {
			description_text = description_text+'<br />Square Feet: '+Math.round(addy.lot_square_feet);
		}
		this_pin.Description = description_text;
		this_pin.Title = addy.street;
		Microsoft.Maps.Events.addHandler(this_pin, 'click', displayInfobox);
		dataLayer.push(this_pin);
	} else {
		if(typeof result.resourceSets[0] == 'undefined') {
			// $.ajax({
			// 	type: 'POST',
			// 	url: '/hide_plot/'+addy.id,
			// });
		} else {
			var loc = new Microsoft.Maps.Location(result.resourceSets[0].resources[0].geocodePoints[0].coordinates[0], result.resourceSets[0].resources[0].geocodePoints[0].coordinates[1]);
			var this_pin = new Microsoft.Maps.Pushpin(loc);
			description_text = 'Parcel ID: '+addy.parcel_id+'<br />Neighborhood: '+addy.neighborhood+'<br />Ward: '+addy.ward;
			if(addy.description!='') {
				description_text = description_text+'<br />Description: '+addy.description;
			}
			if(addy.usage!='') {
				description_text = description_text+'<br />Usage: '+addy.usage;
			}
			if(addy.lot_square_feet!='') {
				description_text = description_text+'<br />Square Feet: '+Math.round(addy.lot_square_feet);
			}
			this_pin.Description = description_text;
			this_pin.Title = addy.street;
			Microsoft.Maps.Events.addHandler(this_pin, 'click', displayInfobox);
			dataLayer.push(this_pin);
			$.ajax({
				type: 'POST',
				url: '/update_address/'+addy.id,
				data: {
					'latitude': result.resourceSets[0].resources[0].geocodePoints[0].coordinates[0],
					'longitude': result.resourceSets[0].resources[0].geocodePoints[0].coordinates[1]
				}
			});
		}
	}
}
