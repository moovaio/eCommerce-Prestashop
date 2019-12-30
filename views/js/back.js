/**
 * 2007-2019 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

$(document).ready(function() {
  function moovaCreateShipping() {
    $.ajax({
      type: "GET",
      url: "/modules/moova/Api/ajax.php",
      headers: { "cache-control": "no-cache" },
      async: true,
      cache: false,

      data: "action=processOrder&order=" + id_order,
      success: function(data) {
        if (!data) {
          alert("Error creating shipment");
        }
        window.location.reload(true);
      }
    });
  }

  function moovaGetLabel() {
    $.ajax({
      type: "GET",
      url: "/modules/moova/Api/ajax.php",
      headers: { "cache-control": "no-cache" },
      async: true,
      cache: false,
      dataType: "json",
      data: {
        action: "getLabel",
        trackingNumber: Moova.trackingNumber
      },
      success: function(data) {
        if (!data) {
          alert("Error getting label");
        }
        window.open(data.label, "_blank");
      }
    });
  }

  function moovaInformReady() {
    $.ajax({
      type: "POST",
      url:  "/modules/moova/Api/ajax.php",
      headers: { "cache-control": "no-cache" },
      async: true,
      cache: false,
      data: {
        action: "updateOrderStatus",
        trackingNumber: Moova.trackingNumber,
        status: "READY",
        reason: ""
      },
      success: function(data) {
        if (!data) {
          alert("Error changing status. Please change it manually in moova.io");
        }
        //window.location.reload(true);
      }
    });
  }

  $("#moova_create_shipping").click(function() {
    $("#moova_create_shipping").attr("disabled", true);
    moovaCreateShipping();
  });

  $("#moova_inform_ready").click(function() {
    moovaInformReady();
  });

  $("#moova_get_label").click(function() {
    moovaGetLabel();
  });
});
