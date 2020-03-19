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
<div class="panel" id='moova_wrapper' data-token="{$token|escape:'htmlall':'UTF-8'}">
    <div class="panel-heading">
        <i class="icon-truck"></i> Moova 
    </div> 
    <div>

        {if !$trackingNumber}
        <a class="btn btn-default" id='moova_create_shipping'>
            <i class="icon-envelope"></i> {l s='Create shipping' mod='moova'}
        </a>

        {/if} {if $trackingNumber} {if sizeof($status) == 1}
        <a class="btn btn-default" id='moova_inform_ready'>
            <i class="icon-truck"></i> {l s='Inform is Ready' mod='moova'}
        </a>
        {/if}
        <a class="btn btn-default _blank" id='moova_get_label' target="_blank">
            <i class="icon-file-text"></i> {l s='Get label' mod='moova'}
        </a>
        {/if}
        <hr>
        <!-- Shipping block -->
        <div class="table-responsive well hidden-print">
            <table class="table" id="shipping_table">
                <thead>
                    <tr>
                        <th>
                            <span class="title_box ">{l s='Status' mod='moova'}</span>
                        </th>

                        <th>
                            <span class="title_box ">{l s='Date' mod='moova'}</span>
                        </th>
                        <th></th>
                    </tr>
                </thead> 
                <tbody> 
                    {foreach $status as $state}
                    <tr> 
                        <td> {l s=$state->status mod='moova'}</td>
                         <td>{$state->createdAt|escape:'htmlall':'UTF-8'}</td> 
                    </tr>
                    {/foreach}

                </tbody> 
            </table>
        </div>
        <div style="display: none;"> 
                <p>{l s="INCIDENCE" mod='moova'}</p>
                <p>{l s="DRAFT" mod='moova'}</p>
                <p>{l s="CONFIRMED" mod='moova'}</p> 
                <p>{l s="WAITING" mod='moova'}</p> 
                <p>{l s="READY" mod='moova'}</p> 
                <p>{l s="CANCELED" mod='moova'}</p> 
                <p>{l s="PICKEDUP" mod='moova'}</p>
                <p>{l s="TOBERETURNED" mod='moova'}</p> 
                <p>{l s="INTRANSIT" mod='moova'}</p> 
                <p>{l s="RETURNED" mod='moova'}</p> 
                <p>{l s="BLOCKED" mod='moova'}</p>
        </div>
        <hr>
    </div>
</div>