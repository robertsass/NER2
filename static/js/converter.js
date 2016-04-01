$.Brainstage.registerPlugin('Plugins/Converter', function(Plugin){
	var container = Plugin.getView();

	container.find('select.selectize').each(function(){
		$(this).initSelectize();
	});

	container.find('.dropzone').each(function(){
		var currentDropzone = $(this);
		currentDropzone.dropzone({
			previewsContainer: false,
			autoProcessQueue: true,
			uploadMultiple: true,
			parallelUploads: 10,
			dragover: function(){
				currentDropzone.addClass('hover');
			},
			dragleave: function(){
				currentDropzone.removeClass('hover');
			},
			drop: function(){
				currentDropzone.removeClass('hover');
			},
			success: function(file){
				var xhr = file.xhr;
				var json = $.parseJSON( xhr.response );
				var result = json.result;
				console.log( result );
				container.find('textarea[name="output"]').text( result.export );
			},
			sending: function(file, xhr, formData){
				currentDropzone.showDropzoneUploadProgressbar( 0 );
				formData.append('format', currentDropzone.find('select[name="format"]').val());
			},
			complete: function(){
				currentDropzone.hideDropzoneUploadProgressbar();
			},
			totaluploadprogress: function(totalValue){
				currentDropzone.showDropzoneUploadProgressbar( totalValue );
			}
		});
	});

});
