( function( window, $ ) {
	var document = window.document;

	var MisspellingReporter = function() {

		var SELF = this;

		SELF.getSelectionText = function() {
			var text = "";
			if ( window.getSelection ) {
				text = window.getSelection().toString();
			} else if ( document.selection && document.selection.type != "Control" ) {
				text = document.selection.createRange().text;
			}
			return text;
		};

		SELF.missrClicked = function( text ) {
			var data = {
				action: 'missr_report',
				post_id: post.post_id,
				selected: text
			};
			var $dialog = $( document.getElementById( 'missr_dialog' ) );

			$.post( post.ajaxurl, data, function(response) {
				//console.log('Got this from the server: ' + response);
			});
			$dialog.addClass( 'success' );
			$dialog.text( post.success );
			setTimeout( function(){
				$dialog.fadeOut();
			}, 500 );
		};

		$(document).ready(function($){

			$( 'body' ).on( 'mouseup', function(e){
				selected = SELF.getSelectionText();
				var word = '';

				if ( '' != selected ) {
					var first_word = selected.split(' ');
					word = first_word[0];
				}

				if ( '' ==  word )
					return;

				// Show popdown to report misspelling
				$( 'body' ).append('<div id="missr_dialog" onclick="MisspellingReporter.missrClicked(\''+word+'\');">' + post.click_to_report + '</div>');
			});

		});
	};

	window.MisspellingReporter = new MisspellingReporter();

} )( window, jQuery );
