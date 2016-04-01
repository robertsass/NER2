$(document).ready(function(){

	var container = $('#main #Media');

	var table = container.find('table tbody');

	table.buildMediaList();

	container.on('navItemDoubleClicked', function(){
		table.buildMediaList();
	});

	container.find('.addLanguage').click(function(){
		addLanguage( $(this), table );
	});

	container.on('click', '.removeFile', function(){
		removeFile( $(this), table );
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
				table.buildMediaList();
			},
			sending: function(){
				currentDropzone.showDropzoneUploadProgressbar( 0 );
			},
			complete: function(){
				currentDropzone.hideDropzoneUploadProgressbar();
			},
			totaluploadprogress: function(totalValue){
				currentDropzone.showDropzoneUploadProgressbar( totalValue );
			}
		});
	});

	container.find('.pagination').on('click', 'a', function(){
		$(this).closest('ul').find('.active').removeClass('active');
		$(this).closest('li').addClass('active');
		var index = parseInt( $(this).text() ) -1;
		table.buildMediaList( 20, index, function(){
		});
	});

});


(function($) {
	$.fn.buildMediaList = function( limit, start, successFn ) {
		var table = this;
		table.html('');
		fillFilesList( table, limit, start, successFn );
	};

	function fillFilesList( table, limit, start, successFn ) {
		var container = table.closest('table').parent();
		var data = {limit: parseInt(limit), start: parseInt(start)};
		container.brainstageAPI('list', data, function(json){
			var files = json.result;
			for( var i=0; i<files.length; i++ ) {
				var file = files[i];
				var row = table.subordinate( 'tr' ).attr( 'data-fileid', file.id );
				row.subordinate( 'td', file.title ? file.title : file.filename );
				var options = row.subordinate( 'td.options' );
				row.subordinate( 'td', file.uploadDate );
				row.subordinate( 'td', file.owner.name );
			//	options.subordinate( 'span.glyphicon glyphicon-circle-arrow-down' );
				options.subordinate( 'a' ).attr( 'href', './?f='+ file.id ).attr( 'target', '_blank' )
					.subordinate( 'span.openFile glyphicon glyphicon-share-alt' );
				var options = row.subordinate( 'td.options' );
				options.subordinate( 'span.removeFile glyphicon glyphicon-remove' );
			}
			if( successFn != undefined )
				successFn();
		});
	}
}(jQuery));


(function($) {
	$.fn.showMediaProgressbar = function( value, max, label ) {
		if( max == undefined )
			max = 100;
		var container = this.find('.progressbar-container');
		if( container.length <= 0 )
			container = this.subordinate( 'div.progressbar-container' );
		var progressbar = container.find('.progress-bar');
		if( progressbar.length <= 0 )
			progressbar = container.subordinate( 'div.progress > div.progress-bar progress-bar-striped active' )
				.attr('aria-valuenow', max)
				.attr('aria-valuemin', '0')
				.attr('aria-valuemax', max);
		if( value != undefined )
			progressbar.css('width', value +'%');
		if( label )
			progressbar.text( label );
	};

	$.fn.hideMediaProgressbar = function() {
		var container = this;
		var footer = container.find('.progressbar-container');
		if( footer.length > 0 )
			footer.remove();
	};
}(jQuery));


function removeFile( button, table, limit, start ) {
	var container = table.closest('#main > div');
	var fileId = button.closest('tr').attr('data-fileid');
	container.brainstageAPI('delete', {id: fileId}, function(json){
		if( json.success ) {
			button.closest('tr').remove(); //table.buildMediaList();
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}
