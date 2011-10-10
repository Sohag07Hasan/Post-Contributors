<?php
/*
*	Plugin Name: Healer's Wiki contributors
*	Description: This plugin shows a nice table with every wiki authors,who created or edited this article at different time. It also show their contribution percentages including rank logo and a donate button.
*	Author: Mahibul Hasan Sohag
*	Author URI: http://sohag07hasan.elanc.com
*	plugin uri: http://www.healerswiki.org
* 	version: 1.0.1
* 
*/

$healerswiki = new healerswiki_contributions();
class healerswiki_contributions{
	
	//constructor functions and all other action hooks
	function __construct(){
		
		//all hooks goes here and I am h
                            
        add_filter('wp_insert_post_data',array($this,'wiki_type_dection'),0,2);
		register_activation_hook( __FILE__, array($this,'table_creation'));
		add_action('wp_print_scripts',array($this,'javascript_adition'));
		add_action('wp_print_styles',array($this,'css_adition'),100);
		
		add_action('deleted_post',array($this,'free_thewiki_table'));
		
		add_action('add_meta_boxes',array($this,'hide_or_show_contributorstalbe'),10,2);
		add_action('save_post',array($this,'save_metabox_data'));
		
		//including the contribution showing class
		include dirname(__FILE__).'/includes/show-contributions.php';
		
	}
	
	//free the wiki table
	function free_thewiki_table($id){
		global $wpdb;
		$table = $wpdb->prefix.'wiki';
		$wpdb->query("DELETE FROM $table WHERE `post_id`=$id ");
		
	}
	
	
	//function to detect wiki type and do certain actions
	function wiki_type_dection($data, $postarr){
		
		$newdata = $data;	
		$data = array();
				
		/*	trigers the necessary actions
		 * if the post is a wiki type and status is publish
		 * */
		if($postarr['post_type'] == 'incsub_wiki' && $postarr['post_status'] == 'publish') :
			//get the current user
			global $current_user;
			get_currentuserinfo();
			
			$user_contributions = array();
			//post content sanitizatin for comparing			
			
			//retrieving the users contributions and sanitize them to compare
			global $wpdb;
			$table = $wpdb->prefix.'wiki';
			$objects = $wpdb->get_results("SELECT * FROM $table WHERE `post_id`='$postarr[ID]' ");			
			$latest_content = $this->str_to_array(strip_tags($postarr['post_content']),'.');
			
			
								
			if($objects){				
				
				
				
				$last_obj = null;
				$new_pre_percentage = array();
				$new_contributions = array();
				
								
				foreach($objects as $key=>$object){	
					
					if($current_user->ID == $object->author_id){
						$last_obj = $object;
						 
					}
					else{								
					
						
						$old_contribution =  $this->str_to_array(strip_tags($object->post_content),'.');		
											
						//latest contirubtions an percentage calculation
						$new_contributions[$object->author_id] = $this->contribution_determination($old_contribution,$latest_content);
						
						$new_pre_percentage[$object->author_id] = $this->pre_percentage_calculation($new_contributions[$object->author_id],strip_tags($postarr['post_content']));										
					}	
					
				}
				
			//	var_dump($new_contributions);
				//var_dump($new_pre_percentage);
				
				//it is time to make the content for current users
				 
				$old_content = $wpdb->get_var("SELECT `post_content` FROM $wpdb->posts WHERE `ID`='$postarr[ID]' AND `post_status`='publish' ");
				
				
				$old_content =  $this->str_to_array(strip_tags($old_content),'.');
				
				$current_user_contribution = $this->current_user_contribution($old_content,$latest_content);
				
				
				
				$current_user_pre_percentage = $this->pre_percentage_calculation($current_user_contribution,implode('.',$latest_content));
				
				
				
				//total percentage calculation
				$total_summation = $this->total_sum($new_pre_percentage,$current_user_pre_percentage);
				
								
				//final percentage calculation and database insertion	except the current user			
				foreach($new_pre_percentage as $author=>$pre_precentage){
					
					$percentage = $this->percent_calculation($total_summation,$pre_precentage);
					
					$data = array(													
						'post_content' => $new_contributions[$author].'.',
						'percent' => (int)$percentage,
						'matched_keys'=> ''
					);
					$data_format = array('%s','%d','%s');
					$where = array(
						'post_id' => $postarr[ID],
						'author_id' => $author
					);

					$where_format = array('%d','%d');
					
					
					$wpdb->update($table,$data,$where,$data_format,$where_format);
					
					
				}
				
				//now it is time for current user to insert or update the databse
				$percentage = floor(($current_user_pre_percentage*100)/$total_summation);
				
				
				if($last_obj){
					$data = array(												
						'post_content' => $current_user_contribution.'.',
						'percent' => (int)$percentage,
						'matched_keys' => ''					
					);
					$data_format = array('%s','%d','%s');
					$where = array(
						'post_id' => $postarr['ID'],
						'author_id' => $last_obj->author_id
					);
					$where_format = array('%d','%d');
												
									
					$wpdb->update($table,$data,$where,$data_format,$where_format);
				}
				
				else{
					$data = array(
						'post_id' => $postarr['ID'],
						'author_id' => $current_user->ID,						
						'post_content' => $current_user_contribution.'.',
						'percent' => $percentage,
						'matched_keys' => ''					
					);
					$format = array('%d','%d','%s','%d','%s');
					$wpdb->insert($table,$data,$format);
				}
				
				
			}
			else{
							
				
				$data = array(
					'post_id' => $postarr['ID'],
					'author_id' => $postarr['post_author'],
					'post_content' => implode('.',$latest_content).'.',
					'percent' => 100,
					'matched_keys' => ''					
				);				
				$format = array('%d','%d','%s','%d','%s');
				
				$wpdb->insert($table,$data,$format);
			}
				
								
		endif;
		
		
		return $newdata;
	}
	
	
	//total percentag calculation
	function total_sum($all,$cuser){
		$c = 0;
		$all[] = $cuser;
		foreach($all as $val){
			$c += $val;
		}
		return $c;
	}
	
	//final percentabe calculation
	function percent_calculation($all,$cuser){
		if($all == 0) return 0;
		return floor($cuser*100/$all);	
	}
	
	
	//current user contribution
	function current_user_contribution($old,$latest){
		$matched = array();
		foreach($old as $o){
			foreach($latest as $l){
				similar_text($l,$o,$p);
				if($p<80){
					$matched[] = $l;
				}
			}
		}
		
		
		return implode('.',$matched);
	}
	
	
	//determination of contributions	
	function contribution_determination($childs,$mothers){
		$matched = array();
		foreach($childs as $child){
			foreach($mothers as $mom){
				$a = similar_text($child,$mom,$per);
				if($per >= 50){
					$matched[] = $mom;
				}
			}
		}
		
		return implode('.',$matched);
	}
	
	//pre percentage calculator
	function pre_percentage_calculation($child,$mom){		
		$c = similar_text($child,$mom,$v);
		
		//return floor($c);
		return floor($c);
	}
	
	
	
	//sting to array converter
	function str_to_array($str,$key){
		//remove the . from last line
		$str = trim($str,'.');
		return explode($key,$str);
	}
	
	//creating wiki control table
	function table_creation(){
		global $wpdb;
			$table = $wpdb->prefix.'wiki';
			$sql = "CREATE TABLE IF NOT EXISTS `$table`(
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) NOT NULL,
				`author_id` bigint(20) NOT NULL,	
				`post_content` longtext collate utf8_general_ci,
				`percent` int(100),
				`matched_keys` longtext collate utf8_general_ci,
				`time` TIMESTAMP,				
				PRIMARY KEY(id)
				
				)";
			//loading the dbDelta function manually
			if(!function_exists('dbDelta')) :
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			endif;
			dbDelta($sql);
	}
	
	//add the javascript for the front-end use
	function javascript_adition(){	
		if ( ! is_admin() ) : 
			wp_enqueue_script('jquery');
			wp_enqueue_script('healerswiki_js',plugins_url('/',__FILE__).'js/script.js',array('jquery'));
		endif;			
				
	}
	
	//css adition
	function css_adition(){	
		if ( ! is_admin() ) :					
			wp_register_style('healerswiki_css',plugins_url('/',__FILE__).'css/style.css');
			wp_enqueue_style('healerswiki_css');	
		endif;
	}
	
	
	//function to hide of add the meta box to show contribution table from admin panel a
	function hide_or_show_contributorstalbe($post_type, $post){
		add_meta_box('wiki-table-hide-or-show',__('Show / Hide Wiki-Contribution Table'),array($this,'wiki_shown_hidden'),'incsub_wiki','advanced','high');
	}
	
	//populating the meta box data
	function wiki_shown_hidden($post){
		
		$status = get_post_meta($post->ID,'wiki-contributons-status',true);
		
	?>	
		To hide the table check the box &nbsp;
		
		<input type="checkbox" value="hide" name="wiki-tabel-shownorhide" <?php checked('hide',$status); ?> /> 
	
	<?php	
	}
	
	//function for saving data
	function save_metabox_data($post_id){	
			
		update_post_meta($post_id,'wiki-contributons-status',$_REQUEST['wiki-tabel-shownorhide']);
		
	}
		
}
?>
