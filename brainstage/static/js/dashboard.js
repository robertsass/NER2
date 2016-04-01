$.Brainstage.registerPlugin('Brainstage/Plugins/Dashboard', function(Plugin){
	var container = Plugin.getView();
	
	container.find('.widget').each(function(){
		var widget = $(this);
		var identifier = widget.attr('data-identifier');
		if( identifier ) {
			var menuItem = $('#navigator [data-identifier="'+ identifier +'"]');
			if( menuItem.length > 0 ) {
				widget.addClass('linked');
				widget.on('click', function(){
					menuItem.click();
				});
			}
		}
	});
});
