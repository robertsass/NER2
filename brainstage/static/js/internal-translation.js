var internalTranslationTable;

$(document).ready(function(){

var container = $('#main #InternalTranslation');
	var limit = 20;
	var start = 0;

	var table = container.find('#translationTable tbody');
	table.buildInternalTranslationTable( container.find('[name="language"]').val(), limit, start );

	container.find('select.selectize').removeClass('selectize').selectize({
		onChange: function(value){
			table.buildInternalTranslationTable( value, limit, start );
		}
	});

	container.on('navItemDoubleClicked', function(){
		refreshInternalTranslationTable( $(this).find('.refreshTranslationTable'), table, limit, start );
	});

	container.find('.pagination').on('click', 'a', function(){
		$(this).closest('ul').find('.active').removeClass('active');
		$(this).closest('li').addClass('active');
		var index = parseInt( $(this).text() ) -1;
		table.buildInternalTranslationTable( container.find('[name="language"]').val(), limit, index, function(){
		});
	});

	container.find('.saveTranslations').click(function(){
		saveInternalTranslationTable( $(this), table );
	});

	container.find('.machineTranslations').click(function(){
		fillInternalMachineTranslations( $(this), table );
	});

	container.find('.cleanTranslationTable').click(function(){
		cleanInternalTranslationTable( $(this), table );
	});

	container.find('.refreshTranslationTable').click(function(){
		refreshInternalTranslationTable( $(this), table, limit, start );
	});

	container.find('.removeSelectedTranslations').click(function(){
		removeSelectedInternalTranslations( $(this), table, limit, start );
	});

	container.find('.enableDeletion').click(function(){
		$(this).addClass('active');
		$(this).one('mouseleave', function(){
		$(this).removeClass('active');
			container.addClass('editing');
		});
	});

});


(function($) {
	$.fn.buildInternalTranslationTable = function( language, limit, start, successFn ) {
		var table = this;
		table.html('');
		fillInternalTranslationTable( table, language, limit, start, successFn );
	};

	function fillInternalTranslationTable( table, language, limit, start, successFn ) {
		var container = table.closest('#main > div');
		var isEditable = table.closest('table').hasClass('editable');
		var data = {language: language, limit: parseInt(limit), start: parseInt(start)};
		container.brainstageAPI('list', data, function(json){
			var translations = json.result;
			internalTranslationTable = translations;
			for( var i=0; i<translations.length; i++ ) {
				var translation = translations[i];
				var row = table.subordinate( 'tr' );
				var keyColumn = row.subordinate( 'td.form-group' );
				if( isEditable )
					keyColumn.subordinate( 'input.checkbox-inline(checkbox):selectedTranslations='+ translation.id );
				keyColumn.append( translation.key );
				if( translation.comment && translation.comment.length > 0 )
					keyColumn.subordinate( 'span.comment', translation.comment );
				else
					keyColumn.addClass('commentless');
				if( isEditable ) {
					row.subordinate( 'td > input.form-control(text)' )
						.attr( 'name', translation.id )
						.attr( 'value', translation.translation )
						.attr( 'placeholder', translation.key );
				}
				else
					row.subordinate( 'td', translation.translation );
			}
			if( successFn != undefined )
				successFn();
		});
	}
}(jQuery));


function saveInternalTranslationTable( button, table ) {
	button.insertLoadingSpinner();
	var container = table.closest('#main > div');
	var language = container.find('[name="language"]').val();
	var data = [];
	for( var i=0; i<internalTranslationTable.length; i++ ) {
		var id = internalTranslationTable[i].id;
		var translation = table.find('input[name="'+ id +'"]').val();
		data.push({id: id, translation: translation});
	}
	container.brainstageAPIPost('save', {language: language, translations: data}, function(json){
		button.removeLoadingSpinner();
		var container = table.closest('#main > div');
		if( json.success ) {
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}


function fillInternalMachineTranslations( button, table ) {
	button.insertLoadingSpinner();
	var container = table.closest('#main > div');
	var language = container.find('[name="language"]').val();
	var keys = [];
	table.find('input[type="text"]').each(function(){
		var value = $(this).val();
		var placeholder = $(this).attr('placeholder');
		if( value.length <= 0 )
			keys.push( placeholder );
	});
	container.brainstageAPI('machine_translation', {language: language, keys: keys}, function(json){
		button.removeLoadingSpinner();
		var container = table.closest('#main > div');
		if( json.success ) {
			var translations = json.result;
			for( var key in translations ) {
				var translation = translations[key];
				table.find('input[type="text"][placeholder="'+ key +'"]').val( translation ).addClass('machine-translated');
			}
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}


function refreshInternalTranslationTable( button, table, limit, start ) {
	button.insertLoadingSpinner();
	var container = table.closest('#main > div');
	container.removeClass('editing');
	table.buildInternalTranslationTable( container.find('[name="language"]').val(), limit, start, function(){
	 	button.removeLoadingSpinner();
	} );
}


function cleanInternalTranslationTable( button, table ) {
	var container = table.closest('#main > div');
	button.insertLoadingSpinner();
	container.brainstageAPI('clean', function(json){
		button.removeLoadingSpinner();
		if( json.success ) {
			container.removeClass('editing');
			table.find('tr').remove();
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}


function removeSelectedInternalTranslations( button, table, limit, start ) {
	var container = table.closest('#main > div');
	button.insertLoadingSpinner();
	var selectedTranslations = [];
	table.find( 'input[name="selectedTranslations"]:checked' ).each(function(){
		selectedTranslations.push( $(this).val() );
	});
	container.brainstageAPI('remove', {ids: selectedTranslations}, function(json){
		button.removeLoadingSpinner();
		if( json.success ) {
			refreshTranslationTable( button, table, limit, start );
		}
		else {
			container.insertErrorMessage( json.error );
		}
	});
}
