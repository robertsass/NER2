$.Brainstage.registerPlugin('Plugins/Assistant', function(Plugin){
	var container = Plugin.getView();
	var reader = container.find('.reader');
	var titleContainer = reader.find('h1');
	var textContainer = reader.find('.plaintext');

	var Highlighter = textContainer.textHighlighter().getHighlighter();

	textContainer.on('textChanged', function(){
		Highlighter.find( titleContainer.find('input[name="title"]').val() );
	});

	container.on('change', 'input[name="decision"]', function(){
		var value = container.find('input[name="decision"]:checked').val();
		var form = container.find('form').removeClass('accepting').removeClass('discarding');
		if( value )
			form.addClass( value == 'accept' ? 'accepting' : 'discarding');
	});

	container.on('click', '.undo-highlighting', function(){
		var button = $(this);
		var lastElement;
		var latestTimestamp = 0;
		textContainer.find('.highlighted').each(function(){
			if( $(this).attr('data-timestamp') > latestTimestamp ) {
				latestTimestamp = $(this).attr('data-timestamp');
				lastElement = $(this);
			}
		});
		if( lastElement )
			Highlighter.removeHighlights( lastElement.get(0) );
	});

	container.find('form').on('submit', function(){
		var form = $(this);
		var button = form.find('input[type="submit"]');
		var data = {};

		data['id'] = form.find('input[name="id"]').val();
		data['title'] = form.find('input[name="title"]').val();
		data['subtitle'] = form.find('input[name="subtitle"]').val();
		data['decision'] = form.find('input[name="decision"]:checked').val();
		data['text'] = textContainer.html();

		if( data['decision'] ) {
			button.insertLoadingSpinner();
			button.brainstageAPIPost( form.attr('action'), data, function(){
				button.removeLoadingSpinner();
				button.trigger('refreshList');
				form.find('input[name="decision"]').prop('checked', false).trigger('change').closest('.btn').removeClass('active');
			});
		}
		return false;
	});

	container.on('refreshList', function(){
		loadUnexaminedReview();
	});

	loadUnexaminedReview();

	function loadUnexaminedReview(){
		container.brainstageAPI('get', {}, function(json){
			var item = json.result;
			var form = container.find('form').removeClass('accepting').removeClass('discarding');
			form.find('input[name="id"]').val( item.id );
			form.find('input[name="title"]').val( item.title );
			form.find('.sourceUrl').text( item.sourceUrl ).attr( 'href', item.sourceUrl );
			textContainer.html( item.brokenPlainText ).trigger('textChanged');
		});
	};

});