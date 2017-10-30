<?php echo $header; ?>
<?php echo $column_left;?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
            <?php if ($export_file) : ?>
                <button type="button" id="export" data-toggle="tooltip" title="<?php echo $text_button_export; ?>" class="btn btn-success"><i class="fa fa-download"></i></button>
            <?php endif; ?> 
                <button type="button" id="icml" data-toggle="tooltip" title="<?php echo $text_button_catalog; ?>" class="btn btn-success"><i class="fa fa-file-text-o"></i></button>
                <button type="submit" form="form-retailcrm" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) : ?>
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
        </div>
        <?php endif; ?>
        <?php if (isset($saved_settings['retailcrm_url'])): ?>
        <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $text_notice; ?>
            <a href="<?php echo $saved_settings['retailcrm_url']; ?>/admin/settings#t-main"><?php echo $saved_settings['retailcrm_url']; ?>/admin/settings#t-main</a>
        </div>

        <?php endif; ?>

        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-retailcrm">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $general_tab_text; ?></a></li>
                        <?php if (isset($saved_settings['retailcrm_apikey']) && $saved_settings['retailcrm_apikey'] != '' && isset($saved_settings['retailcrm_url']) && $saved_settings['retailcrm_url'] != ''): ?>
                        <li><a href="#tab-references" data-toggle="tab"><?php echo $references_tab_text; ?></a></li>
                        <li><a href="#tab-collector" data-toggle="tab"><?php echo $collector_tab_text; ?></a></li>
                        <?php if ($saved_settings['retailcrm_apiversion'] == 'v5') : ?>
                            <li><a href="#tab-custom_fields" data-toggle="tab"><?php echo $custom_fields_tab_text; ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-general">
                            <input type="hidden" name="retailcrm_status" value="1">

                            <h3><?php echo $retailcrm_base_settings; ?></h3>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_url"><?php echo $retailcrm_apiversion; ?></label><br>
                                <select name="retailcrm_apiversion">
                                    <?php foreach($api_versions as $version) : ?>
                                    <option value="<?php echo $version; ?>" <?php if (isset($saved_settings['retailcrm_apiversion']) && $saved_settings['retailcrm_apiversion'] == $version) echo "selected='selected'"; elseif (!isset($saved_settings['retailcrm_apiversion']) && $default_apiversion == $version) echo "selected='selected'"; ?>><?php echo $version; ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_url"><?php echo $retailcrm_url; ?></label><br>
                                <input id="retailcrm_url" type="text" name="retailcrm_url" value="<?php if (isset($saved_settings['retailcrm_url'])): echo $saved_settings['retailcrm_url']; endif; ?>">
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_apikey"><?php echo $retailcrm_apikey; ?></label><br>
                                <input id="retailcrm_apikey" type="text" name="retailcrm_apikey" value="<?php if (isset($saved_settings['retailcrm_apikey'])): echo $saved_settings['retailcrm_apikey']; endif;?>">
                            </div>

                            <h3><?php echo $retailcrm_countries_settings; ?></h3>
                            <div class="retailcrm_unit">
                                <div class="well well-sm" style="height: 150px; overflow: auto; width: 30%;">
                                <?php foreach($countries as $country) : ?>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="<?php echo 'retailcrm_country[]'; ?>" value="<?php echo $country['country_id']; ?>" <?php if(isset($saved_settings['retailcrm_country']) && in_array($country['country_id'], $saved_settings['retailcrm_country'])): echo 'checked'; endif;?>>
                                    <?php echo $country['name']; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        
                            <?php if (isset($saved_settings['retailcrm_apikey']) && $saved_settings['retailcrm_apikey'] != '' && isset($saved_settings['retailcrm_url']) && $saved_settings['retailcrm_url'] != ''): ?>

                            <?php if (!empty($retailcrm_errors)) : ?>
                            <?php foreach($retailcrm_errors as $retailcrm_error): ?>
                            <div class="warning"><?php echo $retailcrm_error ?></div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <h3><?php echo $retailcrm_upload_order; ?></h3>
                            <div class="retailcrm_unit">
                                <label><?php echo $text_button_export_order; ?> № </label><input type="text" name="order_id">
                                <button type="button" id="export_order" data-toggle="tooltip" title="<?php echo $text_button_export_order; ?>" class="btn btn-success"><i class="fa fa-download"></i></button>
                            </div>
                        </div>

                        <div class="tab-pane" id="tab-references">
                            <h3><?php echo $retailcrm_dict_settings; ?></h3>

                            <h4><?php echo $retailcrm_dict_delivery; ?></h4>
                            <?php foreach($delivery['opencart'] as $value): ?>
                            
                                <div class="pm"><?php echo $value['title'].':'; ?></div>
                                <?php unset($value['title']); ?>
                                <?php foreach ($value as $key => $val): ?>
                                    <div class="retailcrm_unit">
                                    <select id="retailcrm_delivery_<?php echo $val['code']; ?>" name="retailcrm_delivery[<?php echo $val['code']; ?>]" >
                                    <?php foreach ($delivery['retailcrm'] as $k => $v): ?>
                                    <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_delivery'][$key]) && $v['code'] == $saved_settings['retailcrm_delivery'][$key]):?>selected="selected"<?php endif;?>>
                                    <?php echo $v['name'];?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                                    <label for="retailcrm_pm_<?php echo $val['code']; ?>"><?php echo $val['title']; ?></label>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <h4><?php echo $retailcrm_dict_status; ?></h4>
                            <?php foreach ($statuses['opencart'] as $status): ?>
                            <?php $uid = $status['order_status_id']?>
                            <div class="retailcrm_unit">
                                <select id="retailcrm_status_<?php echo $uid; ?>" name="retailcrm_status[<?php echo $uid; ?>]" >
                                    <?php foreach ($statuses['retailcrm'] as $k => $v): ?>
                                    <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_status'][$uid]) && $v['code'] == $saved_settings['retailcrm_status'][$uid]):?>selected="selected"<?php endif;?>>
                                    <?php echo $v['name'];?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="retailcrm_status_<?php echo $status['order_status_id']; ?>"><?php echo $status['name']; ?></label>
                            </div>
                            <?php endforeach; ?>

                            <h4><?php echo $retailcrm_dict_payment; ?></h4>
                            <?php foreach ($payments['opencart'] as $key => $value): ?>
                            <div class="retailcrm_unit">
                                <select id="retailcrm_payment_<?php echo $key; ?>" name="retailcrm_payment[<?php echo $key; ?>]" >
                                    <?php foreach ($payments['retailcrm'] as $k => $v): ?>
                                    <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_payment'][$key]) && $v['code'] == $saved_settings['retailcrm_payment'][$key]):?>selected="selected"<?php endif;?>>
                                    <?php echo $v['name'];?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="retailcrm_payment_<?php echo $key; ?>"><?php echo $value; ?></label>
                            </div>
                            <?php endforeach; ?>
                            <h4><?php echo $retailcrm_dict_default; ?></h4>
                            <div class="retailcrm_unit">
                                <select id="retailcrm_default_payment" name="retailcrm_default_payment" >
                                    <?php foreach ($payments['opencart'] as $k => $v): ?>
                                    <option value="<?php echo $k;?>" <?php if(isset($saved_settings['retailcrm_default_payment']) && $k == $saved_settings['retailcrm_default_payment']):?>selected="selected"<?php endif;?>>
                                    <?php echo $v;?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="retailcrm_default_payment"><?php echo $text_payment; ?></label>
                            </div>
                            <div class="retailcrm_unit">
                                <select id="retailcrm_default_shipping" name="retailcrm_default_shipping" >
                                    <?php foreach ($delivery['opencart'] as $key => $value): ?>
                                    <optgroup label="<?php echo $value['title']; ?>">
                                        <?php unset($value['title']); ?>
                                        <?php foreach ($value as $v): ?>
                                        <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_default_shipping']) && $v['code'] == $saved_settings['retailcrm_default_shipping']):?>selected="selected"<?php endif;?>>
                                        <?php echo $v['title'];?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <label for="retailcrm_default_payment"><?php echo $text_shipping; ?></label>
                            </div>
                            <?php endif; ?>

                            <?php endif; ?>
                        </div>

                        <div class="tab-pane" id="tab-collector">
                            <h3><?php echo $daemon_collector; ?></h3>
                            <div class="retailcrm_unit">
                            <label for="retailcrm_collector_active" class="col-md-4"><?php echo $text_collector_activity; ?></label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector_active" value="1" <?php if (isset($saved_settings['retailcrm_collector_active']) && 
                                    $saved_settings['retailcrm_collector_active'] == 1) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_yes; ?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector_active" value="0" <?php if (!isset($saved_settings['retailcrm_collector_active']) || 
                                    $saved_settings['retailcrm_collector_active'] == 0) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_no; ?>
                                </label>
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_collector" class="col-md-4"><?php echo $collector_site_key; ?></label>
                                <input id="retailcrm_collector_site_key" type="text" name="retailcrm_collector[site_key]" value="<?php if (isset($saved_settings['retailcrm_collector']['site_key'])): echo $saved_settings['retailcrm_collector']['site_key']; endif; ?>">
                            </div>
                            <div class="retailcrm_unit">
                            <label for="retailcrm_collector" class="col-md-4"><?php echo $text_collector_form_capture; ?></label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector[form_capture]" value="1" <?php if (isset($saved_settings['retailcrm_collector']['form_capture']) && 
                                    $saved_settings['retailcrm_collector']['form_capture'] == 1) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_yes; ?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector[form_capture]" value="0" <?php if (!isset($saved_settings['retailcrm_collector']['form_capture']) || 
                                    $saved_settings['retailcrm_collector']['form_capture'] == 0) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_no; ?>
                                </label>
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_collector" class="col-md-4"><?php echo $text_collector_period; ?></label>
                                <input id="retailcrm_collector_period" type="text" name="retailcrm_collector[period]" value="<?php if (isset($saved_settings['retailcrm_collector']['period'])): echo $saved_settings['retailcrm_collector']['period']; endif; ?>">
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_collector" class="col-md-4"><?php echo $text_label_promo; ?></label>
                                <input id="retailcrm_collector[]" type="text" name="retailcrm_collector[label_promo]" value="<?php if (isset($saved_settings['retailcrm_collector']['label_promo'])): echo $saved_settings['retailcrm_collector']['label_promo']; endif; ?>">
                            </div>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_collector" class="col-md-4"><?php echo $text_label_send; ?></label>
                                <input id="retailcrm_collector_label_send" type="text" name="retailcrm_collector[label_send]" value="<?php if (isset($saved_settings['retailcrm_collector']['label_send'])): echo $saved_settings['retailcrm_collector']['label_send']; endif; ?>">
                            </div>
                            <div class="retailcrm_unit">
                            <label for="retailcrm_collector" class="col-md-4"><?php echo $collector_custom_text; ?></label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector[custom_form]" value="1" <?php if (isset($saved_settings['retailcrm_collector']['custom_form']) && 
                                    $saved_settings['retailcrm_collector']['custom_form'] == 1) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_yes; ?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="retailcrm_collector[custom_form]" value="0" <?php if (!isset($saved_settings['retailcrm_collector']['custom_form']) || 
                                    $saved_settings['retailcrm_collector']['custom_form'] == 0) :
                                    echo 'checked'; endif; ?>>
                                    <?php echo $text_no; ?>
                                </label>
                            </div>
                            <?php foreach ($collectorFields as $field => $label) : ?>
                            <div class="retailcrm_unit">
                                <label for="retailcrm_collector" class="col-md-4"><?php echo $label; ?></label>
                                <div class="col-md-8">
                                    <input id="retailcrm_collector" type="text" name="retailcrm_collector[custom][<?php echo $field; ?>]" value="<?php if (isset($saved_settings['retailcrm_collector']['custom'][$field])) : echo $saved_settings['retailcrm_collector']['custom'][$field]; endif; ?>">
                                    <input type="checkbox" name="retailcrm_collector[require][<?php echo $field; ?>_require]" value="1" <?php if (isset($saved_settings['retailcrm_collector']['require'][$field.'_require'])) : echo 'checked'; endif;?>>
                                    <label for="retailcrm_collector"><?php echo $text_require; ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($saved_settings['retailcrm_apiversion']) && $saved_settings['retailcrm_apiversion'] == 'v5' && isset($customFields)) : ?>
                            <div class="tab-pane" id="tab-custom_fields">
                                <h3><?php echo $retailcrm_dict_custom_fields; ?></h3>
                                <?php if ($customFields['retailcrm'] && $customFields['opencart']) : ?>
                                    <div class="retailcrm_unit">
                                        <label for="retailcrm_custom_field_active"><?php echo $text_custom_field_activity; ?></label>
                                        <label class="radio-inline">
                                            <input type="radio" name="retailcrm_custom_field_active" value="1" <?php if (isset($saved_settings['retailcrm_custom_field_active']) && 
                                            $saved_settings['retailcrm_custom_field_active'] == 1) :
                                            echo 'checked'; endif; ?>>
                                            <?php echo $text_yes; ?>
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="retailcrm_custom_field_active" value="0" <?php if (!isset($saved_settings['retailcrm_custom_field_active']) || 
                                            $saved_settings['retailcrm_custom_field_active'] == 0) :
                                            echo 'checked'; endif; ?>>
                                            <?php echo $text_no; ?>
                                        </label>
                                    </div>
                                    <h4><?php echo $text_customers_custom_fields; ?></h4>
                                    <?php foreach ($customFields['opencart'] as $customField) : ?>
                                    <?php $fid = 'c_' . $customField['custom_field_id'] ?>
                                        <div class="retailcrm_unit">
                                            <select id="retailcrm_custom_field_<?php echo $fid; ?>" name="retailcrm_custom_field[<?php echo $fid; ?>]" >
                                                <?php foreach ($customFields['retailcrm']['customers'] as $v): ?>
                                                <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_custom_field'][$fid]) && $v['code'] == $saved_settings['retailcrm_custom_field'][$fid]):?>selected="selected"<?php endif;?>>
                                                <?php echo $v['name'];?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="retailcrm_custom_field_<?php echo $fid; ?>"><?php echo $customField['name']; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                    <h4><?php echo $text_orders_custom_fields; ?></h4>
                                    <?php foreach ($customFields['opencart'] as $customField) : ?>
                                    <?php $fid = 'o_' . $customField['custom_field_id'] ?>
                                        <div class="retailcrm_unit">
                                            <select id="retailcrm_custom_field_<?php echo $fid; ?>" name="retailcrm_custom_field[<?php echo $fid; ?>]" >
                                                <?php foreach ($customFields['retailcrm']['orders'] as $v): ?>
                                                <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_custom_field'][$fid]) && $v['code'] == $saved_settings['retailcrm_custom_field'][$fid]):?>selected="selected"<?php endif;?>>
                                                <?php echo $v['name'];?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="retailcrm_custom_field_<?php echo $fid; ?>"><?php echo $customField['name']; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif (!$customFields['retailcrm'] && !$customFields['opencart']) : ?>
                                    <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <?php echo $text_error_custom_field; ?>
                                    </div>
                                <?php elseif (!$customFields['retailcrm']) : ?>
                                    <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <?php echo $text_error_cf_retailcrm; ?>
                                    </div>
                                <?php elseif (!$customFields['opencart']) : ?>
                                    <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <?php echo $text_error_cf_opencart; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php echo $footer; ?>

<script type="text/javascript">
    var token = '<?php echo $token; ?>';
    $('#icml').on('click', function() {
        $.ajax({
            url: '<?php echo $catalog; ?>' + 'admin/index.php?route=extension/module/retailcrm/icml&token=' + token,
            beforeSend: function() {
                $('#icml').button('loading');
            },
            complete: function() {
                $('.alert-success').remove();
                $('#content > .container-fluid').prepend('<div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $text_success_catalog; ?></div>');
                $('#icml').button('reset');
            },
            error: function(){
                alert('error');
            }
        });
    });

    $('#export').on('click', function() {
        $.ajax({
            url: '<?php echo $catalog; ?>' + 'admin/index.php?route=extension/module/retailcrm/export&token=' + token,
            beforeSend: function() {
                $('#export').button('loading');
            },
            complete: function() {
                $('.alert-success').remove();
                $('#content > .container-fluid').prepend('<div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $text_success_export; ?></div>');
                $('#export').button('reset');
            },
            error: function(){
                alert('error');
            }
        });
    });

    $('#export_order').on('click', function() {
        var order_id = $('input[name=\'order_id\']').val();
        if (order_id && order_id > 0) {
            $.ajax({
                url: '<?php echo $catalog; ?>' + 'admin/index.php?route=extension/module/retailcrm/exportOrder&token=' + token + '&order_id=' + order_id,
                beforeSend: function() {
                    $('#export_order').button('loading');
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                },
                success: function(data, textStatus, jqXHR) {
                    response = JSON.parse(jqXHR['responseText']);
                    if (response['status_code'] == '400') {
                        $('.alert-danger').remove();
                        $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i><?php echo $text_error_order; ?>' + response['error_msg'] + '</div>');
                        $('#export_order').button('reset');
                    } else {
                        $('.alert-success').remove();
                        $('#content > .container-fluid').prepend('<div class="alert alert-success"><i class="fa fa-exclamation-circle"></i><?php echo $text_success_export_order; ?></div>');
                        $('#export_order').button('reset');
                        $('input[name=\'order_id\']').val('');
                    }
                }
            });
        } else {
            $('.alert-danger').remove();
            $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $text_error_order_id; ?></div>');
            $('#export_order').button('reset');
        }
    });
</script>

