<table class="form-table">
    <tr class="form-field">
        <td width="200px"><label><?php _e('API Key', 'formidable') ?></label></td>
    	<td>
            <input type="text" class="frm_select_box frm_long_input" value="<?php echo esc_attr($api_key) ?>" style="background:transparent;border:none;text-align:left;box-shadow:none;"/>
    	</td>
    </tr>
    <tr>
        <td colspan="2">
            <?php if ( !is_plugin_active('json-rest-api/plugin.php') ){ ?>
                You are missing the <a href="http://wordpress.org/plugins/json-rest-api/">JSON Rest Api</a> plugin. 
            <?php } ?>
        </td>
    </tr>
    
</table>

