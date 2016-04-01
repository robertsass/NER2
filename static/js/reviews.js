$.Brainstage.registerPlugin('Plugins/Reviews', function(Plugin){
	var container = Plugin.getView();
	var table = container.find('table tbody');

	container.brainstageAPI('badge_count', function(json){
		Plugin.setBadge( json.result );
	});

	container.buildSplitView({
		buildListCellFn:
			function(tableRow, item){
				tableRow.subordinate( 'td', item.title );
			},
		buildDetailsFn:
			function(detailsContainer, item){
				detailsContainer.find('.title h1').text( item.title );
				detailsContainer.find('[name="id"]').val( item.id );
				detailsContainer.find('[name="title"]').val( item.title );
				detailsContainer.find('[name="subtitle"]').val( item.subtitle );
				detailsContainer.find('[name="text"]').val( item.text );
			},
		apiCallFinishedFn:
			function(){},
		savedSuccessFn:
			function(){
				container.trigger('refreshList');
				container.trigger('hideDetails');
			}
	});

	container.on('click', '.save', function(){
		var button = $(this);
		var form = button.closest('form');
		if( form.length == 0 )
			form = button.closest('.modal').find('form:first');
		var modal = form.closest('.modal');
		if( modal.length == 0 )
			modal = form.find('.modal:first');
		var detailsContainer = form.closest('.details');
		button.insertLoadingSpinner();
		button.brainstageAPIPost( form.attr('action'), form.serializeArray(), function(){
			button.removeLoadingSpinner();
			button.trigger('refreshList');
			modal.modal('hide');
			modal.find('input').val('');
		} );
	});

	container.on('click', '.remove', function(){
		var detailsContainer = $(this).closest('.details');
		bootbox.dialog({
			title: "Film löschen?",
			message: detailsContainer.find('[name="title"]').val(),
			buttons: {
				main: {
					label: "Abbrechen",
					className: "btn-default",
				},
				success: {
					label: "Löschen",
					className: "btn-danger",
					callback: function() {
						detailsContainer.brainstageAPIPost( 'delete', {id: detailsContainer.find('[name="id"]').val()}, function(){
							detailsContainer.hide();
							detailsContainer.trigger('refreshList');
						})
					}
				}
			}
		});
	});

});
