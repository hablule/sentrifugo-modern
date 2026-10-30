<?php
session_start();

$centermarign='0';
$left='240px';
$wrappernewpaddingleft='142px';
$wrapperdivrightmarginright='0px';
$wrappernewpadding='10px';
$sidebarnavright='-18px';
$leftwidth='100px';
$leftactive1='15% !important';
$pading='0px';
$rightblockdata='62% !important';
$m_s_top_padding='8px';
$m_s_bottom_padding='12px';
$formforgroundcolor='#3B3B3B';
$formforgroundcolor='#3B3B3B';
$viewmoredivbgcolor='#d3d9dc';
$countdivbgcolor='#f9f9f9';
$countdivactivebgcolor='#fff8ec';
#f9f9f9
#d3d9dc

if (empty($_SESSION['theme'])) {

    $mbg="#3498db";
    $mfg='white';
    $mho='green';
    $hbg="#253f51";
    $pp="#435f71";
    $body="#fff";
    $tablebg="#fff";
    $font='Roboto Regular'; 
    $color='#3B3B3B';
    $formforgroundcolor='#3B3B3B';
   $pagecolor="#3B3B3B";
   $hover="#eeeeee";
   $submenudirection="";
    $tablefg='#253f51';
    $empcolor='black';
    $pading="0px";
    $right="-18px";
    $rightsidbar="-18px";
    $rightsidbarposition="absolute";
    $sidebarmarign="-245px";
    $position='relative';
    $sidebarcolor='white';
    $sidebarfgcolor='black';
    $sidebarhocolor='green';

    $tableheader='white';
    $tableheaderfg='black';
    
}
else{

    $empcolor='black';

    $position='relative';
    $rightsidbarposition="absolute";

    $id=$_SESSION['theme'];
    // if($id!=7){
//     $model=new Default_Model_theme();
//     $themedata=$model->getsingleData($id);
//     $themedata=$themedata[0];
//     $mbg=$themedata['menucolor'];
//     $mfg=$themedata['menufgcolor'];
//     $mho=$themedata['menuhocolor'];

//     $hbg=$themedata['headercolor'];
//     $pp=$themedata['headercolor'];
//     $body=$themedata['bodycolor'];
//     $table=$themedata['tablecolor'];
//     $sidebarcolor=$themedata['sidebarcolor'];
//     $sidebarfgcolor=$themedata['sidebarfgcolor'];
//     $sidebarhocolor=$themedata['sidebarhocolor'];

//     $tableheader=$themedata['tableheadercolor'];
//     $tableheaderfg=$themedata['tableheaderfgcolor'];

//     $font=$themedata['font'];  
//     // $color='white';
//     // $red='#3B3B3B';
//     $pagecolor="#3B3B3B";
//     $hover="rgb(129, 98, 98)";
//     $tfg='white';
//     $empcolor='white';
//     $menustyle=$themedata['menustyle'];
//     if($menustyle=='top_left'){
//         $submenudirection="";
//         $pading="100px";
//         $right="-18px";
//         $rightsidbar="-18px";
//         $sidebarmarign="-245px";
//         $sidebarmarign="-245px";

//         }elseif($menustyle=='bottom_left'){
//             $submenudirection="bottom:0";
//             $pading="100px";
//             $right="-18px";
//             $rightsidbar="-18px";
//             $sidebarmarign="-245px";
    
//         }
//         elseif($menustyle=='style1'){
//             $pading="194px";
//             $right="-18px";
//             $rightsidbar="-18px";
//             $sidebarmarign="-245px";
//             $leftbar = '90%';
//             $co='white';
//             $leftwidth='150px';
//               $wrappernewpadding='110px';
//             $wrappernewpaddingleft='110px';
//             $leftactive1='233px';
//         }
//         elseif($menustyle=='top_right'){
//             $right="1160px";
//             $rightsidbar="94px";
//             $rightsidbarposition="relative";
//             $sidebarmarign="60%";
//             $pading='16px';
//             $leftbar = '-254px';
//             $wrapperdivrightmarginright='17%';
//             $wrappernewpaddingleft='13%';
//             $sidebarmarign='85%';
//             $wrappernewpadding='13%';
//             $sidebarnavright='237px';


//         }
//         elseif($menustyle=='bottom_right'){
//             $right="1160px";
//             $rightsidbar="238px";
//             $sidebarmarign="85%";
//             $pading='90px';
//             $leftbar = '-254px';
//           $activepad='-134px';
// $submenudirection="bottom:0";
// $wrapperdivrightmarginright='17%';
// $wrappernewpaddingleft='13%';
// $sidebarmarign='85%';
// $wrappernewpadding='13%';
// $sidebarnavright='237px';
//         }elseif($menustyle=='style2'){
//             $right="1160px";
//             $rightsidbar="-91px";
//             $rightsidbarposition="relative";
//             $wrappernewpaddingleft='250px';
//             $co='white';
//             $sidebarmarign="94px";
//             $leftbar = '--1%';
//             $pading='90px';
//             $mbg='#594b4f';
//             $centermarign='-8%';
//             $color='#3B3B3B';
//             $tfg='#253f51';
//             $left='0px';

//         }
//         elseif($menustyle=='style3'){
//             $submenudirection="";
//             $pading="0px";
//             $right="-18px";
//             $rightsidbar="-18px";
//             $position='absolute';
//             $menupos='50%';
//             $sidebarmarign="-245px";
//             $sidebarmarign="-245px";
        
//             }
    // }else{
    $model=new Default_Model_theme();
    $themedata=$model->getsingleData($id);

    $themedata=$themedata[0];
    //  Zend_Debug::dump($themedata);
    $theme_id=$themedata['theme_id'];
    $mbg=$themedata['menucolor'];
    $mfg=$themedata['menufgcolor'];
    $mho=$themedata['menuhocolor'];
    $hbg=$themedata['headercolor'];
    $pp=$themedata['headercolor'];
    $body=$themedata['bodycolor'];
    $tablebg=$themedata['tablebgcolor'];
    $tablefg=$themedata['tablefgcolor'];
    $sidebarcolor=$themedata['sidebarcolor'];
    $sidebarfgcolor=$themedata['sidebarfgcolor'];
    $sidebarhocolor=$themedata['sidebarhocolor'];
    $tableheader=$themedata['tableheadercolor'];
    $tableheaderfg=$themedata['tableheaderfgcolor'];
    $font=$themedata['font'];  
    $menustyle=$themedata['menustyle'];
    $menuorentation=$themedata['menuorentation'];
    if($theme_id==1){
        $formforgroundcolor='white';
        $viewmoredivbgcolor='#494c4e';
        $countdivbgcolor='#736f6f';
        $countdivactivebgcolor='#d29d41';

    }
if($menuorentation=='icon_only' or $menuorentation=='both_menu_icon'){
$m_s_top_padding='13px';
$m_s_bottom_padding='9px';
}
    if($menustyle=='top_left'){
        $submenudirection="";
        $pading="0px";
        $right="-18px";
        $rightsidbar="-18px";
        $sidebarmarign="-245px";
        $sidebarmarign="-245px";
        $wrapperdivrightmarginright='0px';
        $wrappernewpaddingleft='245px';
        $wrappernewpadding='0px';

        }
        if($menustyle=='flexmenu'){
            $submenudirection="";
            $pading="0px";
            $right="-18px";
            $rightsidbar="-18px";
            $sidebarmarign="-245px";
            $sidebarmarign="-245px";
            $wrapperdivrightmarginright='0px';
            $wrappernewpaddingleft='245px';
            $wrappernewpadding='0px';
            }
        elseif($menustyle=='bottom_left'){
            $submenudirection="bottom:0";
            $pading="0px";
            $right="-18px";
            $rightsidbar="-18px";
            $sidebarmarign="-245px";
            $wrapperdivrightmarginright='0px';
        $wrappernewpaddingleft='245px';
        $wrappernewpadding='0px';
    $rightblockdata='px';
  
        }
        elseif($menustyle=='style1'){
            $pading="194px";
            $right="-18px";
            $rightsidbar="-18px";
            $sidebarmarign="-245px";
            $leftbar = '90%';
            $co='white';
            $leftwidth='150px';
              $wrappernewpadding='110px';
            $wrappernewpaddingleft='110px';
            $leftactive1='233px';
            $wrapperdivrightmarginright='-8%';

        }
        elseif($menustyle=='top_right'){
            $right="1160px";
            $rightsidbar="94px";
            $rightsidbarposition="relative";
            $sidebarmarign="60%";
            $pading='16%';
            $leftbar = '-254px';
            $wrapperdivrightmarginright='0px';
            $wrappernewpaddingleft='13%';
            $sidebarmarign='85%';
            $wrappernewpadding='0px';
            $sidebarnavright='237px';


        }
        elseif($menustyle=='bottom_right'){
            $right="1160px";
            $rightsidbar="238px";
            $sidebarmarign="85%";
            $pading='16%';
            $leftbar = '-254px';
          $activepad='-134px';
$submenudirection="bottom:0";
$wrapperdivrightmarginright='0px';
$wrappernewpaddingleft='13%';
$sidebarmarign='85%';
$wrappernewpadding='0px';
$sidebarnavright='237px';
$rightblockdata='px';

        }elseif($menustyle=='style2'){
            if($menuorentation=='both_menu_icon' || $menuorentation=='menu_only' && $theme_id==12 ){
                $leftwidth='200px';
                $right="1160px";
                $rightsidbar="-91px";
                $rightsidbarposition="relative";
                $wrappernewpaddingleft='250px';
                $wrapperdivrightmarginright='-170px';
    
                $co='white';
                $sidebarmarign="193px";
                $leftbar = '-1%';
                $pading='150px';
                // $mbg='#594b4f';
                $centermarign='-8%';
                $color='#3B3B3B';
                $left='0px';            }
            else{
            $right="1160px";
            $rightsidbar="-91px";
            $rightsidbarposition="relative";
            $wrappernewpaddingleft='250px';
            $wrapperdivrightmarginright='-97px';

            $co='white';
            $sidebarmarign="6%";
            $leftbar = '-1%';
            $pading='90px';
            // $mbg='#594b4f';
            $centermarign='-8%';
            $color='#3B3B3B';
            $left='0px';
            }
        }
        elseif($menustyle=='style3'){
            $submenudirection="";
            $pading="0px";
            $right="-18px";
            $rightsidbar="-18px";
            $position='absolute';
            $menupos='50%';
            $sidebarmarign="-245px";
            $sidebarmarign="-245px";
        
            }
    // $color='#3B3B3B';
    // $red='#3B3B3B';
    // $pagecolor="#3B3B3B";
    // $hover="#eeeeee";
    // $tfg='#253f51';
    // $empcolor='#666666';
    }

// }
?>

<style>
    .footersentrifigo {
    background: <?php echo $body;?>;
    border: 2px solid #ffffff;
    height: 33px;
    margin: 0 auto;
    margin-top: 2%;
    margin-bottom: 2%;
padding-top: 30px;
    /* width: 99%; */
    width: 100%;
    min-width: 980px;
}
    .page_txt span { float:left; margin:1px 7px 0 0 ; font-family: 'Roboto Regular', sans-serif; color: <?php echo $tablefg;?>; font-size: 13px; }

    .user_color{
        background-color: #f47971;
    }
    *{
        font-family: <?php echo $font ?>;
    }
    .sub{
  position: absolute;
  max-width: 160px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 20;
    background-color: <?php echo $mbg?>;
    margin-top: 17px;
    color: white;
    display: block;
    /* overflow:scroll auto; */
}
.sub .sub2{
  position: absolute;
width: auto;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 0;
    background-color: <?php echo $mbg?>;
    color: white;
    display: block;
    /* margin: 2px 45px 45px -20px; */
    /* margin: -39px 49px 61px 115px; */
    margin: -37px 38px 58px 85%;    /* overflow:scroll auto; */
}

.sub .sub2 li a{
color: white;
padding: 3px 20px 10px 0px;
display: block;
text-decoration: none;
}

.sub ul{
color: white;
/* margin: 20px 20px 20px 20px; */
margin: 15px 0px 25px 24px;

}
.sub ul ul{
color: white;
margin: 20px 20px 20px 20px;
}
    .js .menu {  height: 1.5em; list-style:none; background-color: <?php echo $mbg?>; /* overflow: hidden;*/}
.js .menu li {  background-color: <?php echo $mbg?>; cursor: pointer; ;}
.js .menu a {  padding-top: 10px; padding-bottom: 10px;}

    #wrappernew.active {
    padding-left: <?php echo $wrappernewpaddingleft;?>;
}
    .sidebar-nav {
    position: absolute;
    top: 2%;
    right: <?php echo $sidebarnavright;?>;
    list-style: none;
    margin: 0;
    padding: 0;
}
.acc_li :hover{
    background-color:<?php echo $mho;?> ;
/* color: #ffc40c; */
}
.submenu ul .sub{
    position: absolute;
    display: block;
    margin-top: 20px;
    text-decoration: none;
    background: transparent;
}
    #left {
        position: absolute;
        border-right: 1px solid #dee2e6;
        border: dashed 1px;
        width:<?php echo $leftwidth; ?>;
        /* background: <?php echo $body ?>; */
        background-color: <?php echo $mbg?>;
        color:<?php echo $mfg; ?>;
        position: absolute;
        height: fit-content;
        z-index: 10;
        margin-left:<?php echo $leftbar?>;

        /* padding-right:195px; */
       /*  right: <?php echo $right ?>; */
        /* right: -18px; 1163 */
        transition: all .4s ease 0s;
        overflow-y: auto;
        /* margin:85%; 490px*/
    }
  

    #wrappernew {
    padding-left: <?php echo $wrappernewpadding;?>;
    transition: all .4s ease 0s;
    padding-right: <?php echo $pading?>;
    height: 100%;
}
#wrappernew.active1 #sidebar-wrapper{
    left: <?php echo $leftactive1;?>; 
}
#wrappernew.active #sidebar-wrapper {
    left: <?php echo $left;?>;
}

    .menu-head ul.flexMenu-popup li {
	margin: 0 8px;}
.menu-head ul.flexMenu-popup li.link-selected b {
	color: #fff;
	padding: 4px 10px 8px;
}
.menu-head  ul.flexMenu-popup li b {
	display: block;
}
.menu-head  ul li{ list-style:none; float:left; margin:0 12px;}
.menu-head  ul li b{ font-family: <?php echo $font ?>;   font-size:15px;  color:#fff; font-weight:normal; cursor:pointer;}
.menu-head  ul li b :hover { color: red;}

.menu-head  ul li.flexMenu-viewMore a{ font-family: <?php echo $font ?>;   font-size:15px;  color:#fff; font-weight:normal; cursor:pointer; text-decoration:none; background:url(<?php echo MEDIA_PATH?>images/more_dots.png) no-repeat right center; padding-right:17px;}
.lt-ie8 .flexMenu-popup {}
.flexMenu-popup a {  display: block;}
.flexMenu-popup a:hover {  background-color: #006363;  color: #fff;  display: block;}
.menu-head ul.flexMenu-popup li a{ background:none; }
.menu-head ul.flexMenu-popup li b{color:<?php echo $mfg;?>;}
.menu-head ul.flexMenu-popup li:hover{cursor:pointer;
}
.menu-head ul.flexMenu-popup li:hover b{ color:#E71052; }
.menu-head ul.flexMenu-popup li.link-selected b{ color:#E71052; }
.menu-head ul.flexMenu-popup li{ padding: 6px 2px;    text-align: left; width: 84%;}
li.flexMenu-viewMore{position:<?php echo $position?>; margin-left: <?php echo $menupos?>;}
.menu-head  ul.flexMenu-popup li.clickable_menu a{ background:transparent none repeat scroll 0 0; padding-right:5px;}
.menu-head  ul li.link-selected b{ background: #253f51 none repeat scroll 0 0; color: #fff; padding: <?php echo $m_s_top_padding;?> 15px <?php echo $m_s_bottom_padding;?> 15px;}
    .flexMenu-popup {  list-style-type: none;  padding: 4px 0;  background-color: <?php echo $mbg?>;  margin: 0;   z-index: 125;    	-webkit-box-shadow:  -1px 0px 3px 1px rgba(0, 0, 0, 0.3);    box-shadow:  -1px 0px 3px 1px rgba(0, 0, 0, 0.3);<?php echo $submenudirection;?>; right: 0px; width:168px;}
    .home_menu .menu-head { /* width:100%; */
margin:0 auto; padding:8px 0 9px 10px; height:24px;
background: <?php echo $mbg?>;  
border-top:0; border-top:1px solid #e9eef3;
/*-webkit-border-bottom-right-radius: 5px;
-webkit-border-bottom-left-radius: 5px;
-moz-border-radius-bottomright: 5px;
-moz-border-radius-bottomleft: 5px;
border-bottom-right-radius: 5px;
border-bottom-left-radius: 5px;
-webkit-box-shadow: inset 1px 0 0 0 #686868 ; 
box-shadow: inset 1px 0 0 0 #686868; 
border:1px solid #000000;*/ 
}
.home_menu .menu-head {/* width:100%; */margin:0 auto; padding:8px 0 9px 10px; height:24px;
background:/*url(../images/black-pattren-bg.png) repeat*/ <?php echo $mbg?>;  
/*-webkit-border-bottom-right-radius: 5px;
-webkit-border-bottom-left-radius: 5px;
-moz-border-radius-bottomright: 5px;
-moz-border-radius-bottomleft: 5px;
border-bottom-right-radius: 5px;
border-bottom-left-radius: 5px;
-webkit-box-shadow: inset 1px 0 0 0 #686868 ; 
box-shadow: inset 1px 0 0 0 #686868; 
border:1px solid #000000;*/ border-top:0; border-top:1px solid #e9eef3;
}
.separetor-heading_radio { margin:10px 0 20px 0; padding:0 0 5px 0; clear:both; width:100%; float:left;font-family:<?php echo $font ?>; font-size: 16px; color: <?php echo $color ?>; text-shadow: 0 1px 0 #ffffff; text-align: left;  border-bottom: 1px dashed #898989; }
.separetor-heading { margin:0 0 10px 0; padding:0; clear:both; width:100%; float:left; font-family:<?php echo $font?>; font-size: 15px; color:<?php echo $color ?>; text-shadow: 0 1px 0 #ffffff; text-align: left;
}
#bottom {
   
    padding: 0;
    margin: 0 auto;
    width: 100%;
    float: left;
    position: relative;
    margin-top: 0%;
}
.logoutdiv{
    /*background-color: #FFFFFF;
    height: 50px !important;
    width: 157px;
    min-width:110px; 
	height:35px;  */
    background-color:<?php echo $mbg?>; 

	min-height: 121px;
   /* width: 160px;*/
	position: absolute;
    right: 0;
    text-align: center;
    top: 50px;
/*	-webkit-border-radius: 0 0 5px 5px;
    border-radius: 0 0 5px 5px;
	-webkit-box-shadow:  -1px 0px 3px 1px rgba(0, 0, 0, 0.3);
    box-shadow:  -1px 0px 3px 1px rgba(0, 0, 0, 0.3);*/
	cursor:pointer;
	z-index: 99999999;
}
.logoutdiv a, .logoutdiv span{ color: <?php echo $mfg;?> !important; float: left;  text-decoration: none; text-shadow: 0 1px 0 #ffffff; width:140px; padding: 7px 10px 7px 15px; text-align: left; font-size:13px; border-bottom:1px dashed #dcdee0;}
.logoutdiv a.first:hover{ background-color:<?php echo $hover?>; /*-webkit-border-top-left-radius: 5px;
-webkit-border-top-right-radius: 5px;
-moz-border-radius-topleft: 5px;
-moz-border-radius-topright: 5px;
border-top-left-radius: 5px;
border-top-right-radius: 5px;*/}
.logoutdiv a:last-child:hover{ background-color:<?php echo $hover?>; /*-webkit-border-bottom-right-radius: 5px;
-webkit-border-bottom-left-radius: 5px;
-moz-border-radius-bottomright: 5px;
-moz-border-radius-bottomleft: 5px;
border-bottom-right-radius: 5px;
border-bottom-left-radius: 5px; */}
.logoutdiv a:hover, .logoutdiv span:hover{background-color:<?php echo $hover?>;  }
    #header {
/* background-image:url(../images/header_bg.png);
background-repeat:repeat-x;
	height: 42px;       sapplcia */
	position:relative; 
	width:100%;  
	background-color:<?php echo $hbg?>; 
	min-height:50px; 
	
	/*border-bottom:1px solid #101010; */ 
	/*-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 1);
	box-shadow:  0px 0px 6px 1px rgba(0, 0, 0, 1); z-index:100;
*/
}
.new-form-ui-submit button {background: none transparent;border: 0 none;box-shadow: none;color: <?php echo $formforgroundcolor ?>;font-family: <?php echo $font ?>;font-size: 14px;margin: 11px 0 0 0;padding: 0;text-shadow: none;float: left;text-transform: capitalize;cursor: pointer;line-height: 19px;margin: 3px 0;}	

#header .links {

/* width:336px;
float:right;
height:34px; 
modified by sapplica */ 
color: #FFFFFF;
float: right;
font-family: <?php echo $font?>;
font-weight: normal;
margin: 0px 0px 0 0; 
/*text-shadow: 0 1px 0 #165674;*/ position: relative;
background:<?php echo $pp?>; 
}
#header a.logo{ 

font-family:'nexalight'; 
font-size:28px; 
color:#FFFFFF; 
font-weight:normal; 
margin:6px 0 0 10px; 
float:left; 
text-shadow:0px 1px  0px #165674; 
/*background:url(../images/hrms-logo-new.png) no-repeat left center;*/  height: 40px;  
}
#header a.logo img { max-width:265px; max-height:40px; }

#header p{
	margin:0;
	font-family: <?php echo $font?>; /*Added By Sapplica */
	font-size:14px;
	padding-top:10px;
	color:#8a8887;
	padding-left:15px;
	padding-right:5px;
	/*float:right;*/ float:left;
	margin-right:20px;}
	
	#header span{
	font-family: <?php echo $font?>; /*Added By Sapplica */
	font-size:13px;
	color:#000000;
	margin-right:10px;
	/*margin:0;*/
	/*padding-top:10px;*/
	/*padding-left:10px;*/}
	
	#header span a{
	font-family: <?php echo $font?>; /*Added By Sapplica */
	font-size:14px;
	padding-top:10px;
	color:#FFF;
	/* padding-left:10px;
	text-decoration:none;
	padding-right:10px; */}
    #header span a:hover{
	color:#8a8887;}
    #wrapper {
    /*margin: 15px 0;*/ margin:0; padding:15px 0;
    position: relative;
    width: 100%;
}
.wrapperdiv {
    margin: 0 auto;
    position: relative;
    width: 100%;
}
.wrapperdivleft {
    float: left;
    margin: 0;
    position: relative;
    width: 27%;
	/*z-index:1; z-index:8;*/ z-index:100;
}
.wrapperdivrightmain {
    clear: both;
    margin: 0 auto;
    padding: 15px 0;
    position: relative;
    width: 98%;
}
.wrapperdivrightmainleft {
    float: left;
    margin: 0;
    width: 49%;
}
.wrapperdivrightmain table {
    background-color: #FFFFFF;
    border: 1px solid #BBBBBB;
}
.wrapperdivrightmainleft table {
    background-color: #FFFFFF;
    border: 1px solid #BBBBBB;
}
.checkhomemenu{
    margin-left: <?php echo $menupos?>;
    padding-right: <?php echo $menustyle?>;
    width: 245px;
    float:left;
    box-shadow: 1px 2px 8px 10px 12px rgba(0, 0, 0, 0.3);
    background-color: transparent;
}
.wrapperdivrightmain table tr.grdi {
    /*background: -moz-linear-gradient(center top , #FFFFFF 0%, #EEEEEE 100%) repeat scroll 0 0 transparent;*/
background: #ffffff; /* Old browsers */
/* IE9 SVG, needs conditional override of 'filter' to 'none' */
background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNlZWVlZWUiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
background: -moz-linear-gradient(top, #ffffff 0%, #eeeeee 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#eeeeee)); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* IE10+ */
background: linear-gradient(to bottom, #ffffff 0%,#eeeeee 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#eeeeee',GradientType=0 ); /* IE6-8 */
color: #333333;
font-family:<?php echo $font?>;
font-weight: normal;
height: 35px;
text-transform: uppercase; }

.wrapperdivrightmainleft table tr.grdi {
    /* background: -moz-linear-gradient(center top , #FFFFFF 0%, #EEEEEE 100%) repeat scroll 0 0 transparent; */
 background: #ffffff;  
/* IE9 SVG, needs conditional override of 'filter' to 'none' */
background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNlZWVlZWUiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
background: -moz-linear-gradient(top, #ffffff 0%, #eeeeee 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#eeeeee)); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* IE10+ */
background: linear-gradient(to bottom, #ffffff 0%,#eeeeee 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#eeeeee',GradientType=0 ); /* IE6-8 */
color:<?php echo $color ?>; font-family:<?php echo $font?>; font-weight: normal; height: 35px; text-transform: uppercase; }
.wrapperdivrightmain table tr td:last-child {
    border-right: medium none;
}
.wrapperdivrightmainleft table tr td:last-child {
    border-right: medium none;
}
.wrapperdivrightmain table tr td {
    border-right: 1px solid #DCDCDC;
    padding: 5px 10px;
}
.wrapperdivrightmainleft table tr td {
    border-right: 1px solid #DCDCDC;
    padding: 5px 10px;
}
.wrapperdivrightmain table tr.grdi label {
    color: #333333;
    float: left;
    font-family: <?php echo $font?>;
    font-weight: normal;
    text-transform: uppercase;
}.agency-ui ul li{ float:left; width:209px; clear: both; text-align: left;line-height:25px; /* padding: 10px;*/ padding: 7px 10px; color:<?php echo $formforgroundcolor?>; font-family: <?php echo $font?>; font-size:14px; position: relative; list-style: none; /*border: 1px solid #eeeeee;*/ border-right:0; word-wrap: break-word; }
.hrms-text { position: absolute; font-family: <?php echo $font?>; font-size: 18px; top: 248px; width: 100%; color: #2f2f2f; }
#personalDetailsDiv span { float:left; margin:0 0 0px 15px; text-align: left; color: <?php echo $color ?>; line-height: 24px; font-size: 14px; text-shadow: 0 1px 0 #FFFFFF; }
#personalDetailsDiv span u{ float:left; margin:0 5px 0px 0; text-align: left; color: <?php echo $color ?>; line-height: 24px; font-size: 14px; text-shadow: 0 1px 0 #FFFFFF; text-decoration: none; }

#personalDetailsDiv span.bold-text { font-family:<?php echo $font?>;  }
.new-form-ui .division .add-coloum {float: right;width:auto;clear: both;font-family: 'Roboto Regular',sans-serif;color: #208BBD;font-size: 12px;font-weight: normal;text-align: left;margin-top:2px;cursor: pointer;margin-right: 10px;}

#personalDetailsDiv b { float:left; min-width:140px; margin:0 0 0px 10px; text-align: left; line-height: 24px; font-weight: normal; font-size: 14px; font-family: <?php echo $font?>; 
text-shadow: 0 1px 0 #FFFFFF; color: <?php echo $color?>; width:10%; }
.wrapperdivrightmainright table tr.grdi {
    /*background: -moz-linear-gradient(center top , #FFFFFF 0%, #EEEEEE 100%) repeat scroll 0 0 transparent;*/
	background: #ffffff; /* Old browsers */
/* IE9 SVG, needs conditional override of 'filter' to 'none' */
background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNlZWVlZWUiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
background: -moz-linear-gradient(top, #ffffff 0%, #eeeeee 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#eeeeee)); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top, #ffffff 0%,#eeeeee 100%); /* IE10+ */
background: linear-gradient(to bottom, #ffffff 0%,#eeeeee 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#eeeeee',GradientType=0 ); /* IE6-8 */
    color: #333333;
    font-family: <?php echo $font ?>;
    font-weight: normal;
    height: 35px;
    text-transform: uppercase;
}
.wrapperdivrightmainleft table tr.grdi label {
/*    color: #333333;
    float: left;
    font-family: 'Roboto Regular';
    font-weight: normal;
    text-transform: uppercase; */
color : #333333;
float : left;
font-family: <?php echo $font ?>;
font-weight : normal;
text-transform : uppercase;
}
.wrapperdivrightmainright table {
    background-color: #FFFFFF;
    border: 1px solid #BBBBBB;
}
.wrapperdivrightmainright table tr td:last-child {
    border-right: medium none;
}
.wrapperdivrightmain table tr td:last-child {
    border-right: medium none;
}
.wrapperdivrightmainright table tr td {
    border-right: 1px solid #DCDCDC;
    padding: 5px 10px;
}
.wrapperdivrightmain table tr.sel {
    background-color: #FAFAFA;
}
.wrapperdivrightmainleft table tr.sel {
    background-color: #FAFAFA;

}

.newtablegrid table.grid tbody tr td { white-space: nowrap;text-align:left;color:<?php echo $tablefg ?>; cursor: default;     font-family: <?php echo $font ?>;
 font-size: 14px; font-weight: normal; min-height: 18px; padding: 5px 6px; /*border-right: 1px solid #DCDCDC;  border-bottom: 1px solid #BBBBBB;*/  overflow: hidden; vertical-align: middle;}

 .side-menu {
  padding: 0;
  /* min-height:130px; */
  /*background:url(../images/black-pattren-bg.png) repeat; 
  -webkit-border-top-left-radius: 5px; 
  -webkit-border-bottom-left-radius: 5px; 
  -moz-border-radius-topleft: 5px; 
  -moz-border-radius-bottomleft: 5px; 
  border-top-left-radius: 5px; 
  border-bottom-left-radius: 5px; 
  -webkit-box-shadow: inset 1px 0 0 0 #686868; 
  box-shadow: inset 1px 0 0 0 #686868; 
  border:1px solid #000000; */
  margin:0 0 10px 0;
  float: left;
  width: 100%;
  overflow: hidden;
}
.newtablegrid {   /* border: 1px solid #BBBBBB; border-bottom: 0px solid #BBBBBB; */ color: #362B36; line-height: 25px; float:left; width:100% !important; clear:both; }

side-menu ul {clear: both;margin:0;padding:0;}
.side-menu ul li {list-style: none;float:left;/* width:230px; */width:249px;text-align: left;line-height:25px;margin:5px 0 0 -34px;}
.side-menu ul li.single-menu {list-style: none;text-align: left;line-height:25px;padding: 0px 0 4px 4px;display: block;}
.side-menu ul li span {display: block;height: 30px;padding: 4px 10px 0 63px;  font-family:<?php echo $font?>;
font-size:14px;font-weight:normal;cursor: pointer;color:<?php echo $mfg ?>;border-bottom:dashed 1px #b0b6bb;}
.side-menu ul li span b {  background:url(<?php echo MEDIA_PATH?>images/side-menu-right-arrow.png) no-repeat 96% 5px; font-weight: normal; width:100%; float:left; color:<?php echo $sidebarfgcolor?>; font-family:<?php echo $font?>; }
.side-menu ul li span.acc_li_toggle_down b {  background:url(<?php echo MEDIA_PATH?>images/side-menu-orange-arrow.png) no-repeat 96% 5px; }
.side-menu ul li li {display: block;padding: 4px 0 4px 18px;margin:0;width:93%;border-bottom: dashed 1px #d4d7da;}

.side-menu ul li li a:hover, .side-menu ul li a:hover { background-color: #3498db; background:url(<?php echo MEDIA_PATH?>images/side-menu-point.png) no-repeat left center; }
.side-menu ul li a.ui-draggable:hover, .side-menu ul li a.ui-draggable { background: none; text-shadow:none; color: #253f51; }
.side-menu ul li a.ui-draggable-dragging { color: #3B3B3B; text-shadow: none; background: none; }
.side-menu-selected {color: #3498db !important;background:url(<?php echo MEDIA_PATH?>images/side-menu-point.png) no-repeat left center !important;}
.menu-head  ul li{ list-style:none; float:left; margin:0 12px; }


.menu-head  ul li.flexMenu-viewMore a{     font-family:<?php echo $font?>;
  font-size:15px;  color:#fff; font-weight:normal; cursor:pointer; text-decoration:none; background:url(<?php echo MEDIA_PATH?>images/more_dots.png) no-repeat right center; padding-right:17px;}
/* .menu-head  ul li.link-selected b{ background: #253f51 none repeat scroll 0 0; color: #fff; padding: 8px 15px 12px 15px;} */

/* table -------------------------------------------------*/
.newtablegrid table tbody tr.row1 td{ background-color:<?php echo $tablebg?>; border-bottom:1px solid #dcdcdc;}
.newtablegrid table tbody tr.row2 td{ background-color:<?php echo $tablebg?>; border-bottom:1px solid #dcdcdc;}
.table-header {  text-align: left; padding: 0.5em 0 0; background: <?php echo $tablebg?>; border-bottom:1px solid #bbbbbb; height: 29px; line-height: 23px;  position: relative; top:0; left:0; width:100%; position: relative;
background: <?php echo $tablebg?>; /* Old browsers */
/* IE9 SVG, needs conditional override of 'filter' to 'none' */
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#ededed',GradientType=0); }
.newtablegrid table.grid thead th { /*border-right: 1px solid #DCDCDC; border-bottom: 1px solid #BBBBBB;*/  padding: 7px 22px 7px 6px; text-align: left; background-color: <?php echo $tableheader?>; color:<?php echo $tableheaderfg ;?>;    font-family: <?php echo $font ?>;
 font-size: 13px; font-weight: normal; white-space: nowrap;text-align:left; cursor:pointer;}
.table-btm {  background-color: <?php echo $tablebg?>; /* border: 1px solid #BBBBBB;  border-top: 0px solid #BBBBBB; */ color: #362B36; line-height: 25px; float:left; width:100%; clear:both; margin: 0;  /*position: relative; by barat*/  height:34px; }
/* leftnav-------------------------------------------------- */
.side-menu ul li li a, .side-menu ul li a {     font-family: <?php echo $font?>; color: <?php echo $sidebarfgcolor?> !important;
 font-weight: normal; font-size:14px; float:left; text-align: left; text-decoration: none; background: transparent;
-moz-transition: all 0.3s linear 0s;  padding-left:32px; width:200px;  }
.shortcuts {
   /* background: url(../images/black-pattren-bg.png) repeat scroll 0 0 transparent;
    border: 1px solid #000000;
    border-bottom-left-radius: 5px;
    border-top-left-radius: 5px;
    box-shadow: 0 1px 0 0 #686868 inset;*/
    margin: 0 0 10px 0;
	background:<?php echo $sidebarcolor?>;
    /*min-height: 287px;*/ min-height:130px; float:left;
    /* width: 100%;  width:268px;*/ width: 240px;    color:<?php echo $color?>;
	position:relative; padding-bottom:30px;
	overflow:hidden;
}
.menu-head  ul.flexMenu-popup li.clickable_menu a{ background:transparent none repeat scroll 0 0; padding-right:5px; }
/* body------------------------------------------- */

.wrapperdivright {
    /* background: none repeat scroll 0 0 #fff; */
    background: none repeat scroll 0 0<?php echo $body?>;
    
   /* border-radius: 5px 5px 5px 5px;*/
    float: right;
    /* margin: 20px -8% 0 50px; */
    margin-top: 20px;
    margin-right: <?php echo $wrapperdivrightmarginright;?> ;
    margin-bottom: 15px;
    margin-left: 50px;
	/*min-height: 1050px;  min-height: 905px;*/ min-height: 791px;
    position: relative;
	width:100%;
    /*width: 73%;*/
	/*min-width: 721px; */ min-width: 734px;
	/* z-index:7; */
	padding-bottom:20px; 
}
body{
width:100%;
margin:0 auto;
/*font-family:Arial, Helvetica, sans-serif;
font-size:11px;*/
font-family: <?php echo $font?>;
/* text-align:center; by sapplica*/
/*background:url(../images/debut_dark.png) repeat left top;*/
background:<?php echo $body?>;
}
.reports-div p.one-line{
    color: <?php echo $formforgroundcolor ?> !important;
}
.config_help .h_txt span{
    color: <?php echo $formforgroundcolor ?> !important;
}
.company-info-block, .requisitions-block{
    background-color: <?php echo $body ?> !important;
}
.simply-scroll-clip{
    background: <?php echo $body ?> ;
}
.left-config-ctrl{
    background-color: <?php echo $body?> !important;
}
.reports-ctrl{
 background:<?php echo $mbg ?>  none repeat scroll 0 0 !important;

}
.reports-block-area{
    background: <?php echo $body?> !important;
}
.chart-ctrl {  float:left; /* width:96%; margin:0 2%; */ margin:0; width:100%; }

.agency-ui ul li a{ color: <?php echo $formforgroundcolor?>; text-decoration: none;}
.width_80{ color:black !important; width:60%; float:left; padding:10px 10px; text-align:left; -moz-box-sizing: border-box; word-wrap: break-word;
    -webkit-box-sizing: border-box;
    -ms-box-sizing: border-box;
    box-sizing: border-box; }
.agency-ui ul li{ float:left; width:209px; clear: both; text-align: left;line-height:25px; /* padding: 10px;*/ padding: 7px 10px; color: <?php echo $formforgroundcolor?>; font-family: <?php echo $font?>; font-size:14px; position: relative; list-style: none; /*border: 1px solid #eeeeee;*/ border-right:0; word-wrap: break-word; }
#sidebar-wrapper {
    margin-left: <?php echo $sidebarmarign?>;
    left: 0;
    width: 240px;
    background: <?php echo $sidebarcolor?>;
    position: absolute;
    border-right: 1px solid #dee2e6;
    height: 100%;
    z-index: 0;
    transition: all .4s ease 0s;
}
#sidebar-wrapper .side-menu ul li span b :hover {
    background-color:red;
        

}
#sidebar_menu li a, .sidebar-nav li a {
    color: #253f51;
    display: block;
    text-align: center;
     border-top: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6; 
  border-radius: 0 8px 8px 0;
 padding: 16px 6px 16px 3px;
    background: <?php echo $sidebarcolor?>;
    -webkit-transition: background .5s;
    -moz-transition: background .5s;
    -o-transition: background .5s;
    -ms-transition: background .5s;
    transition: background .5s;
    font-family: <?php echo $font ?>;

/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#eeeeee+0,cccccc+100;Gren+3D */
     /* Old browsers */
     /* FF3.6-15 */
     /* Chrome10-25,Safari5.1-6 */
     /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
     /* IE6-9 */
}
.list-container {
    display: block;
    padding: 14px;
    background: <?php echo $body?>;
}
.list-container .list-item .li-head {
    float: left;
    background-color: <?php echo $body?>;
    position: relative;
    width: 100%;
    padding-top: 12px;
    padding-bottom: 12px;
}
.list-container .list-item .li-body ul li {
    font-size: 14px;
    color:<?php echo $empcolor ?>;
    /* color: #666666; */
    margin-bottom: 8px;
    width: 204px;
    word-wrap: break-word;
    display: inline-block;
    vertical-align: text-bottom;
}
.list-container .list-item .li-body ul li span {
	width: 182px;
    word-wrap: break-word;
    float: right;
    color: <?php echo $formforgroundcolor ?>;
}
#viewmorediv {
    float: left;
    width: 100%;
    color: <?php echo $formforgroundcolor ?>;
    text-align: center;
    background: <?php echo $viewmoredivbgcolor ?>;
    line-height: 38px;
    cursor: pointer;
    font-family: 'Roboto Regular', sans-serif;
    font-size: 14px;
}
.count_new_dis{width:20%; 	background: <?php echo $countdivbgcolor ?>;    display: table-cell;    height: 60px;    position: relative;    text-align: center;    vertical-align: middle; cursor:pointer;}
.count_new_dis_active{ background:<?php echo $countdivactivebgcolor?> !important;}
#new-iframe .slimScrollDiv { background: <?php echo $body ?>; height:480px !important; }

.page-content{
	text-align:left;
	font-size:12px;
 color:<?php echo $formforgroundcolor?>;
	/*font-face:verdana ariel courier;*/     font-family: <?php echo $font ?>;

	padding:5px 10px 5px 10px;
	line-height:1.5em;
}
.list-container .list-item .li-head .li-avatar {
    width: 54px;
    height: 54px;
    border: 1px solid #999999;
    background: #fff;
    padding: 1px;
    float: left;
    margin-right: 8px;
    margin-left: 12px;
}
.list-container .list-item .li-body {
    float: left;
    padding: 0;
    background-color:<?php echo $body?>;
    width: 100%;
}
.shortcuts h1{
    /* color: #3498db; */
    color: <?php echo $sidebarfgcolor ?>;

    font-family: <?php echo $font?>;
    font-size: 14px;
    font-weight: normal;
    /*margin: 10px 25px 20px;*/ margin: 10px 15px 10px 15px;
/*    text-shadow: 0 1px 0 #000000;
    text-transform: uppercase;*/
	text-align: left;
	position:relative;
}
.right-block-data { float:left; border:1px solid #cfcfcf; padding: 10px 15px; background-color:<?php echo $body?>; min-height: 388px; /*-webkit-border-radius: 5px;  -moz-border-radius: 5px;76% !important border-radius: 5px;*/  }
.all-bg-ctrl { float:left; width: 96%; padding:2%; border: 1px solid #cfcfcf; background-color: <?php echo $body?>; margin:0px 0 20px 0; position: relative;
/*-webkit-border-radius: 5px;
-moz-border-radius: 5px;
border-radius: 5px;  */}
.profile-tabs-right-settings { float:left; width:74%; border: 1px solid #cfcfcf; background-color: <?php echo $body?>; min-height:315px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; }
.profile-tabs-right-password { float:left; width:74%; border: 1px solid #cfcfcf; background-color:<?php echo $body?>; min-height:370px; -webkit-border-radius: 5px; -webkit-border-bottom-left-radius: 0; -moz-border-radius: 5px;}
.new-form-ui label, .grpnamecls label { font-family:<?php echo $font?>;
 color:<?php echo $formforgroundcolor; ?>;  font-size: 13px;  font-weight: normal; height: auto; padding:0; margin:0px 0 2px 0; text-align: left;  float :left; clear: both; min-height:20px;}
 .breadcrumbs{ /* background:#fff;padding: 4px;border-bottom:1px solid #ccc;*/color: #353535; float:left; text-align:left;font-family:<?php echo $font?>; font-size:13px; text-shadow: 0 1px 0 #ffffff; color: <?php echo $color ?>; width:96%; margin:10px 2%; }
.firstbreadcrumb { color: #1f8abc; font-family: <?php echo $font?>;text-shadow: 0 1px 0 #ffffff; cursor:pointer;}
.table-header span { padding-left:0px;  text-align: left; text-shadow: 0 1px 0 #FFFFFF; /*text-transform:uppercase;*/ font-family:<?php echo $font?>; color:<?php echo $tableheaderfg ?>;; font-size: 18px; font-weight: normal;
letter-spacing: 0.5px; }
.newtablegrid table.grid thead th span.action-text { float: left; font-size:14px; font-family: <?php echo $font?>; font-weight:normal;}
.newtablegrid table.grid thead th a { color:<?php echo $tableheaderfg ?>; text-decoration: none; float: left; font-family: <?php echo $font?>; font-weight:normal; font-size:14px;}

.list-container .list-item .li-head .li-emp-details .emp-name {
    font-family: <?php echo $font?>;
    font-size: 16px;
}
.list-container .list-item .li-head .li-emp-details .emp-designation,
.list-container .list-item .li-head .li-emp-details .emp-department {
    font-family: <?php echo $font?>;
    font-size: 14px;
}
html,div, span, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, abbr, address, cite, code, del, dfn, em, img, ins, kbd, q, samp, small, strong, sub, sup, var, b, i, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, figcaption, figure,  footer, header, hgroup, menu, nav, section, summary, time, mark, audio, video {
    margin:0;
    padding:0;
    border:0;
    outline:0;
    font-size:100%;
    vertical-align:baseline;
    background:transparent;
    font-family: <?php echo $font?>;
}
.new-form-ui-multi label {  <?php echo $font?>;color: #3b3b3b;  font-size: 13px;  font-weight: normal; height: auto; padding:0; margin:0px 0 2px 0; text-align: left;  float :left; /*width:100%; min-width: 225px;*/ clear: both; min-height:20px;}
</style>