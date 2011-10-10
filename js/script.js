jQuery(document).ready(function($){
		
	// donate button using default boxy library
	$('.cybercarma-donate').bind('click',function(){
					
		//var wiki_class = $(this.parentNode.parentNode).attr('class');
		//var author = $(this.parentNode.parentNode.childNodes[1].childNodes[3]).text();	
		var author = $(this.parentNode.parentNode.childNodes[1].childNodes[1]).attr('class');
		
			
		cp_module_donate();	
			
		$('#cp_recipient').attr('value',author);
		
		
		return false;
	});
	
	
	//donate button
		jQuery('.cp_give_some').bind('click',function(){
			var auth = jQuery(this).attr('id').replace('wiki-','');
			
			cp_module_donate();				
			$('#cp_recipient').attr('value',auth);
		
		});
	
});
