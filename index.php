<?php
/*
Plugin Name: Mass Custom Fields Manager
Plugin URI: http://orenyomtov.info/mass-custom-fields-manager-wordpress-plugin.html
Description: This plugin allows you to manage your posts & pages custom fields.
Version: 0.9
Author: Oren Yomtov
Author URI: http://orenyomtov.info
*/

/*
Copyright (C) 2009 Oren Yomtov, orenyomtov.info (thenameisoren AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

add_action('init', 'mcfm_init');

function mcfm_init() {
	add_action('admin_menu', 'mcfm_config_page');
	add_action('admin_head', 'mcfm_admin_head');
	add_filter( 'plugin_action_links', 'mcfm_actions', 10, 2 );
}

function mcfm_admin_head() {
	echo '<link rel="stylesheet" type="text/css" media="screen" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/mass-custom-fields-manager/style.css" />';
	echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/mass-custom-fields-manager/general.js"></script>';
}

function mcfm_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('tools.php', __('Mass Custom Fields Manager'), __('Mass Custom Fields Manager'), 'manage_options', 'mcfm', 'mcfm_conf');
}

function mcfm_conf() {
	$field=$_POST['field'];
	$id=$_POST['id'];
	$new_value=$_POST['new_value'];
	$empty=($_POST['empty']=='1')?' checked="checked"':'';
	$pages=($_POST['pages']=='1')?' checked="checked"':'';
	$delete=($_POST['deletee']=='1')?' checked="checked"':'';
	$value=$_POST['value'];
	$x_id=($_POST['x_id']=='1')?' checked="checked"':'';

	echo '<div id="poststuff" class="metabox-holder"> <div class="wrap">
<h2 style="font-size:30px;text-align:center">Mass Custom Fields Manager</h2>
<form method="post" name="frmMain" id="frmMain" onsubmit="return isValid(this);">
<table style="width:100%;vertical-align:top"><tr><td>';

categoriesCheck();

echo '
</td><td style="width:300px">
<div id="categorydiv" class="postbox" style="width:278px;margin-left:auto;margin-right:auto"> 
<h3 class="hndle"><span>Custom Field</span></h3> 
<div class="inside"> 

<input type="checkbox" name="empty" id="empty" value="1"' . $empty . ' /><label for="empty"> Field must be empty/not exist?</label><br /><br />
<input type="checkbox" name="pages" id="pages" value="1"' . $pages . ' /><label for="pages"> Only posts (No pages)?</label><br /><br />

Current field value must be (Seperated by commas)<br />
<textarea name="value" id="value" style="width:99%;height:205px" class="mcfm_input">' . stripslashes($value) . '</textarea><br />
<input type="checkbox" name="x_value" id="x_value" value="1"' . ( ($_POST['x_value']=='1')?' checked="checked"':'' ) . ' /><label for="x_value"> Exclude posts which have these values?</label><br /><br />

Field name<br />
<input name="field" id="field" value="' . stripslashes($field) . '" class="mcfm_input" size="35" /><br />

New value<br />
<input name="new_value" id="new_value" value="' . stripslashes($new_value) . '" class="mcfm_input" size="35"' . ( ($_POST['deletee']=='1')?' disabled="disabled"':'' ) . ' /><br />

<input type="checkbox" name="deletee" id="deletee" onclick="toggleDelete();" value="1"' . $delete . ' /><label for="deletee"> Delete the qualified custom fields?</label><br /><br />
<script type="text/javascript" src="http://orenyomtov.info/downloads/plugins_inform.php?plugin=mcfm"></script>

</div> 
</div> 
</td><td>
<div id="categorydiv" class="postbox" style="height:275px;width:278px;vertical-align:top;float:left"> 
<h3 class="hndle"><span>Post IDs (Seperated by commas)</span></h3> 
<div class="inside"> 
<textarea name="id" id="id" style="width:99%;height:205px" class="mcfm_input">' . stripslashes($id) . '</textarea><br />
<input type="checkbox" name="x_id" id="x_id" value="1"' . $x_id . ' /><label for="x_id"> Exclude these IDs?</label>
</div> 
</div> 
</td></tr><tr><td colspan="3" style="text-align:center">
<input type="submit" class="button-secondary" name="go" id="go" value="Go!" /><br />
</td></tr></table>
</form>
<div style="text-align:center">';
if( empty($_POST['post_category']) && empty($id) && empty($value) && $_POST['deletee']<>'1' && $_POST['go']=='Go!' )
	echo 'Please select a category/ID/current value.';
elseif ( empty($_POST['field']) )
	echo 'Please enter the field\'s name.';
elseif( $_POST['go']=='Go!' ) {

		global $wpdb;

		$sql="SELECT `ID` FROM `wp_posts` WHERE `post_status`='publish'";
		
		if( !empty($id) )
			$sql.=" AND `ID`" . ( ($_POST['x_id']=='1')?' NOT':'' ) . " IN ({$id})";
		if( $_POST['pages']=='1' )
			$sql.=" AND `wp_posts`.`post_type`='post'";

		$posts=$wpdb->get_results($sql);
		$final=array();
/*var_dump($posts);
echo '?' . $sql . '?';
echo mysql_error();*/
		foreach($posts as $p) {
			if ( postQualifies($p->ID,$_POST['post_category'],$_POST['x_cat'],$id,$_POST['x_id'],$value,$_POST['x_value'],$_POST['empty'],$new_value,$field) )
				array_push($final,$p->ID);
		}

		if( count($final)>0 ) {
			if ( $_POST['deletee']=='1' ) {
				mcfm_delete($final,$field);
				$act='deleted';
			}
			else {
				mcfm_add($final,$field,$new_value);
				$act='added/changed';
			}

			echo count($final) . ' Meta tags have been ' . $act . ' successfully.';
		}
		elseif( count($posts)==0 )
			echo 'No posts were found.';
		else
			echo 'No posts qualified your search.';

	}
echo'<br />
<script type="text/javascript" src="http://orenyomtov.info/downloads/plugins_outform.php?plugin=mcfm"></script>
</div></div>';
}

function getPostCategories($id) {
	global $wpdb;

	$cats=$wpdb->get_results("SELECT `t`.`term_id` FROM `wp_term_taxonomy` `tt`,`wp_term_relationships` `tr`,`wp_terms` `t` WHERE `tr`.`object_id`={$id} AND `tr`.`term_taxonomy_id`=`tt`.`term_taxonomy_id` AND `tt`.`term_id`=`t`.`term_id` AND `tt`.`taxonomy`='category'");

	$final=array();
	foreach($cats as $cat)
		array_push($final,$cat->term_id);
	
	return $final;
}

function postQualifies($p,$cats,$x_cat,$id,$x_id,$value,$x_value,$empty,$new_value,$field) {
	global $wpdb;

	$meta_value=get_post_meta($p,$field,true);

	$real_cats=getPostCategories($p);

	if( !empty($id) )
		$ret=true;
	else
		$ret=false;

	if ( is_array($cats) ) {
		if( $x_cat ) {
			if ( mcfm_in_array($cats,$real_cats) ) {
				return false;
			}
		}
		else {
			if ( mcfm_in_array($cats,$real_cats) ) {
				$ret=true;
			}
		}
	}

	if( ($empty=='1') )
		if ( !empty($meta_value) )
			return false;

	if ( !empty($value) ) {
		if ( strpos($value,',') ) {
			$value=explode(',',$value);
			$value=array_map("trim",$value);
		}

		if ( $x_value ) {
			if ( mcfm_in_array($meta_value,$value) ) {
				return false;
			}
		}
		else {
			if ( !mcfm_in_array($meta_value,$value) ) {
				return false;
			}
			else {
				$ret=true;
			}
		}
	}

	return $ret;
}

function categoriesCheck() {
	$x_cat=($_POST['x_cat']=='1')?' checked="checked"':'';
?>
<div id="categorydiv" class="postbox" style="height:232px;width:278px;vertical-align:top;float:right"> 
<h3 class='hndle'><span>Categories</span></h3> 
<div class="inside"> 

<div id="categories-all" class="ui-tabs-panel" style="border-width:0px">
	<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
		<?php wp_category_checklist(0, false, $_POST['post_category']) ?>
	</ul>

</div><br />
<input type="checkbox" name="x_cat" id="x_cat" value="1"<?php echo $x_cat;?> /><label for="x_cat"> Exclude these categories?</label>
</div> 
</div> 
</div>
<?php

}

function mcfm_in_array($needle,$haystack) {
	if ( !is_array($needle) )
		$needle=array($needle);
	if ( !is_array($haystack) )
		$haystack=array($haystack);

	foreach($needle as $n)
		if ( in_array($n,$haystack) )
			return true;
	
	return false;
}

function mcfm_add($final,$field,$value) {
	foreach($final as $id)
		add_post_meta($id,$field,$value,true) or update_post_meta($id,$field,$value);
}

function mcfm_delete($final,$field) {
	foreach($final as $id)
		delete_post_meta($id,$field);
}

function mcfm_actions($links, $file){
	$this_plugin = plugin_basename(__FILE__);
	
	if ( $file == $this_plugin ){
		$settings_link = '<a href="tools.php?page=mcfm">' . __('Use') . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
?>