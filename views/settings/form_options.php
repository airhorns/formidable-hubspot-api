<?php if ( $hooks ) { ?>
<br/>
<div id="postcustomstuff">
<table class="form-table" id="list-table">
    <thead>
        <tr>
            <th class="left"><?php _e('Trigger', 'frmapi') ?></th>
            <th><?php _e('URL', 'frmapi') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $hooks as $hook ) { ?>
    <tr>
        <td class="left"><?php echo $hook->post_title ?></td>
        <td><?php echo $hook->post_excerpt ?></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<p><a class="button-secondary" href="<?php echo admin_url('post-new.php?post_type=frm_api&form_id='. (int) $values['id'] ) ?>"><?php _e('Add') ?></a></p>
</div>
<?php } else { ?>
<p>You don't have any webhooks set up yet.<br/>
You can add one <a href="<?php echo admin_url('post-new.php?post_type=frm_api') ?>">here</a></p>
<?php } ?>