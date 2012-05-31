<?php
/**
 * k2categorytemplate.php
 *
 * Allow user to set the template to use based on the content category being viewed.
 * If a template style has been set through the menu item parameter, we retain that setting.
 * If the K2 category inherits from another category, we also search for a match there,
 * but only if there is no direct category match.
 * 
 * Based on SM2 Section Template plugin.
 *
 * @copyright Copyright (C) 2012 Lighthouse Consulting and Design / All Rights Reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @author http://www.lcdservices.biz
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemK2CategoryTemplate extends JPlugin {

    function plgSystemK2CategoryTemplate( &$subject, $config) {

        parent::__construct($subject, $config);
    } // plgSystemCategoryTemplate()

    function onAfterRoute() {

        // dont run in admin
        $mainframe = &JFactory::getApplication();
        if ( $mainframe->isAdmin() ) return;

        // leave if not in the content component
        $option = JRequest::getCmd('option');
        if ( $option!='com_k2' && $option!='k2' ) return;

        // leave if page has a template style manually set
        $menuItemStyle = $mainframe->getMenu()->getActive()->template_style_id;
        if ( !empty($menuItemStyle) ) return;

        // get view and task to decide how we retrieve catid
        $view = (string) JRequest::getVar('view');
        $task = (string) JRequest::getVar('task');
        $catid = $itemid = $catinherit = '';

        //search view
        switch ($view) {
            case 'itemlist':
                //search task
                switch ($task) {
                    case 'category':
                        $catid = (int) JRequest::getVar('id');
                        break;
                    default:
                        return;
                }
                break;
            case 'item':
                $itemid = (int) JRequest::getVar('id');
                break;
            default:
                return;
        }

        // get the database
        $db =& JFactory::getDBO();

        // if itemid, use to retrieve catid
        if ( !empty($itemid) ) {
            $db->setQuery('SELECT catid'
                .' FROM '.$db->NameQuote('#__k2_items')
                .' WHERE id='.$itemid);
            $catid = (int) $db->loadResult();
        }

        //exit if no catid
        if ( empty($catid) ) {
            return;
        }

        //we also want to retrieve the "inherit from" value and check for a template match
        JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'tables');

        $category = &JTable::getInstance('K2Category', 'Table');
        $category->load($catid);
        $cparams  = new JParameter($category->params);

        if ($cparams->get('inheritFrom')) {
            $catinherit = $cparams->get('inheritFrom');
        }

        // create the parameter object and set a default
        $matrix = $this->params->get('matrix','');

        // final validation
        if ($matrix=='') return;

        // retrieve matrix and construct lookup array
        $catTpl = array();
        foreach ( explode("\n", $matrix) as $matrixSet ) {
            list($cid,$tpl) = explode(',', $matrixSet);
            $catTpl[$cid] = $tpl;
        }

        //first check catid; then check inherit catid
        if ( array_key_exists($catid, $catTpl) ) {
            $mainframe->setTemplate($catTpl[$catid]);
            return;
        } elseif ( !empty($catinherit) && array_key_exists($catinherit, $catTpl) ) {
            $mainframe->setTemplate($catTpl[$catinherit]);
            return;
        }

    } // onAfterRoute()

} // class plgSystemK2CategoryTemplate
