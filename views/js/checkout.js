/**
 * 2007-2021·PrestaShop Moova
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Moova SA <help@moova.io>
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
"use strict";

//$('[name="address2"]').closest('.form-group').hide();
var googleAutocomplete = document.createElement('script');
googleAutocomplete.setAttribute('src',`https://maps.googleapis.com/maps/api/js?key=${moova.key}&libraries=places&callback=initMap`);
document.head.appendChild(googleAutocomplete);

function initMap() {
	function init() {
		$("<div id='moova-map' style='height: 400px;' ></div>").insertBefore("#use_same_address");
		const map = new google.maps.Map(document.getElementById("moova-map"), {
			center: {
				lat: -33.8688,
				lng: 151.2195
			},
			zoom: 13,
		});

		const marker = new google.maps.Marker({
			map,
			anchorPoint: new google.maps.Point(0, -29),
    });
    
    let autocomplete = setAutocomplete(map);
		let place = new Place(map, marker, autocomplete);
		autocomplete.addListener("place_changed", () => {
				place.changed()
			});

		google.maps.event.addListener(map, 'click', function(event) {
			placeMarker(event.latLng, marker);
		});
	}

	function setAutocomplete(map) {
		const input = document.getElementsByName("address1")[0];
		const autocomplete = new google.maps.places.Autocomplete(input);
		autocomplete.bindTo("bounds", map);
		// Set the data fields to return when the user selects a place.
		autocomplete.setFields(["address_components", "geometry", "icon", "name"]);
		return autocomplete;
	}

	function placeMarker(location, marker) {
		//$('[name="address2"]').val(JSON.stringify({ lat: location.lat(), lng: location.lng() }));
		marker.setPosition(location); 
	}

	class Place {
		constructor(map, marker, autocomplete) {
			this.marker = marker;
			this.map = map;
			this.autocomplete = autocomplete
		}

		changed() {
			this.marker.setVisible(false);
			let place = this.autocomplete.getPlace();
			if (!place.geometry) {
				window.alert("No details available for input: '" + place.name + "'");
				return;
			}

			// If the place has a geometry, then present it on a map.
			if (place.geometry.viewport) {
				this.map.fitBounds(place.geometry.viewport);
			} else {
				this.map.setCenter(place.geometry.location);
				this.map.setZoom(17); // Why 17? Because it looks good.
			}

			this.marker.setPosition(place.geometry.location);
			this.marker.setVisible(true);
			/*$('[name="address2"]').val(JSON.stringify({
				lat: place.geometry.location.lat(),
				lng: place.geometry.location.lng()
			}));*/

			let postalCode = place.address_components.find(element => element.types[0] === 'postal_code')
			let city = place.address_components.find(element => element.types[0] === "administrative_area_level_1")
			let country = place.address_components.find(element => element.types[0] === 'country')

			postalCode = postalCode ? postalCode.long_name : '';
			$('[name="postcode"]').val(postalCode);
			$('[name="city"]').val(city.long_name);
		}
	}

	init();
}
 