<?php

/**
 * Menu Privileges Helper
 * Generates hierarchical permission UI with legacy CSS compatibility and modern JS hooks.
 * 
 * @category   Application
 * @package    Default
 * @subpackage View_Helpers
 */
class Zend_View_Helper_MenuPrivileges extends Zend_View_Helper_Abstract
{
    /**
     * Map DB columns to UI Labels and Input Names
     */
    protected $_permMap = [
        'add'    => ['col' => 'canadd',        'input' => 'rd_addpermission',      'disabled' => 'adddisabled'],
        'edit'   => ['col' => 'canedit',       'input' => 'rd_editpermission',     'disabled' => 'editdisabled'],
        'delete' => ['col' => 'candelete',     'input' => 'rd_deletepermission',   'disabled' => 'deletedisabled'],
        'view'   => ['col' => 'canview',       'input' => 'rd_viewpermission',     'disabled' => 'viewdisabled'],
        'upload' => ['col' => 'canupload',     'input' => 'rd_uploadattachments',  'disabled' => 'uploaddisabled'],
        'viewup' => ['col' => 'canviewupload', 'input' => 'rd_viewattachments',    'disabled' => 'viewupdisabled']
    ];

    protected $_disabled = false;


    /**
     * Entry Point
     */
    public function menuPrivileges(array $treeData, $disabled = false)
    {
        $this->_disabled = $disabled;

        return '<div id="menu_accordion" class="ui-accordion ui-widget ui-helper-reset" role="tablist">'
            . $this->_buildBranch($treeData, 0, [])
            . '</div>';
    }

    /**
     * Recursive Builder
     * Handles specific HTML structure changes based on Depth (0, 1, 2+)
     */
    protected function _buildBranch($nodes, $depth, $parentIds)
    {
        $html = '';

        foreach ($nodes as $menuId => $node) {
            $hasChildren = !empty($node['child']);
            $isActive    = ($node['active'] === 'Active');

            // 1. Prepare Classes & Attributes
            $ancestorClasses = '';
            foreach ($parentIds as $pid) {
                $ancestorClasses .= ' childclass_' . $pid;
            }

            // Ancestor string for data attributes (e.g., "10,20")
            $parentIdsStr = implode(',', $parentIds);

            // Display States
            $displayStyle = $isActive ? 'block' : 'none';
            $expandedAttr = $isActive ? 'true' : 'false';

            // 2. Render Logic based on Depth (Strict HTML Compliance)

            // --- LEVEL 0 (ROOT) ---
            if ($depth === 0) {
                if ($hasChildren) {

                    $activeHeaderClass = $isActive ? 'ui-accordion-header-active ui-state-active ui-corner-top' : 'ui-corner-all';
                    $iconClass = $isActive ? 'ui-icon-minus' : 'ui-icon-plus';

                    $html .= '<div class="header_class header_' . $menuId . ' ui-accordion-header ui-helper-reset ui-state-default ui-accordion-icons ' . $activeHeaderClass . '" role="tab" aria-selected="' . $expandedAttr . '" tabindex="0">';
                    $html .= '<span class="ui-accordion-header-icon ui-icon ' . $iconClass . '"></span>';

                    $html .= $this->_renderHeaderContent($node, $menuId, 0, $ancestorClasses, $parentIdsStr);

                    // Permissions Block (Collapsible)
                    $html .= $this->_renderPermissions($node, $menuId, $displayStyle, $ancestorClasses);
                    $html .= '</div>'; // End Header

                    // Children Container 
                    $activeContentClass = $isActive ? 'ui-accordion-content-active' : '';
                    $html .= '<div id="iddivcontent' . $menuId . '" class="contentdiv ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ' . $activeContentClass . '" style="display: ' . $displayStyle . ';">';
                    $html .= '<div class="chlist_one ui-accordion ui-widget ui-helper-reset" role="tablist">';

                    // Recursive Call
                    $newParents = $parentIds;
                    $newParents[] = $menuId;
                    $html .= $this->_buildBranch($node['child'], $depth + 1, $newParents);

                    $html .= '</div></div>';
                } else {
                    // Level 0 Leaf
                    $html .= '<div class="header_classnone">';
                    // Note class change: menu_heading2 for L1 leaves
                    $html .= $this->_renderHeaderContent($node, $menuId, 5, $ancestorClasses, $parentIdsStr);
                    $html .= $this->_renderPermissions($node, $menuId, $displayStyle, $ancestorClasses);
                    $html .= '</div>';
                }
            }

            // --- LEVEL 1 (SUB-GROUPS OR LEAVES) ---
            elseif ($depth === 1) {
                if ($hasChildren) {
                    // Level 1 Parent (Sub-Accordion)
                    $html .= '<div class="header_class_one header_' . $menuId . ' ui-accordion-header ui-helper-reset ui-state-default ui-accordion-icons ui-corner-all" role="tab">';
                    $html .= '<span class="ui-accordion-header-icon ui-icon child_level ui-icon-plus"></span>';

                    // Note class change: menu_heading4 for L1 parents
                    $html .= $this->_renderHeaderContent($node, $menuId, 4, $ancestorClasses, $parentIdsStr);
                    $html .= $this->_renderPermissions($node, $menuId, 'none', $ancestorClasses);
                    $html .= '</div>';

                    // Children
                    $html .= '<div id="iddivcontent_one' . $menuId . '" class="contentdiv ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" style="display: none;">';
                    $html .= '<ul class="tree_level_ui">';
                    $newParents = $parentIds;
                    $newParents[] = $menuId;
                    $html .= $this->_buildBranch($node['child'], $depth + 1, $newParents);
                    $html .= '</ul></div>';
                } else {
                    // Level 1 Leaf
                    $html .= '<div class="header_class_onenone">';
                    // Note class change: menu_heading2 for L1 leaves
                    $html .= $this->_renderHeaderContent($node, $menuId, 2, $ancestorClasses, $parentIdsStr);
                    $html .= $this->_renderPermissions($node, $menuId, 'none', $ancestorClasses);
                    $html .= '</div>';
                }
            }

            // --- LEVEL 2+ (DEEP TREE) ---
            else {
                $html .= '<li>';
                // Note class change: menu_heading3 for L2+ items
                $html .= $this->_renderHeaderContent($node, $menuId, 3, $ancestorClasses, $parentIdsStr);
                $html .= $this->_renderPermissions($node, $menuId, 'none', $ancestorClasses);

                if ($hasChildren) {
                    $html .= '<ul>';
                    $newParents = $parentIds;
                    $newParents[] = $menuId;
                    $html .= $this->_buildBranch($node['child'], $depth + 1, $newParents);
                    $html .= '</ul>';
                }
                $html .= '</li>';
            }
        }

        return $html;
    }

    /**
     * Render the Title and Main Checkbox
     */
    protected function _renderHeaderContent($node, $menuId, $headingType, $ancestorClasses, $parentIdsStr)
    {
        $checked = ($node['active'] === 'Active') ? 'checked="checked"' : '';
        $disabled = ($node['disabled'] === 'disabled' || $this->_disabled) ? 'disabled="disabled"' : '';

        // Dynamic class based on depth/type (menu_heading1, 2, 3, 4)
        $headingClass = ($headingType > 0) ? 'menu_heading' . $headingType : 'menu_heading1';

        $html = '<div class="' . $headingClass . '">';

        // DATA ATTRIBUTES for Unobtrusive JS
        $html .= '<input type="checkbox" class="cls_checkboxes' . $ancestorClasses . '" ';
        $html .= $disabled . ' ';
        $html .= 'name="chk_menu[' . $menuId . ']" ';
        $html .= 'id="idcheckbox' . $menuId . '" ';
        $html .= 'value="' . $menuId . '" ';
        $html .= 'data-role="menu-checkbox" ';
        $html .= 'data-id="' . $menuId . '" ';
        $html .= 'data-parents="' . $parentIdsStr . '" '; // For cascading up
        $html .= $checked . '>';

        $html .= '<div class="cls_menuname" ';
        $html .= 'data-role="menu-name" ';
        $html .= $checked . ' ';
        $html .= 'data-id="' . $menuId . '" ';
        $html .= 'id="menu_name'. $menuId . '" >' . $this->view->escape($node['mnu_Name']) . '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render the Permission Radio Buttons
     */
    protected function _renderPermissions($node, $menuId, $displayStyle, $ancestorClasses)
    {
        // Add ancestor classes to permission div for "Select All" logic
        $html = '<div id="idcls_checkboxes_' . $menuId . '" class="cls_radiobuttons_div cls_radiobuttons_div' . $menuId . $ancestorClasses . '" style="display:' . $displayStyle . '">';
        $html .= '<div class="arrow_img_cls"></div>';

        foreach ($this->_permMap as $key => $meta) {
            $dbCol = $meta['col'];
            $dbColDisabled = $meta['disabled'];
            $inputName = $meta['input'] . $menuId;

            // Only render if data exists in array (some menus might not have upload perms)
            if (!isset($node[$dbCol])) continue;

            $val = $node[$dbCol];
            $optionDisabled = $node[$dbColDisabled] === 'disabled' || $this->_disabled;
            $isChecked = ($val === 'Active') ? 'checked="checked"' : '';
            $disabled = ($optionDisabled) ? 'disabled="disabled"' : '';
            $borderClass = ($key == 'add' || $key == 'delete' || $key == 'upload') ? 'permission_div_brdr' : '';

            
            $html .= '<div class="permission_div ' . $borderClass . '">';
            $html .= '<div class="permission_radio_div">';

            // DATA ATTRIBUTES for JSON Serialization
            $html .= '<input type="checkbox" class="cls_radiobuttons_rd cls_radiomenu_yes_' . $menuId . $ancestorClasses . '" ';
            $html .= 'name="' . $inputName . '" ';
            $html .= 'value="Yes" ';
            $html .= 'id="id' . $key . 'permi_yes_' . $menuId . '" ';
            $html .= 'data-role="perm-radio" ';
            $html .= 'data-menu-id="' . $menuId . '" ';
            $html .= 'data-perm-type="' . $key . '" ';
            $html .= $disabled . ' ';
            $html .= $isChecked . '>';

            $html .= '</div>';
            $html .= '<span class="radio_titles">' . ucfirst($key) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}
