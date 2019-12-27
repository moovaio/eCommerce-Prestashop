<div class="panel">
    <div class="panel-heading">
        <i class="icon-truck"></i> Moova
    </div>
    <div>

        {if !$trackingNumber}
        <a class="btn btn-default" id='moova_create_shipping'>
            <i class="icon-envelope"></i> {l s='Create shipping' mod='Moova'}
        </a>
        {/if} {if $trackingNumber} {if $status ==='READY'}
        <a class="btn btn-default" id='moova_inform_ready'>
            <i class="icon-truck"></i> Inform is Ready
        </a>
        {/if}
        <a class="btn btn-default _blank" id='moova_get_label' target="_blank">
            <i class="icon-truck"></i> Get Label
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
                    {foreach $status as $pes}
                    <tr>
                        <td>{$pes['status']}</td>
                         <td>{$pes['date']}</td>
                    </tr>
                    {/foreach}

                </tbody>
            </table>
        </div>
        <hr>

    </div>
</div>