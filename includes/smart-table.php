<?
/*
 * This script is to show the contriutions as a table view
 * */
global $healerswiki;
$new_content = $healerswiki->str_to_array(strip_tags($content),'.');

$tbody = '';
$thead = '<thead>
					<tr>
						<th> Authors </th>						
						<th> Rank </th>
						<th> Contribution </th>						
						<th>  CyberKarma </th>						
					</tr>	
				  </thead>';
	/*	$tfoot = '<tfoot>
					<tr>						
						<th> Author </th>
						<th>Colour</th>
						<th> Rank </th>
						<th> Contribution </th>						
						<th>  CyberKarma </th>						
					</tr>	
				  </tfoot>';*/
		
		$tfoot = '';
					
		//wiki table			
		global $wpdb;		
		$table = $wpdb->prefix.'wiki';
		$objects = $wpdb->get_results("SELECT * FROM $table WHERE `post_id`='$post->ID' ");
				
		if($objects) :
			$tbody = '<tbody>';
			foreach($objects as $object){
							
				
				//getting the author login name
				$author = $wpdb->get_var("SELECT `user_login` FROM $wpdb->users WHERE `ID`='$object->author_id' ");
				$firstname = get_user_meta($object->author_id,'first_name', true);
				$lastname = get_user_meta($object->author_id,'last_name', true);
				$img = cp_module_ranks_getLogo($object->author_id);
				
				// if no matching found skip the user
				$keys = unserialize($object->matched_keys);
				if(count($keys)<1) continue;
				
				$class = 'wiki-'.$object->author_id;				
				
				$tbody .= '<tr  class="'.$class.'">
					<td>
					
					<a class="'.$author.'" href="javascript:void(0);" >'.$firstname.' '.$lastname.'</a></td>
					
					<td><img style="height:20px;width:20px" src="'.$img.'" alt="not available" /></td>
					<td>'.$object->percent.' %</td>
					<td>
						<a href="#" class="cybercarma-donate">Give some!</a>
					</td>
				</tr>';
			}
			$tbody .= '</tbody>';
		endif;
				
		$table = '<br/><h4>Contributors</h4> 
			<div class="wrap">
				<table class="widefat">'
					. $thead . $tfoot . $tbody .
				'</table>
			</div>';
				
		$content .= $table;		
		
?>
