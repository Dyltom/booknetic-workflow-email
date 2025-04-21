<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\EmailWorkflow\EmailWorkflowAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\EmailWorkflow\bkntc__;

?>


<script>
    var workflow_email_action_all_shortcodes = <?php echo json_encode($parameters['all_shortcodes']) ?>;

    var workflow_email_action_all_shortcodes_obj = {};
    workflow_email_action_all_shortcodes.forEach((value,index)=>{
        workflow_email_action_all_shortcodes_obj[value.code] = value.name;
    });
</script>

<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>" type="text/css">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">
<script type="text/javascript" src="<?php echo EmailWorkflowAddon::loadAsset('assets/js/workflow_action_edit.js')?>"></script>


<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
	<div class="title-text"><?php echo bkntc__('Edit action')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">
		<form id="editWorkflowActionForm">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_to"><?php echo bkntc__('To')?></label>
                    <select id="input_to" class="form-control" multiple="multiple">
                        <?php foreach ( $parameters[ 'to_shortcodes' ] as $key => $shortcode ): ?>
                            <option value="<?php echo htmlspecialchars( $key ); ?>" <?php echo isset($shortcode['selected']) ? 'selected' : '';?> ><?php echo htmlspecialchars( $shortcode['value'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

			<div class="form-row">
				<div class="form-group col-md-12">
					<label for="input_subject"><?php echo bkntc__('Subject')?></label>
					<input type="text" class="form-control required" id="input_subject" value="<?php echo empty( $parameters['data']['subject'] ) ? '' : htmlspecialchars($parameters['data']['subject']);?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-12">
					<label for="input_body"><?php echo bkntc__('Body')?></label>
					<textarea class="form-control required" id="input_body"><?php echo empty( $parameters['data']['body'] ) ? '' : htmlspecialchars($parameters['data']['body']);?></textarea>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-12">
					<label for="input_attachments"><?php echo bkntc__('Attachment(s)')?></label>
                    <select id="input_attachments" class="form-control" multiple="multiple">
                        <?php foreach ( $parameters[ 'attachment_shortcodes' ] as $key => $shortcode ): ?>
                            <option value="<?php echo htmlspecialchars( $key ); ?>" <?php echo isset($shortcode['selected']) ? 'selected' : '';?> ><?php echo htmlspecialchars( $shortcode['value'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
				</div>
			</div>

		</form>
	</div>
</div>

<div class="fs-modal-footer">

    <div class="footer_left_action">
        <input type="checkbox" id="input_is_active" <?php echo $parameters['action_info']->is_active ? 'checked' : '' ?>>
        <label for="input_is_active" class="font-size-14 text-secondary"><?php echo bkntc__('Enabled')?></label>
    </div>

	<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="saveAndTestWorkflowActionBtn"><?php echo bkntc__( 'SAVE & TEST' ) ?></button>
    <button type="button" class="btn btn-lg btn-primary" id="saveWorkflowActionBtn"><?php echo bkntc__('SAVE')?></button>
</div>
