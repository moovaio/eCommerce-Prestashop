{*
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

	{if $message['status']=='error' }
	<div class="alert alert-danger" role="alert">
	{l s='Please complete the required field ' mod='moova'} {$message['field']|escape:'htmlall':'UTF-8'}
	<br />
	</div>
	{/if}

	{if $message['status']=='success'}
	<div class="alert alert-success" role="alert">
	{l s='Everything was saved correctly' mod='moova'}<br />
	</div>
	{/if}



	<div class="panel">
		<h3><i class="icon icon-truck"></i> {l s='Moova Configuration' mod='moova'}</h3>
		<img src="{$module_dir|escape:'html':'UTF-8'}/logo.png" id="payment-logo" class="pull-right"  style='    height: 90px;' />
		<p>
			<strong>{l s='Moova shipping' mod='moova'}</strong><br />
			{l s='This Moova extension allows you to display real-time shipping quotes to your customers based on their cart details and shipping address' mod='moova'}<br />
			{l s='We produce shipping labels that can be downloaded both from this extension or on the website.' mod='moova'}<br />
			{l s='		Furthermore, we give you a real-time tracking URL so you and your client can follow your shipping at every time.' mod='moova'}
		</p>
		<br /> 
	</div>

	<div class="panel">
		<h3><i class="icon icon-tags"></i> {l s='Documentation' mod='moova'}</h3>
		<p>
			&raquo; {l s='You can get a PDF documentation to configure this module' mod='moova'} :
			<ul>
				<li><a href="https://moova.io/wp-content/uploads/2020/01/moova_prestashop_1.6-1.7.pdf" target="_blank">{l s='English' mod='moova'}</a></li>
			</ul>
		</p>
	</div>
</div>