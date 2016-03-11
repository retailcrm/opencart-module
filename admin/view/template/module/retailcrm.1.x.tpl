<?php echo $header; ?>

<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb): ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($error_warning) : ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php endif; ?>
    <?php if (isset($saved_settings['retailcrm_url'])): ?>
    <div class="success">
        <?php echo $text_notice; ?>
        <a href="<?php echo $saved_settings['retailcrm_url']; ?>/admin/settings#t-main"><?php echo $saved_settings['retailcrm_url']; ?>/admin/settings#t-main</a>
    </div>
    <?php endif; ?>

    <div class="box">
        <div class="heading">
            <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
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

                <?php if (isset($saved_settings['retailcrm_apikey']) && $saved_settings['retailcrm_apikey'] != '' && isset($saved_settings['retailcrm_url']) && $saved_settings['retailcrm_url'] != ''): ?>

                    <?php if (!empty($retailcrm_errors)) : ?>
                        <?php foreach($retailcrm_errors as $retailcrm_error): ?>
                            <div class="warning"><?php echo $retailcrm_error ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <h3><?php echo $retailcrm_dict_settings; ?></h3>

                        <h4><?php echo $retailcrm_dict_delivery; ?></h4>
                        <?php foreach ($delivery['opencart'] as $key => $value): ?>
                            <div class="retailcrm_unit">
                                <select id="retailcrm_delivery_<?php echo $key; ?>" name="retailcrm_delivery[<?php echo $key; ?>]" >
                                    <?php foreach ($delivery['retailcrm'] as $k => $v): ?>
                                        <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['retailcrm_delivery'][$key]) && $v['code'] == $saved_settings['retailcrm_delivery'][$key]):?>selected="selected"<?php endif;?>>
                                            <?php echo $v['name'];?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="retailcrm_delivery_<?php echo $key; ?>"><?php echo $value; ?></label>
                            </div>
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


<?php echo $footer; ?>
