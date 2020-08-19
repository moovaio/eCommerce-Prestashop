/**
 * 2007-2020 PrestaShop
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
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

$(document).ready(function () {
  function autocomplete(request, response) {
    $.ajax({
      type: "GET",
      data: { query: request.term },
      url: "https://api-dev.moova.io/autocomplete",
      dataType: "json",
      success: function (res) {
        var limitedAutocomplete = res.slice(0, 5);
        var res = $.map(limitedAutocomplete, function (value, key) {
          return {
            label: value.main_text + ", " + value.secondary_text,
            value: value.place_id,
          };
        });

        response(res);
      },
    });
  }

  function showSpecialPricing() {
    $("#MOOVA_FIXED_PRICE").closest(".form-group").hide();
    $("#MOOVA_MIN_PRICE").closest(".form-group").hide();
    $("#MOOVA_MAX_PRICE").closest(".form-group").hide();

    var specialPrice = $("#SPECIAL_PRICING_OPTIONS").val();
    if (specialPrice == "fixed") {
      $("#MOOVA_FIXED_PRICE").closest(".form-group").show();
    } else if (specialPrice == "range") {
      $("#MOOVA_MIN_PRICE").closest(".form-group").show();
      $("#MOOVA_MAX_PRICE").closest(".form-group").show();
    }
  }

  $("#MOOVA_ORIGIN_ADDRESS").autocomplete({
    delay: 50,
    minChars: 3,
    autoFill: false,
    source: function (request, response) {
      return autocomplete(request, response);
    },
    select: function (event, ui) {
      $(this).val(ui.item.label);
      $("#MOOVA_ORIGIN_GOOGLE_PLACE_ID").val(ui.item.value);
      return false;
    },
  });

  $("#MOOVA_ORIGIN_GOOGLE_PLACE_ID").closest(".form-group").hide();
  $("#SPECIAL_PRICING_OPTIONS").change(showSpecialPricing);
  showSpecialPricing();
});
