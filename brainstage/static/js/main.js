$(document).ready(function(){

	init_navigator();
//	init_cockpit();
	init_bootstrapFunctions();
	autobind_APIForms();
	init_dropzones();
	init_sourceReader();
	init_mobile();
	
	$.Brainstage.init();

});


function getLanguage() {
	return $('head meta[name="language"]').attr('content');
}


function init_navigator() {

	$('#navigator nav').on('click', 'li', function(){
		var clickedListItem = $(this);
		var selectedListItem = $('#navigator li.selected');
		var anchorLink = clickedListItem.find('a').attr('href');
		var accessPath = getAccessPath( anchorLink );
		var oldAccessPath = selectedListItem.length > 0 ? getAccessPath( selectedListItem.find('a').attr('href') ) : '';

		selectedListItem.removeClass('selected');
		clickedListItem.addClass('selected');
		location.hash = anchorLink;

		var selectedView = getViewByAccessPath( accessPath );
		if( selectedView.length == 1 ) {
			$('#main > .visible').removeClass('visible');
			selectedView.addClass('visible');
			selectedView.trigger('navItemClicked');
			if( accessPath != oldAccessPath )
				selectedView.trigger('appear');
		}
	}).on('dblclick', 'li', function(){
		var clickedListItem = $(this);
		var selectedListItem = $('#navigator li.selected');
		var accessPath = getAccessPath( clickedListItem.find('a').attr('href') );
		var oldAccessPath = selectedListItem.length > 0 ? getAccessPath( selectedListItem.find('a').attr('href') ) : '';
		var selectedView = getViewByAccessPath( accessPath );
		if( selectedView.length == 1 ) {
			selectedView.trigger('navItemDoubleClicked');
		}
	});

	if( location.hash.length > 1 && $('#navigator nav ul li a[href="#'+ getAccessPath( location.hash ) +'"]').size() > 0 )
		selectByAccessPath( getAccessPath( location.hash ) );
	else
		selectFirstNavigatorItem();

}

function getAccessPath( url ) {
	var accessPath = url;
	if( accessPath.substr(0, 1) == '#' )
		accessPath = url.substring(1);
//	if( accessPath.substr(0, 1) == '!' )
//		accessPath = accessPath.substring(1);
	return accessPath;
}

function getViewByAccessPath( accessPath ) {
	accessPath = accessPath.replace('/', '-');
	return $('#main #'+ accessPath);
}

function selectByAccessPath( accessPath ) {
	$('#navigator nav ul li a[href="#'+ accessPath +'"]').closest('li').click();
}

function selectFirstNavigatorItem() {
	$('#navigator nav ul:first li:first').click();
}



function init_cockpit() {
	var mousePosition = {x: 0, y: 0};

	if( documents == undefined ) {
		setTimeout('init_cockpit()', 500);
	} else {
		$('#cockpit #sitetree-graph:first').each(function(){
			var graphContainer = $(this);
			var tree = {id: 1, name: "/", children: documents};
			graphContainer.buildFlowerGraph(tree, {
				onRendered: function(graph) {
					graph.zoom(0.001, false);
					graphContainer.parent().find('.spinner').hide();
					graphContainer.show(function(){
						graph.zoom( 0.7 );
					});
				},
				onClick: function(graph, node) {
					console.log( JSON.stringify(node) );
					graph.zoom( 1.2 ).center( node );
				},
				onMouseOver: function(graph, node) {
					console.log( JSON.stringify(node) );
					showTooltip( node.text );
				},
				onMouseOut: function(graph, node) {
					$('#sitetree div.tooltip').remove();
				}
			});
		}).mousemove(function(event){
			mousePosition.x = event.pageX - $(this).offset().left;
			mousePosition.y = event.pageY - $(this).offset().top;
			var tooltip = $('#sitetree div.tooltip');
			tooltip.css({
				top: mousePosition.y - parseInt( tooltip.outerHeight() ) -20,
				left: mousePosition.x - parseInt( tooltip.outerWidth() )/2
			});
		});
	}

	function showTooltip( text ) {
		$('#sitetree div.tooltip').remove();
		$('#sitetree').subordinate('div.tooltip', text).css({
			top: mousePosition.y,
			left: mousePosition.x
		});
	}
}



function init_bootstrapFunctions() {
	$('[data-toggle="popover"]').popover();
	$('[data-toggle="tooltip"]').tooltip();
//	$('[data-toggle="modal"]').modal();
	$('ul.nav-tabs').each(function(){
		$(this).children('li').on('click', function(){
			var target = $(this).find('a').attr('data-target');
			$(target).addClass('active').trigger('appear');
		});
		if( $(this).children('li.active').size() == 0 ) {
			$(this).children('li:first').addClass('active').click();
		}
	});
}



function autobind_APIForms() {
	$('form.autobind-api').on('submit', function(event){
		var form = $(this);
		var url = form.attr('action');
		var method = form.attr('method').toLowerCase();
		var data = form.serialize();
		var successFn = function(){
			form.find('[type="submit"]').removeLoadingSpinner();
		};

		if( method == 'post' ) {
			form.brainstageAPIPost( url, data, successFn );
		} else {
			form.brainstageAPI( url, data, successFn )
		}
		form.find('[type="submit"]').insertLoadingSpinner();

		event.preventDefault();
		return false;
	});
}



function init_dropzones() {
/*
	Dropzone.autoDiscover = false;
	$('.dropzone').dropzone({
		previewsContainer: false,
		autoProcessQueue: true
	});
*/
}


(function($) {
	$.fn.showDropzoneUploadProgressbar = function( value, max, label ) {
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

	$.fn.hideDropzoneUploadProgressbar = function() {
		var container = this;
		var footer = container.find('.progressbar-container');
		if( footer.length > 0 )
			footer.remove();
	};
}(jQuery));



function init_sourceReader() {
	$('.source-reader').each(function(){
		setupSourceReader( $(this) );
	});
}

function setupSourceReader( container ) {
	var content = container.text();
	var selectedLine = container.attr('data-line');
	var isWritable = container.attr('data-writable') != undefined && container.attr('data-writable') == 'true';
	var showLineGutter = container.attr('data-show-lines') != undefined && container.attr('data-show-lines') == 'true';
	var syntaxMode = container.attr('data-syntax-mode');
	if( syntaxMode == undefined )
		syntaxMode = 'php';

	var editor = container.data('editorInstance');
	if( editor !== undefined )
		return editor;

	var id = container.attr('id');
	if( id == undefined ) {
		id = 'ace-'+ container.closest('[id]').attr('id');
		container.attr('id', id);
	}
	var editor = ace.edit(id);
	container.data('editorInstance', editor);

	editor.setShowPrintMargin(false);
	editor.setTheme('ace/theme/chrome_light');
	editor.getSession().setMode('ace/mode/'+ syntaxMode);
	editor.getSession().setUseWorker(false);
	editor.setValue( content, 1 );
	editor.renderer.setShowGutter( showLineGutter );
	editor.setReadOnly(!isWritable);
	if( selectedLine > 0 ) {
		editor.gotoLine( selectedLine, 0, false );
	//	editor.scrollToRow(selectedLine);
	//	editor.scrollToLine( selectedLine, true, true );
	}
}



(function($) {
	$.fn.initSelectize = function(options) {
		if( options === undefined )
			options = {};
		var instance = this.getSelectizeInstance();
		if( !instance ) {
			instance = this.selectize(options);
			this.data('selectizeInstance', instance);
		}
	};
	$.fn.getSelectizeInstance = function() {
		var instance = this.data('selectizeInstance');
		if( instance )
			instance = instance[0];
		if( instance )
			instance = instance.selectize;
		return instance ? instance : null;
	};
	$.fn.setSelectizeValue = function(value) {
		var element = this;
		var selectize = element.getSelectizeInstance();
		if( selectize )
			selectize.setValue(value);
		else if( element.is('input') )
			element.val(value);
	};
}(jQuery));

(function($) {
	$.fn.buildSplitView = function( params ) {
		var options = $.extend({}, $.fn.buildSplitView.defaults, params);
		var items;
		var container = this;
		var list = container.find('.list');
		var details = container.find('.details');
		var containers = {
			'listContainer': list,
			'detailsContainer': details,
			'mainContainer': container
		};

		function buildList( containers, list, options ) {
			var container = containers.mainContainer;
			var apiUrl = options.apiUrl;
			var buildListCellFn = options.buildListCellFn;
			var apiCallFinishedFn = options.apiCallFinishedFn;

			if( typeof(apiUrl) === 'function' )
				apiUrl = apiUrl(containers);
			if( apiUrl != undefined && buildListCellFn != undefined ) {
				var table = list.find('table tbody');
				items = [];
				table.brainstageAPI(apiUrl, function(json){
					if( items.length <= 0 ) {
						table.html('');
						items = json.result;
						for( var index in items ) {
							var item = items[index];
							var row = table.subordinate( 'tr', {'data-itemindex': index} );
							buildListCellFn( row, item );
						}
						if( typeof(apiCallFinishedFn) === 'function' )
							apiCallFinishedFn();
					}
				});
			}
		}

		list.on('click', 'tbody > tr', function(){
			if( !$(this).hasClass('selected') ) {
				var index = parseInt( $(this).attr('data-itemindex') );
				var item = items[ index ];
				$(this).closest('table').find('tr.selected').removeClass('selected');
				$(this).addClass('selected');
				details.trigger('showDetails', [item]);
				if( typeof(options.buildDetailsFn) === 'function' )
					options.buildDetailsFn( details, item );
			}
		});

		details.find('.section').on('click', 'h1, h2, h3, h4', function(){
			var section = $(this).closest('.section');
			section.toggleClass('expanded');
			section.find('.collapse').toggleClass('in');
		});

		details.on('click', '.saveDetails', function(){
			saveDetails( $(this), details, options.savedSuccessFn );
		});

		container.on('refreshList', function(){
			buildList( containers, list, options );
		}).on('hideDetails', function(){
			details.hide();
		}).on('showDetails', function(item){
			details.show();
		});

		if( options.refreshListOnNavItemDoubleClick ) {
			container.on('navItemDoubleClicked', function(){
				container.trigger('refreshList');
			});
		}

		if( options.initSelectize ) {
			container.find('select.selectize').each(function(){
				$(this).initSelectize();
			});
		}

		if( options.initDatePicker ) {
			container.find('div.input-group.date > input').each(function(){
				var value = $(this).val();
				var instance = $(this).data('DateTimePicker');
				var format = $(this).attr('data-dateformat');
				if( instance )
					instance.destroy();
				$(this).datetimepicker({
					locale: getLanguage(),
					useCurrent: false,
					sideBySide: true
				});
				instance = $(this).data('DateTimePicker');
				instance.format( format );
				instance.defaultDate( value );
			});
		}

		buildList( containers, list, options );
		
		return containers;
	};

	$.fn.buildSplitView.defaults = {
		apiUrl: 'list',
		buildListCellFn: null,
		buildDetailsFn: null,
		apiCallFinishedFn: null,
		savedSuccessFn: null,
		refreshListOnNavItemDoubleClick: true,
		initSelectize: true,
		initDatePicker: true
	};

	// Deprecated; downwards compatibility
	$.fn.splitView = function( apiUrl, buildListCellFn, buildDetailsFn, apiCallFinishedFn, savedSuccessFn ) {
		return this.buildSplitView({
			apiUrl: apiUrl,
			buildListCellFn: buildListCellFn,
			buildDetailsFn: buildDetailsFn,
			apiCallFinishedFn: apiCallFinishedFn,
			savedSuccessFn: savedSuccessFn
		});
	};
}(jQuery));


function saveDetails( button, detailsContainer, successFn ) {
	button.insertLoadingSpinner();
	var form = detailsContainer.find('form');
	if( form.length <= 0 )
		form = detailsContainer.closest('form');
	var url = form.attr('action');
	var array = form.serializeArray();
	var callback = function(json){
		button.removeLoadingSpinner();
		if( typeof(successFn) === 'function' )
			successFn(json);
	};
	form.brainstageAPIPost( url, array, callback );
}


(function($) {
	function Brainstage() {
		this._isReady = false;
		this._plugins = [];
	}
	
	Brainstage.prototype.isReady = function(){
		return this._isReady;
	};
	
	Brainstage.prototype.init = function(){
		this._isReady = true;
		for( var index in this._plugins ) {
			var Plugin = this._plugins[ index ];
			Plugin.init();
		}
	};
	
	Brainstage.prototype.addPlugin = function(Plugin){
		this._plugins.push( Plugin );
		if( this.isReady() )
			Plugin.init();
	};

	Brainstage.prototype.registerPlugin = function( pluginIdentifier, initFunction ) {
		var Plugin = new BrainstagePlugin( pluginIdentifier );
		Plugin.setInitFunction( initFunction );
		this.addPlugin( Plugin );
	};
	
	$.Brainstage = new Brainstage();

	$.fn.Brainstage = function() {
		return $.Brainstage;
	}
	
	function BrainstagePlugin( pluginIdentifier ) {
		this.identifier = pluginIdentifier;
		this.viewContainer = undefined;
		this.navigatorItem = undefined;
		this.navigatorItemBadge = undefined;
		this.badgeCount = 0;
		this.initFunction = undefined;
	//	this.init();
	}
	
	BrainstagePlugin.prototype.init = function() {
		try {
			this.viewContainer = $('#main').children('[data-identifier="'+ this.identifier +'"]');
			this.navigatorItem = $('#navigator').find('[data-identifier="'+ this.identifier +'"]');
			if( this.navigatorItem.length == 1 )
				this.navigatorItemBadge = this.navigatorItem.find('.badge');
			if( this.initFunction )
				this.initFunction( this );
		} catch(e) {
			console.log(e);
		}
	};
	
	BrainstagePlugin.prototype.setBadge = function(number) {
		this.badgeCount = Math.round( number );
		if( this.navigatorItemBadge )
			this.navigatorItemBadge.text( this.badgeCount );
		if( number === false || number === null )
			this.navigatorItemBadge.text( '' );
	};
	
	BrainstagePlugin.prototype.getBadge = function() {
		return this.badgeCount;
	};
	
	BrainstagePlugin.prototype.setInitFunction = function(initFunction) {
		if( this.initFunction == undefined && typeof(initFunction) === 'function' ) {
			this.initFunction = initFunction;
		//	this.init();
		}
	};
	
	BrainstagePlugin.prototype.getView = function() {
		return this.viewContainer;
	};
	
	BrainstagePlugin.prototype.getNavigatorItem = function() {
		return this.navigatorItem;
	};
}(jQuery));



function init_mobile() {
	$(document).on('flick', function(e) {
		if( e.orientation == 'horizontal' ) {
			if( e.direction == 1 ) {	// swipe right
				$('body').addClass('offcanvas-open');
			} else {	// swipe left
				$('body').removeClass('offcanvas-open');
			}
		}
	});
	
	$(document).on('navItemClicked', function(){
		$('body').removeClass('offcanvas-open');
	});
	
	$('#topbar .open-offcanvas').on('click', function(){
		$('body').addClass('offcanvas-open');
	});
}