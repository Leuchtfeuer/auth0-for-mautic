<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.leuchtfeuerauth0config'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <?php echo $view['translator']->trans('plugin.leuchtfeuerauth0.config.form.leuchtfeuerauth0.hint'); ?>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_username']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_email']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_firstName']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_lastName']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_position']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_signature']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_timezone']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_locale']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['leuchtfeuerauth0_role']); ?>
                <?php echo $view['form']->row($form->children['multiple_roles']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['rolemapping']); ?>
            </div>
        </div>
    </div>
</div>
