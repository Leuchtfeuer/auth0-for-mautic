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
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.auth0config'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <?php echo $view['translator']->trans('plugin.auth0.config.form.auth0.hint') ?>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_username']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_email']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_firstName']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_lastName']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_position']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_signature']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_timezone']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['auth0_locale']); ?>
            </div>
        </div>
    </div>
</div>