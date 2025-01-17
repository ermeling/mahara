<?php
/**
 *
 * @package    mahara
 * @subpackage core
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

define('INTERNAL', 1);
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'view');
define('SECTION_PAGE', 'blocks');

require(dirname(dirname(__FILE__)) . '/init.php');
require_once(get_config('libroot') . 'view.php');
require_once(get_config('libroot') . 'group.php');

$id = param_integer('id', 0); // if 0, we're editing our profile.
$profile = param_boolean('profile');
$dashboard = param_boolean('dashboard');

if (empty($id)) {
    if (!empty($profile)) {
        try {
            $view = $USER->get_profile_view();
            $id = $view->get('id');
        }
        catch (ViewNotFoundException $_e) {
            throw new ParameterException("Missing parameter id and couldn't find default user profile view");
        }
    }
    else {
        throw new ParameterException("Missing parameter id");
    }
}
if (!empty($id) && empty($view)) {
    $view = new View($id);
}

if (!$USER->can_edit_view($view)) {
    throw new AccessDeniedException();
}

// If the view has been submitted, disallow editing
if ($view->is_submitted()) {
    $submittedto = $view->submitted_to();
    throw new AccessDeniedException(get_string('canteditsubmitted', 'view', $submittedto['name']));
}

$group = $view->get('group');
$institution = $view->get('institution');

if ($group && !group_within_edit_window($group)) {
    throw new AccessDeniedException();
}

// If a block was configured & submitted, build the form now so it can
// be processed without having to render the other blocks.
if ($blockid = param_integer('blockconfig', 0)) {
    // However, if removing a newly placed block, let it fall through to process_changes
    if (!param_exists('cancel_action_configureblockinstance_id_' . $blockid) || !param_integer('removeoncancel', 0) || param_integer('pieform_jssubmission', 0)) {
        require_once(get_config('docroot') . 'blocktype/lib.php');
        $bi = new BlockInstance($blockid);
        // Check if the block_instance belongs to this view
        if ($bi->get('view') != $view->get('id')) {
            throw new AccessDeniedException(get_string('blocknotinview', 'view', $bi->get('id')));
        }
        $bi->build_configure_form();
    }
}

$view->set_edit_nav();
$state = get_string('editcontent1', 'view');

if ($view->get('type') == 'profile') {
    $profile = true;
    $title = get_string('usersprofile', 'mahara', display_name($view->get('owner'), null, true));
    define('TITLE', $title);
}
else if ($view->get('type') == 'dashboard') {
    $dashboard = true;
    $title = get_string('usersdashboard', 'mahara', display_name($view->get('owner'), null, true));
    define('TITLE', $title );
}
else if ($view->get('type') == 'grouphomepage') {
    $title = get_string('Grouphomepage', 'view');
    if ($view->get('template') != View::SITE_TEMPLATE) {
        $groupurl = group_homepage_url(get_group_by_id($view->get('group'), true), false);
    }
    define('TITLE', $title);
}
else {
    define('TITLE', $view->get('title'));
    $editabletitle = true;
}
define('SUBSECTIONHEADING', TITLE);
// Make the default category the first tab if none is set
$category = '';
if (param_exists('c')) {
    $category = param_variable('c');
}
if (empty($category)) {
    $category = $view->get_default_category();
}

$view->process_changes($category, false);

$extraconfig = array(
    'sidebars'    => false,
);

// Set up theme
$viewtheme = $view->set_user_theme();

$allowedthemes = get_user_accessible_themes();

// Pull in cross-theme view stylesheet and file stylesheets
$stylesheets = array();
foreach (array_reverse($THEME->get_url('style/style.css', true, 'artefact/file')) as $sheet) {
    $stylesheets[] = '<link rel="stylesheet" type="text/css" href="' . append_version_number($sheet) . '">';
}

$issiteview = $view->get('institution') == 'mahara';
$issitetemplate = ($view->get('template') == View::SITE_TEMPLATE ? true : false);
$canedittitle = $view->can_edit_title();
$canuseskins = !$issitetemplate && can_use_skins(null, false, $issiteview);

$stylesheets = array_merge($stylesheets, $view->get_all_blocktype_css());
// Tell the user to change the view theme if the current one is no
// longer available to them.
if ($viewtheme && !isset($allowedthemes[$viewtheme])) {
    $smarty = smarty(array(), $stylesheets, false, $extraconfig);
    $smarty->assign('PAGEHEADING', get_string('choosetheme'));
    $smarty->assign('formurl', get_config('wwwroot') . 'view/blocks.php');
    $smarty->assign('view', $view->get('id'));
    $smarty->assign('viewtitle', $view->get('title'));
    $smarty->assign('viewtheme', $viewtheme);
    $smarty->assign('viewthemes', $allowedthemes);
    $smarty->display('view/changetheme.tpl');
    exit;
}

$javascript = array('views', 'tinymce', 'paginator', 'js/jquery/jquery-ui/js/jquery-ui.min.js',
                    'js/jquery/jquery-ui/js/jquery-ui.touch-punch.min.js', 'tablerenderer', 'artefact/file/js/filebrowser.js',
                    'lib/pieforms/static/core/pieforms.js', 'js/switchbox.js');
$blocktype_js = $view->get_all_blocktype_javascript();
$javascript = array_merge($javascript, $blocktype_js['jsfiles']);
if (is_plugin_active('externalvideo', 'blocktype')) {
    $javascript = array_merge($javascript, array((is_https() ? 'https:' : 'http:') . '//cdn.embedly.com/widgets/platform.js'));
}
$inlinejs = "jQuery( function() {\n" . join("\n", $blocktype_js['initjs']) . "\n});";
require_once('pieforms/pieform/elements/select.php');
$inlinejs .= pieform_element_select_get_inlinejs();
$inlinejs .= "jQuery(window).on('pageupdated', {}, function() { dock.init(jQuery(document)); });";
// The form for adding blocks via the keyboard
$addform = pieform(array(
    'name' => 'newblock',
    'method' => 'post',
    'jsform' => true,
    'renderer' => 'div',
    'autofocus' => false,
    'class' => 'cell-radios',
    'elements' => array(
        'blocklegend' => array(
            'type' => 'fieldset',
            'elements' => array(
                'celltitle' => array(
                    'type' => 'markup',
                    'value'=> '<legend>' . get_string('celltitle', 'view') . '</legend>',
                ),
                'cellchooser' => array(
                    'type' => 'radio',
                    'class' => 'fullwidth',
                    'rowsize' => 2,
                    'options' => array('R1C1', 'R1C2', 'R2C1'),
                ),
            ),
        ),
        'position' => array(
            'type' => 'select',
            'title' => get_string('blockorder', 'view'),
            'options' => array('Top', 'After 1', 'After 2'),
        ),
        'submit' => array(
            'type' => 'submitcancel',
            'class' => 'btn-secondary',
            'value' => array(get_string('add'), get_string('cancel')),
        ),
    ),
));

// Build content before initialising smarty in case pieform elements define headers.
$viewcontent = $view->build_rows(true);

// Get the placeholder block info
$placeholderblock = PluginBlockType::get_blocktypes_for_category('shortcut', $view, 'placeholder');
$placeholderbutton = '';
if ($placeholderblock) {
    // it's active so make the button with different display title
    $placeholderblock[0]['title'] = get_string('addnewblock', 'view');
    $placeholderblock[0]['cssicon'] = 'plus';
    $smarty = smarty_core();
    $smarty->assign('blocktypes', $placeholderblock);
    $smarty->assign('javascript', false);
    $placeholderbutton = $smarty->fetch('view/blocktypelist.tpl');
}

$smarty = smarty($javascript, $stylesheets, array(
    'view' => array(
        'addblock',
        'cellposition',
        'blockordertopcell',
        'blockorderafter',
        'moveblock',
    ),
), $extraconfig);

$smarty->assign('addform', $addform);

// Tell smarty we're editing rather than just rendering
$smarty->assign('editing', true);

$smarty->assign('placeholder_button', $placeholderbutton);

// Work out what action is being performed. This is used to put a hidden submit
// button right at the very start of the form, so that hitting enter in any
// form fields will cause the correct action to be performed
foreach (array_keys($_POST + $_GET) as $key) {
    if (substr($key, 0, 7) == 'action_') {
        if (param_boolean('s')) {
            // When configuring a blockinstance and the search tab is open,
            // pressing enter should search
            $key = str_replace('configureblockinstance', 'acsearch', $key);
            if (substr($key, -2) == '_x') {
                $key = substr($key, 0, -2);
            }
        }
        $smarty->assign('action_name', $key);
        break;
    }
}

$viewid = $view->get('id');
$displaylink = $view->get_url();
$smarty->assign('edittitle', $canedittitle);
$smarty->assign('canuseskins', $canuseskins);
$smarty->assign('displaylink', $displaylink);
$smarty->assign('formurl', get_config('wwwroot') . 'view/blocks.php');
$smarty->assign('category', $category);
$smarty->assign('profile', $profile);
$smarty->assign('dashboard', $dashboard);
if (get_config('blockeditormaxwidth')) {
    $inlinejs .= 'config.blockeditormaxwidth = true;';
}
$smarty->assign('INLINEJAVASCRIPT', $inlinejs);
$viewtype = $view->get('type');
$viewtitle = $view->get('title');
$owner = $view->get('owner');
if ($owner &&  $viewtype == 'profile') {
    $viewtitle = get_string('usersprofile', 'mahara', display_name($view->get('owner'), null, true));
}
$smarty->assign('accesssuspended', View::access_override_pending(array('id' => $viewid)));
$smarty->assign('viewtype', $viewtype);
$smarty->assign('view', $view->get('id'));
$smarty->assign('groupid', $group);
if (isset($groupurl)) {
    $smarty->assign('groupurl', $groupurl);
}
$smarty->assign('institution', $institution);

$smarty->assign('viewid', $view->get('id'));
$collectionid = false;
if ($collection = $view->get('collection')) {
    $collectionid = $collection->get('id');
}
$smarty->assign('collectionid', $collectionid);

// The HTML for the columns in the view
$columns = $viewcontent;
$smarty->assign('columns', $columns);

$smarty->assign('issiteview', isset($institution) && ($institution == 'mahara'));

$smarty->assign('issitetemplate', $view->is_site_template());
$smarty->assign('PAGEHEADING', $state);
$smarty->assign('instructions', $view->get('instructions'));
$smarty->assign('instructionscollapsed', $view->get('instructionscollapsed'));
$smarty->display('view/blocks.tpl');
