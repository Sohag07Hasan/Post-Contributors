<?php
/*
 * This Template is to show the contribution table below every wiki article
 * */
 
$show_wiki_contribution = new show_wiki_contributions();
class show_wiki_contributions{
	
	//all the necessary hooks are calling here
	function __construct(){
		
		// this is to be called befor original wiki plugin call the filter
		//add_filter('the_content',array($this,'wiki_contributions'),10);
		
		//this is to called after the wiki plugin call the_content filter
		add_filter('the_content',array($this,'wiki_contributions_table'),200);
			
	}
	
	 
	
	//creating the table
	function wiki_contributions_table($content){
		global $post;
		$status  = $status = get_post_meta($post->ID,'wiki-contributons-status',true);
				
		if($post->post_type == 'incsub_wiki' && $status != 'hide') :	
			include dirname(__FILE__).'/smart-table.php';
		endif;
		
		return $content;
	}
}


?>
