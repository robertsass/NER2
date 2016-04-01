$.Brainstage.registerPlugin('Plugins/Photos', function(Plugin){
	var container = Plugin.getView();
	var table = container.find('table tbody');

	container.splitView('list-albums', function(tableRow, album){
		var albumMeta = album.languages[ getLanguage() ];
		if( albumMeta == undefined )
			albumMeta = $.dictionaryFirstItem( album.languages );
		tableRow.subordinate( 'td', albumMeta.title );
		tableRow.subordinate( 'td', album.date );
	}, function(detailsContainer, album){
		var albumMeta = album.languages[ getLanguage() ];
		if( albumMeta == undefined )
			albumMeta = $.dictionaryFirstItem( album.languages );
		detailsContainer.find('.title h1').text( albumMeta.title );
		detailsContainer.find('[name="id"]').val( album.id );
		detailsContainer.find('[name="date"]').val( album.date );
		detailsContainer.find('.album-meta').hide();

		detailsContainer.find('div.input-group.date > input').each(function(){
			var instance = $(this).data('DateTimePicker');
			if( instance )
				instance.destroy();
			$(this).datetimepicker({
				locale: getLanguage(),
				useCurrent: false,
				sideBySide: true
			});
			instance = $(this).data('DateTimePicker');
			instance.format( localeDateFormat );
			instance.defaultDate( $(this).val() );
		});

		for( var language in album.languages ) {
			var meta = album.languages[ language ];
			var metaContainer = 	detailsContainer.find('.album-meta.language-'+ language);
			metaContainer.show();
			if( meta ) {
				metaContainer.find('[name="title['+ language +']"]').val( meta.title );
				metaContainer.find('[name="description['+ language +']"]').val( meta.description );
			}
		}
		refreshPhotoGrid( album.id );
	}, function(){
	}, function(json){
		container.trigger('refreshList');
		container.trigger('hideDetails');
	});

	container.on('navItemDoubleClicked', function(){
		container.trigger('refreshList');
	});

	container.on('refreshPhotoGrid', function(){
		refreshPhotoGrid( container.find('.details [name="id"]:first').val() );
	});


	container.find('select.selectize').each(function(){
		var selectizeInstance = $(this).selectize();
		$(this).data('selectizeInstance', selectizeInstance);
	});

	container.find('.dropzone').each(function(){
		var currentDropzone = $(this);
		currentDropzone.dropzone({
			previewsContainer: false,
			autoProcessQueue: true,
			dragover: function(){
				currentDropzone.addClass('hover');
			},
			dragleave: function(){
				currentDropzone.removeClass('hover');
			},
			drop: function(){
				currentDropzone.removeClass('hover');
			},
			success: function(){
				currentDropzone.trigger('refreshPhotoGrid');
			},
			sending: function(file, xhr, formData){
				currentDropzone.showMediaProgressbar( 0 );
			},
			complete: function(){
				currentDropzone.hideMediaProgressbar();
			},
			totaluploadprogress: function(totalValue){
				currentDropzone.showMediaProgressbar( totalValue );
			}
		});
	});

	container.find('div.input-group.date > input').each(function(){
		$(this).datetimepicker({
			locale: getLanguage(),
			useCurrent: false,
			format: $(this).attr('data-dateformat') ? $(this).attr('data-dateformat') : localeDateFormat
		});
	});


	container.on('click', '.removePhoto', function(){
		var detailsContainer = $(this).closest('form').closest('.row').find('.details');
		bootbox.dialog({
			title: "Foto aus dem Album löschen?",
			message: detailsContainer.find('[name="start"]').val(),
			buttons: {
				main: {
					label: "Abbrechen",
					className: "btn-default",
				},
				success: {
					label: "Löschen",
					className: "btn-danger",
					callback: function() {
						$.brainstageAPIPost( 'photos/remove-photo', {id: detailsContainer.find('[name="id"]').val()}, function(){
							detailsContainer.hide();
							detailsContainer.trigger('refreshList');
						})
					}
				}
			}
		});
	});


	$('.save-album').click(function(){
		var button = $(this);
		var modal = button.closest('.modal');
		button.insertLoadingSpinner();
		container.brainstageAPI( $(this).closest('form').attr('action'), {
			date: container.find('[name="date"]').val(),
		}, function(){
			button.removeLoadingSpinner();
			modal.modal('hide');
			modal.trigger('refreshList');
		} );
	});


	function refreshPhotoGrid( albumId ) {
		var grid = container.find('.details .grid').html('');
		container.brainstageAPI('list-photos', {id: albumId}, function(json){
			for( var index in json.result ) {
				var photo = json.result[ index ];
				buildPhotoGridCell( grid, photo );
			}
		});
	}

	function buildPhotoGridCell( grid, photo ) {
		var photoUrl = './?f='+ photo.id;
		var thumbnailUrl = photoUrl +'&size=300';
		var anchor = grid.subordinate( 'div.col-md-3 > a.thumbnail' );
		anchor.subordinate( 'img' ).attr('src', thumbnailUrl);
		anchor.attr('target', '_blank');
		anchor.attr('href', photoUrl);
		anchor.click(function(){
			showPhotoLightbox( photo, photoUrl, '1200x800' );
			return false;
		});
	}

	function showPhotoLightbox( photo, photoUrl, dimensions ) {
		var thumbnailUrl = photoUrl +'&size='+ dimensions;
		var modal = container.find('#lightboxModal:first');
		var imageFrame = modal.find('.imageFrame:first');
		var downloadButton = modal.find('a.download');
		var closeButton = modal.find('a.closeModal');
		var metaTable = modal.find('table');

		imageFrame.html('').subordinate( 'img', {src: thumbnailUrl} );
		downloadButton.attr('href', photoUrl +'&download');

		metaTable.find('.filename').text( photo.filename );
		metaTable.find('.owner').text( photo.ownerName );
		metaTable.find('.date').text( photo.uploadDate );
		metaTable.find('.filesize').text( photo.readableSize );

		closeButton.one('click', function(){
			modal.modal('hide');
		});

		modal.modal('show');
	}

});
