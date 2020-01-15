{**
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
*}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-truck"></i> Moova
    </div> 
    <div>
 
        {if !$trackingNumber}
        <a class="btn btn-default" id='moova_create_shipping'>
            <i class="icon-envelope"></i> {l s='Create shipping' mod='moova'}
        </a>
        {/if} {if $trackingNumber} {if sizeof($status) == 0}
        <a class="btn btn-default" id='moova_inform_ready'>
            <i class="icon-truck"></i> Inform is Ready
        </a>
        {/if}
        <a class="btn btn-default _blank" id='moova_get_label' target="_blank">
            <i class="icon-file-text"></i> Get Label
        </a>
        {/if}
        <hr>
        <!-- Shipping block -->
        <div class="table-responsive well hidden-print">
            <table class="table" id="shipping_table">
                <thead>
                    <tr>
                        <th>
                            <span class="title_box ">Status</span>
                        </th>

                        <th>
                            <span class="title_box ">Date</span>
                        </th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    {foreach $status as $state}
                    <tr>
                        <td>{$state['status']|escape:'htmlall':'UTF-8'}</td>
                         <td>{$state['date']|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    {/foreach}

                </tbody>
            </table>
        </div>
        <hr>

    </div>
</div>