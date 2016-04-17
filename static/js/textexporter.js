$.Brainstage.registerPlugin('Plugins/TextExporter', function(Plugin){
	var container = Plugin.getView();
	var textarea = container.find('textarea[name="output"]');
	var markupCheckbox = container.find('input[name="markupTags"]');
	var wrapCheckbox = container.find('input[name="wrapSentences"]');
	var includeMarkup = markupCheckbox.is(':checked');
	var wrapSentences = wrapCheckbox.is(':checked');
	
	var getExport = function(){
		Plugin.getView().brainstageAPI('export', {includeMarkup: includeMarkup, wrapSentences: wrapSentences}, function(json){
			textarea.text( json.result );
		});
	};
	
	markupCheckbox.on('change', function(){
		includeMarkup = $(this).is(':checked');
		getExport();
	});
	
	wrapCheckbox.on('change', function(){
		wrapSentences = $(this).is(':checked');
		getExport();
	});
	
	getExport();

});