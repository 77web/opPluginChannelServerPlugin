<?php op_include_yesno('packageDeleteConfirmForm', $deleteForm, $backForm, array('title'=>__('Delete Package'), 'yes_url'=>url_for('package_delete', $package), 'no_url'=>url_for('package_edit', $package), 'body'=>__('Are you sure?'), 'yes_button'=>__('Delete'), 'no_button'=>__('Back'), 'no_method'=>'get')); ?>