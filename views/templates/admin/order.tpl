<div class="panel">
    <div class="panel-heading">
        <i class="icon-truck"></i> Moova
    </div> 
    <div class="well hidden-print">
    
        {if !$trackingNumber}
            <a class="btn btn-default" id='moova_create_shipping'>
                <i class="icon-envelope"></i> {l s='Create shipping' mod='Moova'}
            </a>
        {/if}
         {if $trackingNumber}
            {if $status ==='READY'}
            <a class="btn btn-default" id='moova_inform_ready'>
                <i class="icon-truck"></i> Inform is Ready
            </a> 
            {/if}
            <a class="btn btn-default _blank" id='moova_get_label' target="_blank">
                <i class="icon-truck"></i> Get Label
            </a>
        {/if}
    </div>
</div>
