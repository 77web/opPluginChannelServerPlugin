<?php echo op_include_form('PackageUpdateForm', $form, array(
  'url'         => url_for('package_update', $package),
  'title'       => __('Edit Package'),
  'isMultipart' => true,
)) ?>

<?php if($isDeletable): ?>
  <?php op_include_form('PackageDeleteForm', $deleteForm, array('url'=>url_for('package_delete', $package), 'title'=>__('Delete Package'))); ?>
<?php endif; ?>