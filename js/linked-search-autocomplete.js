(function( $ ) {
	$(function() {
        if ($("#linked_notes").length > 0) {
            $("#linked_notes").sortable();
            $("#linked_notes").disableSelection();
        }

        jQuery.ui.autocomplete.prototype._resizeMenu = function () {
            var ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth());
        }

		var url = LinkedSearchAutocomplete.url + "?action=linked_note_search";
		$( "#linked_notes_search" ).autocomplete({
			source: url,
			delay: 500,
			minLength: 3,
            select: function( event, ui ) {
                var label = ui.item.label;
                var id = parseInt(ui.item.id);

                var elements = $("input[name='note_ids[]']");
                for (var i = 0; i < elements.length; i++) {
                    console.log(parseInt(elements[i].value), id);
                    if (parseInt(elements[i].value) === id) {
                        elements[i].checked = true;
                        
                        return;
                    }
                }

                var path = window.location.pathname;
                var link = path+'?post='+id+'&action=edit';
                link = link.replace('post-new', 'post');

                $("#linked_notes p").remove();
                var html = $("#linked_notes").html();

                var newItem = '<label><input type="checkbox" name="note_ids[]" value="'+id+'" checked="checked"/><a href="'+link+'">'+label+'</a></label>';
                $("#linked_notes").html(html + newItem);
            },
            response: function( event, ui ) {
                var urlParams = new URLSearchParams(window.location.search);
                var thisNoteId = parseInt(urlParams.get('post'));

                for (var i = ui.content.length - 1; i >= 0; i--) {
                    if (parseInt(ui.content[i].id) === thisNoteId) {
                        ui.content.splice(i, 1);

                        continue;
                    }

                    // Decode HTML attributes 
                    var elem = document.createElement('textarea');
                    elem.innerHTML = ui.content[i].label;
                    ui.content[i].label = elem.value;
                    elem.remove();
                }

                return ui;
            },
            close: function() {
                $("#linked_notes_search").val('');
            }
		});	

        $('#linked_notes').on('change', 'input', function() {
            $(this).parent().fadeOut('slow', function() {
                $(this).remove();
               
                var linkedNotes = $("#linked_notes");
                if (linkedNotes.html() == '') {
                    linkedNotes.html('<p>No Linked Notes</p>');
                }
            });
        });
	});
})( jQuery );
