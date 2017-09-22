<?php echo $header; ?>
<?php echo $column_left;?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
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
                    <input type="hidden" name="retailcrm_status" value="1">

                    <h3><?php echo $retailcrm_base_settings; ?></h3>
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

                    <?php endif; ?>

                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php echo $footer; ?>
