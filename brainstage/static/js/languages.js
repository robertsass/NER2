$(document).ready(function(){

	var container = $('#main #Languages');

	var table = container.find('table tbody');

	container.on('navItemDoubleClicked', function(){
		table.buildLanguagesTable();
	});

	container.find('.addLanguage').click(function(){
		addLanguage( $(this), table );
	});

});


(function($) {
	$.fn.buildLanguagesTable = function( successFn ) {
		var table = this;
		table.html('');
		fillLanguagesTable( table, successFn );
	};

	function fillLanguagesTable( table, successFn ) {
		var container = table.closest('table').parent();
		container.brainstageAPI('list', function(json){
			var languages = json.result;
			for( var i=0; i<languages.length; i++ ) {
				var lang = languages[i];
				var row = table.subordinate( 'tr' );
				row.subordinate( 'td', lang.name );
				row.subordinate( 'td', lang.locale );
				row.subordinate( 'td', lang.shortCode );
			}
			if( successFn != undefined )
				successFn();
		});
	}
}(jQuery));


function addLanguage( button, table ) {
	var container = table.closest('#main > div');
	var name = container.find('input[name="name"]').val();
	var locale = container.find('input[name="locale"]').val();
	if( !name || !locale )
		return;
	button.insertLoadingSpinner();
	container.find('.add-form .alert').remove();
	container.brainstageAPI('add', {name: name, locale: locale}, function(json){
		button.removeLoadingSpinner();
		if( json.success ) {
			container.find('input[name="name"]').val('');
			container.find('input[name="locale"]').val('');
			table.buildLanguagesTable();
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}