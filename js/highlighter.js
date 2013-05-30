function getSelectionText() {
    var text = "";
    if ( window.getSelection ) {
	text = window.getSelection().toString();
    } else if ( document.selection && document.selection.type != "Control" ) {
	text = document.selection.createRange().text;
    }
    return text;
}



jQuery(document).ready(function($){
    
    $( 'body' ).on( 'mouseup', function(e){
		selected = getSelectionText();
		var word = '';
	
		if ( '' != selected ) {
	    	var first_word = selected.split(' ');
	    	word = first_word[0];
		}
	    
		if ( '' ==  word )
			return;
	    
		// Show popdown to report misspelling
		$( 'body' ).append('<div id="missr_dialog" onclick="missr_clicked(\''+word+'\');" style="cursor: pointer; position: fixed; top: 0px; z-index: 999999999; padding: 7px; background-color: yellow; border: 1px solid darkyellow;"> Click to Report Misspelling</div>');
	});
   
});


function missr_clicked( text ) {    
    var data = {
		action: 'missr_report',
		post_id: post.post_id,
		selected: text
    };
    
    jQuery.post( post.ajaxurl, data, function(response) {
		//console.log('Got this from the server: ' + response);
	});
}
