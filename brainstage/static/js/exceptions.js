$(document).ready(function(){

	var container = $('#main #Exceptions');
	var table = container.find('#exceptionList tbody');
	table.buildExceptionList( 20, 0 );

	container.find('.refreshExceptions').click(function(){
		refreshExceptions( $(this), table );
	});

	container.find('.cleanExceptions').click(function(){
		cleanExceptions( $(this), table );
	});

	container.find('.pagination').on('click', 'a', function(){
		$(this).closest('ul').find('.active').removeClass('active');
		$(this).closest('li').addClass('active');
		var index = parseInt( $(this).text() ) -1;
		table.buildExceptionList( 20, index, function(){
		});
	});

	container.on('navItemClicked', function(){
		refreshExceptions( $(this).find('.refreshExceptions'), table );
	});

});


(function($) {
	$.fn.buildExceptionList = function( limit, start, successFn ) {
		var table = this;
		var container = table.closest('#main > div');
		table.html('');
		var data = {limit: parseInt(limit), start: parseInt(start)};
		container.brainstageAPI('list', data, function(json){
			fillExceptionList( table, json );
			buildPagination( table, json.result.pages, (start+1) );
			if( successFn != undefined )
				successFn();
		});
	};

	function fillExceptionList( table, json ) {
		var container = table.closest('#main > div');
		var modalSpace = getModalSpace( container ).html('');
		var exceptions = json.result.list;
		for( var i=0; i<exceptions.length; i++ ) {
			var exception = exceptions[i];
			buildModal( modalSpace, exception );
			var file = exception.shortFilePath ? exception.shortFilePath : exception.file;
			var date = exception.readableDate ? exception.readableDate : exception.timestamp;
			var modalAttr = {
				'data-toggle': 'modal',
				'data-trigger': 'click',
				'data-target': '#exceptionModal'+ exception.id
			};
			var row = table.subordinate( 'tr' ).attr( modalAttr );
			row.subordinate( 'td', date );
			row.subordinate( 'td', file +' ('+ exception.line +')' );
			row.subordinate( 'td', exception.title );
		}
	}

	function getModalSpace( container ) {
		var selector = 'div.modal-space';
		var modalSpace = container.children(selector);
		if( modalSpace == undefined || modalSpace.length < 1 )
			modalSpace = container.subordinate(selector);
		return modalSpace;
	}

	function buildPagination( table, numPages, start ) {
		var container = table.closest('#main > div');
		var pagination = container.find('ul.pagination');
		pagination.html('');
		for( var index=1; index <= numPages; index++ ) {
			pagination.subordinate( 'li'+ (index == start ? '.active' : '') +' > a', index );
		}
	}

	function buildModal( container, exception ) {
		var file = exception.shortFilePath ? exception.shortFilePath : exception.file;
		var date = exception.readableDate ? exception.readableDate : exception.timestamp;

		var modal = container.subordinate( 'div.modal fade#exceptionModal'+ exception.id +' > div.modal-dialog modal-lg > div.modal-content' );
		var modalHeader = modal.subordinate( 'div.modal-header' );
		var modalBody = modal.subordinate( 'div.modal-body' );

		modalHeader.subordinate( 'button(button).close' ).attr( 'data-dismiss', 'modal' )
			.subordinate( 'span', "&times;" ).attr( 'aria-hidden', 'true' );
		modalHeader.subordinate( 'h3.modal-title', exception.title );

		modalBody.subordinate( 'p > b', file +' ('+ exception.line +')' );
		if( exception.title != exception.text )
			modalBody.subordinate( 'div', exception.text.replace( /\n/g, '<br />' ) );

		var tabSection = modalBody.subordinate( 'div.section' );
		var tabList = tabSection.subordinate( 'ul.nav nav-tabs' );
		var tabContent = tabSection.subordinate( 'div.tab-content' );

		try {
			tabList.subordinate( 'li.active > a', "Query" ).attr( 'href', '#query'+exception.id ).attr( 'data-toggle', 'tab' );
			tabContent.subordinate( 'div.tab-pane active#query'+exception.id )
				.buildTable( $.parseJSON(exception.queryUrl) )
				.addClass('table-bordered');
		} catch(err) {}
		try {
			if( exception.userInfo ) {
				tabList.subordinate( 'li > a', "User" ).attr( 'href', '#user'+exception.id ).attr( 'data-toggle', 'tab' );
				tabContent.subordinate( 'div.tab-pane#user'+exception.id )
					.buildTable( $.parseJSON(exception.userInfo) )
					.addClass('table-bordered');
			}
		} catch(err) {}
		try {
			if( exception.sessionVariables ) {
				tabList.subordinate( 'li > a', "Session" ).attr( 'href', '#session'+exception.id ).attr( 'data-toggle', 'tab' );
				tabContent.subordinate( 'div.tab-pane#session'+exception.id )
					.buildTable( $.parseJSON(exception.sessionVariables) )
					.addClass('table-bordered');
			}
		} catch(err) {}
	}
}(jQuery));


function refreshExceptions( button, table ) {
	button.insertLoadingSpinner();
	table.buildExceptionList( 20, 0, function(){
	 	button.removeLoadingSpinner();
	} );
}


function cleanExceptions( button, table ) {
	var container = table.closest('#main > div');
	button.insertLoadingSpinner();
	container.brainstageAPI('clean', function(json){
		button.removeLoadingSpinner();
		if( json.success ) {
			table.find('tr').remove();
		}
	});
}
