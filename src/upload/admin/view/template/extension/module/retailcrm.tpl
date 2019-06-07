<?php echo $header; ?><?php echo $column_left;?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <?php if ($export_file) : ?>
        <button type="button" id="export" data-toggle="tooltip" title="<?php echo $text_button_export; ?>" class="btn btn-success"><i class="fa fa-download"></i></button>
        <?php endif; ?>
        <button type="button" id="icml" data-toggle="tooltip" title="<?php echo $text_button_catalog; ?>" class="btn btn-success"><i class="fa fa-file-text-o"></i></button>
        <button type="submit" form="form-retailcrm" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
      </div>
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
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-retailcrm" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $general_tab_text; ?></a></li>
            <?php if (isset($saved_settings['retailcrm_apikey']) && $saved_settings['retailcrm_apikey'] != '' && isset($saved_settings['retailcrm_url']) && $saved_settings['retailcrm_url'] != ''): ?>
            <li><a href="#tab-references" data-toggle="tab"><?php echo $references_tab_text; ?></a></li>
            <li><a href="#tab-collector" data-toggle="tab"><?php echo $collector_tab_text; ?></a></li>
            <?php if ($saved_settings['retailcrm_apiversion'] == 'v5') : ?>
            <li><a href="#tab-custom_fields" data-toggle="tab"><?php echo $custom_fields_tab_text; ?></a></li>
            <?php endif; ?>
            <li><a href="#tab-logs" data-toggle="tab"><?php echo $logs_tab_text; ?></a></li>
            <?php endif; ?>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-general">
              <input type="hidden" name="retailcrm_status" value="1">
              <fieldset>
                <legend><?php echo $retailcrm_base_settings; ?></legend>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label" for="retailcrm_url"><?php echo $retailcrm_apiversion; ?></label>
                  <div class="col-lg-1 col-md-2 col-sm-2">
                    <select name="retailcrm_apiversion" class="form-control">
                      <?php foreach($api_versions as $version) : ?>
                      <option value="<?php echo $version; ?>" <?php if (isset($saved_settings['retailcrm_apiversion']) && $saved_settings['retailcrm_apiversion'] == $version) echo "selected='selected'"; elseif (!isset($saved_settings['retailcrm_apiversion']) && $default_apiversion == $version) echo "selected='selected'"; ?>><?php echo $version; ?></option>
                      <?php endforeach ?>
                    </select>
                  </div>
                </div>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label" for="retailcrm_url"><?php echo $retailcrm_url; ?></label>
                  <div class="col-lg-4 col-md-6 col-sm-10">
                    <input id="retailcrm_url" type="text" name="retailcrm_url" value="<?php if (isset($saved_settings['retailcrm_url'])): echo $saved_settings['retailcrm_url']; endif; ?>"  class="form-control" />
                  </div>
                </div>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label" for="retailcrm_apikey"><?php echo $retailcrm_apikey; ?></label>
                  <div class="col-lg-4 col-md-6 col-sm-10">
                    <input id="retailcrm_apikey" type="text" name="retailcrm_apikey" value="<?php if (isset($saved_settings['retailcrm_apikey'])): echo $saved_settings['retailcrm_apikey']; endif;?>" class="form-control" />
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $retailcrm_countries_settings; ?></legend>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label">Страны</label>
                  <div class="col-lg-4 col-md-6 col-sm-10">
                    <div class="well well-sm" style="height: 150px; overflow: auto;">
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
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_currency; ?></legend>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label"><?php echo $text_currency; ?></label>
                  <div class="col-md-4 col-sm-10">
                    <select id="retailcrm_currency" name="retailcrm_currency" class="form-control">
                      <?php foreach ($currencies as $currency) :?>
                      <?php if ($currency['status']) :?>
                      <option value="<?php echo $currency['code']; ?>" <?php if(isset($saved_settings['retailcrm_currency']) && $saved_settings['retailcrm_currency'] == $currency['code']):?>selected="selected"<?php endif;?>>
                      <?php echo $currency['title']; ?>
                      </option>
                      <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_lenght; ?></legend>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label"><?php echo $text_lenght_label; ?></label>
                  <div class="col-md-4 col-sm-10">
                    <select id="retailcrm_lenght" name="retailcrm_lenght" class="form-control">
                      <?php foreach ($lenghts as $lenght) :?>
                      <option value="<?php echo $lenght['length_class_id']; ?>" <?php if(isset($saved_settings['retailcrm_lenght']) && $saved_settings['retailcrm_lenght'] == $lenght['length_class_id']):?>selected="selected"<?php endif;?>>
                      <?php echo $lenght['title']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $status_changes; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_status_changes"><?php echo $text_status_changes; ?></label>
                  <div class="col-sm-10">
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_status_changes" value="1" <?php if (isset($saved_settings['retailcrm_status_changes']) &&
                      $saved_settings['retailcrm_status_changes'] == 1) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_yes; ?>
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_status_changes" value="0" <?php if (!isset($saved_settings['retailcrm_status_changes']) ||
                      $saved_settings['retailcrm_status_changes'] == 0) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_no; ?>
                    </label>
                  </div>
                </div>
              </fieldset>
              <?php if (isset($saved_settings['retailcrm_apikey']) && $saved_settings['retailcrm_apikey'] != '' && isset($saved_settings['retailcrm_url']) && $saved_settings['retailcrm_url'] != ''): ?>
              <?php if (!empty($retailcrm_errors)) : ?>
              <?php foreach($retailcrm_errors as $retailcrm_error): ?>
              <div class="warning"><?php echo $retailcrm_error ?></div>
              <?php endforeach; ?>
              <?php else: ?>
              <fieldset>
                <legend><?php echo $retailcrm_upload_order; ?></legend>
                <div class="form-group retailcrm_unit">
                  <label class="col-sm-2 control-label"><?php echo $text_button_export_order; ?> №</label>
                  <div class="col-sm-10">
                    <div class="row">
                      <div class="col-lg-3 col-md-6 col-sm-6">
                        <input type="text" name="order_id" class="form-control" />
                      </div>
                      <div class="col-lg-3 col-md-4 col-sm-6">
                        <button type="button" id="export_order" class="btn btn-success"><i class="fa fa-download"></i> <?php echo $text_button_export_order; ?></button>
                      </div>
                    </div>
                  </div>
                </div>
              </fieldset>
              <?php if (isset($saved_settings['retailcrm_apiversion']) && $saved_settings['retailcrm_apiversion'] != 'v3') : ?>
              <fieldset>
                <legend><?php echo $special_price_settings; ?></legend>
                <div class="form-group retailcrm_unit">
                  <?php foreach ($customerGroups as $customerGroup) :?>
                  <?php $cid = $customerGroup['customer_group_id']?>
                  <div class="row retailcrm_unit">
                    <label class="col-sm-2 control-label" style="text-align:right!important;" for="opencart_customer_group_<?php echo $customerGroup['customer_group_id']; ?>"><?php echo $customerGroup['name']; ?></label>
                    <div class="col-md-4 col-sm-10">
                      <select id="retailcrm_special_<?php echo $cid; ?>" name="retailcrm_special_<?php echo $cid; ?>" class="form-control">
                        <?php foreach ($priceTypes as $k => $priceType): ?>
                        <?php if ($priceType['active'] == true and $priceType['default'] == false) :?>
                        <option value="<?php echo $priceType['code'];?>" <?php if(isset($saved_settings['retailcrm_special_' . $cid]) && $priceType['code'] == $saved_settings['retailcrm_special_' . $cid]):?>selected="selected"<?php endif;?>>
                        <?php echo $priceType['name'];?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </fieldset>
              <?php endif; ?>
              <fieldset>
                <legend><?php echo $order_number; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_order_number"><?php echo $text_order_number; ?></label>
                  <div class="col-sm-10">
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_order_number" value="1" <?php if (isset($saved_settings['retailcrm_order_number']) &&
                      $saved_settings['retailcrm_order_number'] == 1) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_yes; ?>
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_order_number" value="0" <?php if (!isset($saved_settings['retailcrm_order_number']) ||
                      $saved_settings['retailcrm_order_number'] == 0) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_no; ?>
                    </label>
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="tab-pane" id="tab-references">
              <fieldset>
                <legend><?php echo $retailcrm_dict_settings; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $retailcrm_dict_delivery; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <?php if ($delivery['opencart']) :?>
                      <?php foreach($delivery['opencart'] as $value): ?>
                      <div class="col-sm-12" style="margin-bottom:10px;">
                        <div class="pm" style="margin-bottom:5px;"><?php echo $value['title'].':'; ?></div>
                        <?php unset($value['title']); ?>
                        <?php foreach ($value as $key => $val): ?>
                        <div class="row retailcrm_unit">
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <select id="retailcrm_delivery_<?php echo $val['code']; ?>" name="retailcrm_delivery[<?php echo $val['code']; ?>]" class="form-control">
                              <?php foreach ($delivery['retailcrm'] as $k => $v): ?>
                              <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_delivery'][$key]) && $v['code'] == $saved_settings['retailcrm_delivery'][$key]):?>selected="selected"<?php endif;?>>
                              <?php echo $v['name'];?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <label class="control-label" style="text-align:left!important;" for="retailcrm_pm_<?php echo $val['code']; ?>"><?php echo $val['title']; ?></label>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                      <?php endforeach; ?>
                      <?php else :?>
                      <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo $text_error_delivery; ?>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $retailcrm_dict_status; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($statuses['opencart'] as $status): ?>
                    <?php $uid = $status['order_status_id']?>
                    <div class="row retailcrm_unit">
                      <div class="col-lg-4 col-md-6 col-sm-6">
                        <select id="retailcrm_status_<?php echo $uid; ?>" name="retailcrm_status[<?php echo $uid; ?>]" class="form-control">
                          <?php foreach ($statuses['retailcrm'] as $k => $v): ?>
                          <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_status'][$uid]) && $v['code'] == $saved_settings['retailcrm_status'][$uid]):?>selected="selected"<?php endif;?>>
                          <?php echo $v['name'];?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-6">
                        <label class="control-label" style="text-align:left!important;" for="retailcrm_status_<?php echo $status['order_status_id']; ?>"><?php echo $status['name']; ?></label>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $retailcrm_dict_payment; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($payments['opencart'] as $key => $value): ?>
                    <div class="row retailcrm_unit">
                      <div class="col-lg-4 col-md-6 col-sm-6">
                        <select id="retailcrm_payment_<?php echo $key; ?>" name="retailcrm_payment[<?php echo $key; ?>]" class="form-control">
                          <?php foreach ($payments['retailcrm'] as $k => $v): ?>
                          <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_payment'][$key]) && $v['code'] == $saved_settings['retailcrm_payment'][$key]):?>selected="selected"<?php endif;?>>
                          <?php echo $v['name'];?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-6">
                        <label class="control-label" for="retailcrm_payment_<?php echo $key; ?>"><?php echo $value; ?></label>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $retailcrm_dict_default; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <div class="retailcrm_unit col-sm-12">
                        <div class="row">
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <select id="retailcrm_default_payment" name="retailcrm_default_payment" class="form-control">
                              <?php foreach ($payments['opencart'] as $k => $v): ?>
                              <option value="<?php echo $k;?>" <?php if(isset($saved_settings['retailcrm_default_payment']) && $k == $saved_settings['retailcrm_default_payment']):?>selected="selected"<?php endif;?>>
                              <?php echo $v;?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <label class="control-label" for="retailcrm_default_payment"><?php echo $text_payment; ?></label>
                          </div>
                        </div>
                      </div>
                      <div class="retailcrm_unit col-sm-12">
                        <div class="row">
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <select id="retailcrm_default_shipping" name="retailcrm_default_shipping" class="form-control">
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
                          </div>
                          <div class="col-lg-4 col-md-6 col-sm-6">
                            <label class="control-label" for="retailcrm_default_payment"><?php echo $text_shipping; ?></label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $retailcrm_missing_status; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <div class="col-lg-4 col-md-6 col-sm-6">
                        <select id="retailcrm_missing_status" name="retailcrm_missing_status" class="form-control">
                          <option>-- Выберите --</option>
                          <?php foreach ($statuses['retailcrm'] as $k => $v): ?>
                          <option value="<?php echo $k;?>" <?php if(isset($saved_settings['retailcrm_missing_status']) && $k == $saved_settings['retailcrm_missing_status']):?>selected="selected"<?php endif;?>>
                          <?php echo $v['name'];?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </fieldset>
              <?php endif; ?>
              <?php endif; ?>
            </div>
            <div class="tab-pane" id="tab-collector">
              <fieldset>
                <legend><?php echo $daemon_collector; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector_active"><?php echo $text_collector_activity; ?></label>
                  <div class="col-sm-10">
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_collector_active" value="1" <?php if (isset($saved_settings['retailcrm_collector_active']) &&
                      $saved_settings['retailcrm_collector_active'] == 1) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_yes; ?>
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_collector_active" value="0" <?php if (!isset($saved_settings['retailcrm_collector_active']) ||
                      $saved_settings['retailcrm_collector_active'] == 0) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_no; ?>
                    </label>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector" class="col-md-4"><?php echo $collector_site_key; ?></label>
                  <div class="col-sm-10">
                    <input id="retailcrm_collector_site_key" type="text" name="retailcrm_collector[site_key]" value="<?php if (isset($saved_settings['retailcrm_collector']['site_key'])): echo $saved_settings['retailcrm_collector']['site_key']; endif; ?>" class="form-control" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector"><?php echo $text_collector_form_capture; ?></label>
                  <div class="col-sm-10">
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
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector_period" class="col-md-4"><?php echo $text_collector_period; ?></label>
                  <div class="col-sm-4">
                    <input id="retailcrm_collector_period" type="text" name="retailcrm_collector[period]" value="<?php if (isset($saved_settings['retailcrm_collector']['period'])): echo $saved_settings['retailcrm_collector']['period']; endif; ?>" class="form-control" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector[]"><?php echo $text_label_promo; ?></label>
                  <div class="col-sm-10">
                    <input id="retailcrm_collector[]" type="text" name="retailcrm_collector[label_promo]" value="<?php if (isset($saved_settings['retailcrm_collector']['label_promo'])): echo $saved_settings['retailcrm_collector']['label_promo']; endif; ?>" class="form-control" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector_label_send"><?php echo $text_label_send; ?></label>
                  <div class="col-sm-10">
                    <input id="retailcrm_collector_label_send" type="text" name="retailcrm_collector[label_send]" value="<?php if (isset($saved_settings['retailcrm_collector']['label_send'])): echo $saved_settings['retailcrm_collector']['label_send']; endif; ?>" class="form-control" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector"><?php echo $collector_custom_text; ?></label>
                  <div class="col-sm-10">
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
                </div>
                <?php foreach ($collectorFields as $field => $label) : ?>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_collector"><?php echo $label; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <div class="col-md-4 col-sm-6">
                        <input id="retailcrm_collector" type="text" name="retailcrm_collector[custom][<?php echo $field; ?>]" value="<?php if (isset($saved_settings['retailcrm_collector']['custom'][$field])) : echo $saved_settings['retailcrm_collector']['custom'][$field]; endif; ?>" class="form-control" />
                      </div>
                      <div class="col-md-8 col-sm-6" style="margin-top: 8px;">
                        <input style="margin-top: 0; vertical-align: middle;" type="checkbox" name="retailcrm_collector[require][<?php echo $field; ?>_require]" value="1" <?php if (isset($saved_settings['retailcrm_collector']['require'][$field.'_require'])) : echo 'checked'; endif;?> />
                        <label style="margin-bottom: 0; vertical-align: middle; margin-left: 5px;" for="retailcrm_collector"><?php echo $text_require; ?></label>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </fieldset>
            </div>
            <?php if (isset($saved_settings['retailcrm_apiversion']) && $saved_settings['retailcrm_apiversion'] == 'v5' && isset($customFields)) : ?>
            <div class="tab-pane" id="tab-custom_fields">
              <fieldset>
                <legend><?php echo $retailcrm_dict_custom_fields; ?></legend>
                <?php if ($customFields['retailcrm'] && $customFields['opencart']) : ?>
                <div class="form-group">
                  <label class="col-sm-2 control-label" for="retailcrm_custom_field_active"><?php echo $text_custom_field_activity; ?></label>
                  <div class="col-sm-10">
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_custom_field_active" value="1" <?php if (isset($saved_settings['retailcrm_custom_field_active']) &&
                      $saved_settings['retailcrm_custom_field_active'] == 1) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_yes; ?>
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="retailcrm_custom_field_active" value="0" <?php if (!isset($saved_settings['retailcrm_custom_field_active']) ||
                      $saved_settings['retailcrm_custom_field_active'] == 0) :
                      echo 'checked'; endif; ?> />
                      <?php echo $text_no; ?>
                    </label>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $text_customers_custom_fields; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <?php foreach ($customFields['opencart'] as $customField) : ?>
                      <div class="col-sm-12" style="margin-bottom:5px;">
                        <div class="row">
                          <?php $fid = 'c_' . $customField['custom_field_id'] ?>
                          <div class="col-sm-4">
                            <select id="retailcrm_custom_field_<?php echo $fid; ?>" name="retailcrm_custom_field[<?php echo $fid; ?>]" class="form-control">
                              <?php foreach ($customFields['retailcrm']['customers'] as $v): ?>
                              <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_custom_field'][$fid]) && $v['code'] == $saved_settings['retailcrm_custom_field'][$fid]):?>selected="selected"<?php endif;?>>
                              <?php echo $v['name'];?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <label style="padding-top: 9px;" for="retailcrm_custom_field_<?php echo $fid; ?>"><?php echo $customField['name']; ?></label>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $text_orders_custom_fields; ?></label>
                  <div class="col-sm-10">
                    <div class="row">
                      <?php foreach ($customFields['opencart'] as $customField) : ?>
                      <div class="col-sm-12" style="margin-bottom:5px;">
                        <div class="row">
                          <?php $fid = 'o_' . $customField['custom_field_id'] ?>
                          <div class="col-sm-4">
                            <select id="retailcrm_custom_field_<?php echo $fid; ?>" name="retailcrm_custom_field[<?php echo $fid; ?>]" class="form-control">
                              <?php foreach ($customFields['retailcrm']['orders'] as $v): ?>
                              <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_custom_field'][$fid]) && $v['code'] == $saved_settings['retailcrm_custom_field'][$fid]):?>selected="selected"<?php endif;?>>
                              <?php echo $v['name'];?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <label style="padding-top: 9px;" for="retailcrm_custom_field_<?php echo $fid; ?>"><?php echo $customField['name']; ?></label>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
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
              </fieldset>
            </div>
            <?php endif; ?>
            <div class="tab-pane" id="tab-logs">
              <fieldset style="margin-bottom: 30px;">
                <legend>retailCRM API error log</legend>
                <div class="retailcrm_unit">
                  <a onclick="confirm('<?php echo $text_confirm_log; ?>') ? location.href='<?php echo $clear_retailcrm; ?>' : false;" data-toggle="tooltip" title="<?php echo $button_clear; ?>" class="btn btn-danger"><i class="fa fa-eraser"></i> <span class="hidden-xs"><?php echo $button_clear; ?></span></a>
                </div>
                <?php if (isset($logs['retailcrm_log'])) : ?>
                <div class="row">
                  <div class="col-sm-12">
                    <textarea wrap="off" rows="15" readonly class="form-control"><?php echo $logs['retailcrm_log']; ?></textarea>
                  </div>
                </div>
                <?php elseif (isset($logs['retailcrm_error'])) : ?>
                <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $logs['retailcrm_error']; ?>
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>
              </fieldset>
              <fieldset>
                <legend>Opencart API error log</legend>
                <div class="retailcrm_unit">
                  <a onclick="confirm('<?php echo $text_confirm_log; ?>') ? location.href='<?php echo $clear_opencart; ?>' : false;" data-toggle="tooltip" title="<?php echo $button_clear; ?>" class="btn btn-danger"><i class="fa fa-eraser"></i> <span class="hidden-xs"><?php echo $button_clear; ?></span></a>
                </div>
                <?php if (isset($logs['oc_api_log'])) : ?>
                <div class="row">
                  <div class="col-sm-12">
                    <textarea wrap="off" rows="15" readonly class="form-control"><?php echo $logs['oc_api_log']; ?></textarea>
                  </div>
                </div>
                <?php elseif (isset($logs['oc_error'])) : ?>
                <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $logs['oc_error']; ?>
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>
              </fieldset>
            </div>
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
