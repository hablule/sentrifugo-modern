<?php
class Zend_View_Helper_StyleBuilder extends Zend_View_Helper_Abstract
{
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this->view;
    }

    public function style($menuarr,$act_menus,$usergroup,$menuorentation)
    {
        $testmenu = '';
		foreach($menuarr as $menuid => $data)
		{	
			if($menuorentation=='icon_only'){
				$mo="<img class='icon' height='24px' width='24px' src='".MEDIA_PATH."/images/menuIcons/" . $data['iconPath'] . "' >";
			}
			else if($menuorentation=='menu_only'){
				$mo=$data['menuName'] ;
			}
			else if($menuorentation=='both_menu_icon'){
				$mo="<img class='icon' height='24px' width='24px' src='".MEDIA_PATH."/images/menuIcons/" . $data['iconPath'] . "' > ".$data['menuName'] ."" ;
			}
			if (isset($data['childs']) && count($data['childs']) > 0) {
			
				$testmenu .= "<div class='side-menu div_mchilds_" . $menuid . "' style='display:block;'  onclick='togglesubmenudis(".$menuid.")'><ul>";
				$testmenu .= "<li class='acc_li'><span class='acc_li_toggle' id='acc_li_toggle_" . $menuid . "' onclick='togglesubmenus(".$menuid.")'>".$mo." </span><div class='menupopup'><ul>";
				$testmenu .= "</ul></div></li></ul></div>";
			
			}
			
	}
	if(isset($act_menus[TIMEMANAGEMENT]) && $act_menus[TIMEMANAGEMENT]['isactive'] == 1 && $usergroup!=USERS_GROUP)
		{
			if($menuorentation=='icon_only'){
				$motime="<img class='icon' height='20px' width='30px' src='".MEDIA_PATH."/images/menuIcons/" .$act_menus[TIMEMANAGEMENT]['iconPath'] . "' >";
			}
			else if($menuorentation=='menu_only'){
				$motime=$act_menus[TIMEMANAGEMENT]['menuName'];
			}
			else if($menuorentation=='both_menu_icon'){
				$motime="<img class='icon' height='20px' width='30px' src='".MEDIA_PATH."/images/menuIcons/" .$act_menus[TIMEMANAGEMENT]['iconPath'] . "' > ".$act_menus[TIMEMANAGEMENT]['menuName']."" ;
			}
			$testmenu .= "<div class='side-menu div_mchilds_" . $menuid . "' style='display:block;'>
								<ul>";
						
							//	Zend_Debug::dump($data);
			$testmenu .= "<li class = 'clickable_menu'><a href ='".BASE_URL."timemanagement'><span class='acc_li_toggle'  id='acc_li_toggle_" . TIMEMANAGEMENT . "'>" . $motime . "</span></a><div class='menupopup'><ul>";
			$testmenu .= "</ul></div></li></ul></div>";
		}
		if($menuorentation=='icon_only'){
			$mlogs="<img class='icon' height='20px' width='30px' src='".MEDIA_PATH."/images/menuIcons/log_icon.png' >";
		}
		else if($menuorentation=='menu_only'){
			$mlogs='Logs';
		}
		else if($menuorentation=='both_menu_icon'){
			$mlogs="<img class='icon' height='20px' width='30px' src='".MEDIA_PATH."/images/menuIcons/log_icon.png' > Logs" ;
		}
	$testmenu .= "<div class='side-menu div_mchilds_logs' style='display:block;' onclick='togglesubmenudis(".'"logs"'.")'>
		<ul>
		<li class='acc_li'><span class='acc_li_toggle' id='acc_li_toggle_300' onclick='togglesubmenus(300);'>". $mlogs ."</span><ul>

		</ul>
	  </div>";
    return $testmenu;
    }

}
?> 