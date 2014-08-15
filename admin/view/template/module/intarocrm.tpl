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
    <?php if (isset($saved_settings['intarocrm_url'])): ?>
    <div class="success">
        <?php echo $text_notice; ?>
        <a href="<?php echo $saved_settings['intarocrm_url']; ?>/admin/settings#t-main"><?php echo $saved_settings['intarocrm_url']; ?>/admin/settings#t-main</a>
    </div>
    <?php endif; ?>

    <div class="box">
        <div class="heading">
            <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <input type="hidden" name="intarocrm_status" value="1">

                <h3><?php echo $intarocrm_base_settings; ?></h3>
                <div class="intarocrm_unit">
                    <label for="intarocrm_url"><?php echo $intarocrm_url; ?></label><br>
                    <input id="intarocrm_url" type="text" name="intarocrm_url" value="<?php if (isset($saved_settings['intarocrm_url'])): echo $saved_settings['intarocrm_url']; endif; ?>">
                </div>
                <div class="intarocrm_unit">
                    <label for="intarocrm_apikey"><?php echo $intarocrm_apikey; ?></label><br>
                    <input id="intarocrm_apikey" type="text" name="intarocrm_apikey" value="<?php if (isset($saved_settings['intarocrm_apikey'])): echo $saved_settings['intarocrm_apikey']; endif;?>">
                </div>

                <?php if (isset($saved_settings['intarocrm_apikey']) && $saved_settings['intarocrm_apikey'] != '' && isset($saved_settings['intarocrm_url']) && $saved_settings['intarocrm_url'] != ''): ?>

                    <?php if (!empty($intarocrm_errors)) : ?>
                        <?php foreach($intarocrm_errors as $intarocrm_error): ?>
                            <div class="warning"><?php echo $intarocrm_error ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <h3><?php echo $intarocrm_dict_settings; ?></h3>

                        <h4><?php echo $intarocrm_dict_delivery; ?></h4>
                        <?php foreach ($delivery['opencart'] as $key => $value): ?>
                            <div class="intarocrm_unit">
                                <select id="intarocrm_delivery_<?php echo $key; ?>" name="intarocrm_delivery[<?php echo $key; ?>]" >
                                    <?php foreach ($delivery['intarocrm'] as $k => $v): ?>
                                        <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['intarocrm_delivery'][$key]) && $v['code'] == $saved_settings['intarocrm_delivery'][$key]):?>selected="selected"<?php endif;?>>
                                            <?php echo $v['name'];?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="intarocrm_delivery_<?php echo $key; ?>"><?php echo $value; ?></label>
                            </div>
                        <?php endforeach; ?>

                        <h4><?php echo $intarocrm_dict_status; ?></h4>
                        <?php foreach ($statuses['opencart'] as $status): ?>
                            <?php $uid = $status['order_status_id']?>
                            <div class="intarocrm_unit">
                                <select id="intarocrm_status_<?php echo $uid; ?>" name="intarocrm_status[<?php echo $uid; ?>]" >
                                    <?php foreach ($statuses['intarocrm'] as $k => $v): ?>
                                        <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['intarocrm_status'][$uid]) && $v['code'] == $saved_settings['intarocrm_status'][$uid]):?>selected="selected"<?php endif;?>>
                                            <?php echo $v['name'];?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="intarocrm_status_<?php echo $status['order_status_id']; ?>"><?php echo $status['name']; ?></label>
                            </div>
                        <?php endforeach; ?>

                        <h4><?php echo $intarocrm_dict_payment; ?></h4>
                        <?php foreach ($payments['opencart'] as $key => $value): ?>
                            <div class="intarocrm_unit">
                                <select id="intarocrm_payment_<?php echo $key; ?>" name="intarocrm_payment[<?php echo $key; ?>]" >
                                    <?php foreach ($payments['intarocrm'] as $k => $v): ?>
                                        <option value="<?php echo $v['code'];?>" <?php if(isset($saved_settings['intarocrm_payment'][$key]) && $v['code'] == $saved_settings['intarocrm_payment'][$key]):?>selected="selected"<?php endif;?>>
                                            <?php echo $v['name'];?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="intarocrm_payment_<?php echo $key; ?>"><?php echo $value; ?></label>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php //var_dump($saved_settings);?>
</div>


<?php echo $footer; ?>