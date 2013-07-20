( function( window, $ ) {
	var document = window.document;

	var MisspellingReporter = function() {

		var SELF = this;

		SELF.selectionObj = null;

		SELF.getSelectionText = function() {
			var text = "";
			SELF.selectionObj = window.getSelection();
			if ( SELF.selectionObj ) {
				text = SELF.selectionObj.toString();
			} else if ( document.selection && document.selection.type != "Control" ) {
				SELF.selectionObj = document.selection.createRange();
				text = SELF.selectionObj.text;
			}
			return text;
		};

		SELF.findPostId = function( parentNode ) {

			var parentID = '',
				parentClassName = '',
				matches = null,
				rePattern = new RegExp( "post-[1-9]{1}[0-9]*", "i");

			while( document.body !== parentNode ) {

				parentClassName = parentNode.className;
				if( '' !== parentClassName ) {
					matches = rePattern.exec( parentClassName );
					if( matches ) {
						post.post_id = matches[0].split('-')[1];
						break;
					}
				}

				parentID = parentNode.id;

				if( '' !== parentID ) {
					matches = rePattern.exec( parentID );
					if( matches ) {
						post.post_id = matches[0].split('-')[1];
						break;
					}
				}

				parentNode = parentNode.parentElement;
			}

		};

		SELF.missrClicked = function( node ) {
			var data = {
				action: 'missr_report',
				post_id: post.post_id,
				selected: $(node).attr('data-word')
			};
			var $dialog = $( document.getElementById( 'missr_dialog' ) );

			$.post( post.ajaxurl, data, function(response) {
				//console.log('Got this from the server: ' + response);
			});
			$dialog.addClass( 'success' );
			$dialog.text( post.success );
			setTimeout( function(){
				$dialog.fadeOut(function(){
					$dialog.remove();
				});
			}, 500 );
		};

		$(document).ready(function($){

			// remove popdown with a single click
			$('body').on('mouseup', function(e) {
				var $dialog = $( document.getElementById( 'missr_dialog' ) );
					if( $dialog )
					$dialog.fadeOut(function(){
						$dialog.remove();
					});
			});

			$( 'body' ).on( 'dblclick', function(e){
				selected = SELF.getSelectionText();
				var word = '';

				if ( '' !== selected ) {

					var $dialog = $( document.getElementById( 'missr_dialog' ) );
					$dialog.remove();

					// Retrieve cursor position
					xposition = e.pageX + 35;
					yposition = e.pageY - 10;

					var firstWord = selected.split(' ');
					word = firstWord[0];

					// make sure that misspelling gets submitted for the appropriate post
					if( post.post_id && !post.is_singular ) {
						post.post_id = null;
					}

					if( !post.post_id ) {
						SELF.findPostId( SELF.selectionObj.anchorNode.parentElement );
					}

					// Show popdown to report misspelling only when post id is defined
					if( undefined !== post.post_id && post.post_id ) {
						$( 'body' ).append($('<div id="missr_dialog" onclick="MisspellingReporter.missrClicked(this);" style="top:'+yposition+'px; left:'+xposition+'px;">' + post.click_to_report + '</div>').attr('data-word', word));
					}
				}

			});

		});
	};

	window.MisspellingReporter = new MisspellingReporter();

} )( window, jQuery );
