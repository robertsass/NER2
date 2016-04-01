_language = 'de';
_defaultEditorType = 'simple';


$(document).ready(function(){

	var container = $('#main #Documents');
	container.setupDocuments();

});

(function($) {
	var container;
	var documents = undefined, templates = undefined, openDocuments = [], openDocumentsLanguages = {};
	var deepestLevel = 0;


	$.fn.setupDocuments = function() {
		container = this;
		init_navigator();
		init_languageSelector( container );
		init_documentBrowser( container );

		container.on('appear', function(){
			// alert("Welcome back :)");
		}).on('navItemClicked', function(){
			// alert("Yes? I'm still here.");
		}).on('navItemDoubleClicked', function(){
			reloadDocumentsBrowser();
		});

		container.find('select.selectize').removeClass('selectize').selectize();
	};


	function init_navigator() {

		$('#navigator #open_tabs').on('click', '.close-button', function() {
			var parts = $(this).closest('li').attr('data-document').split('-');
			var documentId = parts[0];
			var language = parts[1];
			closeDocument( documentId, language );
		});

	}


	function init_languageSelector( container ) {

		var selector = container.find('select.languageSelector');
		if( selector.hasClass('selectize') ) {
			selector.removeClass('selectize').selectize({
				onChange: function(value){
					_language = value;
					reloadDocumentsBrowser();
				},
			});
			_language = selector.val();
		}

	}


	function init_documentBrowser( successFn ) {
		var document_clicked = function( element ) {
			var selectedDocument = element;
			var selectedDocumentLevel = selectedDocument.closest('ul').attr('data-level');
			var browser = selectedDocument.closest('.browser');
	//			var currentlySelectedDocument = $('#Documents .browser li.selected:last');
	//			var selectedLevelSelectedDocument = selectedDocument.closest('ul').find('li.selected');

			var selectedDocumentsId = selectedDocument.attr('id').substring(5);
	//			var currentlySelectedDocumentsId = currentlySelectedDocument.length == 0 ? undefined : currentlySelectedDocument.attr('id').substring(5);
			selectDocument( browser.parent(), selectedDocumentsId );
		};

		var document_doubleClicked = function( element ) {
			var selectedDocument = element;
			var selectedDocumentsId = selectedDocument.attr('id').substring(5);
			openDocument( selectedDocumentsId, container.find('select.languageSelector').val() );
		};

		var init_browserSorting = function( selector ) {
			$(selector).sortable({
				connectWith: selector,
				helper: 'clone',
				items: '.node',
				scroll: true,
				cursor: 'move',
				tolerance: 'pointer',
				update: function(event, ui) {
					var node = ui.item;
					var nodeId = parseInt( node.attr('id').substr(5) );
					var targetSubtree = event.target;
					var targetId = parseInt( $(targetSubtree).attr('id').substr(8) );
					if( nodeId == targetId )
						return $(selector).sortable('cancel');
					var targetItems = [];
					$(targetSubtree).find('li.node').each(function(){
						targetItems.push( parseInt( $(this).attr('id').substr(5) ) );
					});

				//	console.log( nodeId, targetId, targetItems );
					$.brainstageAPI( 'brainstage/plugins/documents/move', {node: nodeId, targetNode: targetId, targetItems: targetItems.join(',')}, function(response){
					//	console.log(response);
					} );
				}
			});
		};

		if( templates == undefined ) {
			loadTemplatesList();
		}
		if( documents == undefined ) {
			$.brainstageAPI('brainstage/plugins/documents/list', {language: _language}, function(json) {
				if( json.success ) {
					documents = json['result'];
					init_documentBrowser( successFn );
				}
			});
		} else {
			var browser = $('#Documents .browser');
			var rootId = browser.attr('data-root');
			browser.html('').subordinate('li.col3 > ul.subtree#subtree-'+ rootId).attr('data-level', '0');
			browser.find('ul:last').buildDocumentBrowser( documents, 1, browser );

			browser.find('ul li.node')
				.on('click', function(){
					document_clicked( $(this) );
				})
				.on('dblclick', function(){
					document_doubleClicked( $(this) );
				});

			browser.off('click', '.open-document').on('click', '.open-document', function(){
				var selectedDocument = $(this).closest('.nodeinfo');
				var selectedDocumentsId = selectedDocument.attr('id').substring(9);
				openDocument( selectedDocumentsId, container.find('select.languageSelector').val() );
			});

			browser.off('click', '.delete-document').on('click', '.delete-document', function(){
				var selectedDocument = $(this).closest('.nodeinfo');
				var selectedDocumentsId = selectedDocument.attr('id').substring(9);
				deleteDocument( selectedDocumentsId );
			});

			init_browserSorting('#Documents .browser .subtree');

			if( typeof(successFn) == 'function' )
				successFn();
		}
	}


	function loadTemplatesList() {
		$.brainstageAPI('brainstage/plugins/documents/templates', function(json) {
			if( json.success ) {
				templates = json['result'];
			}
		});
	}


	function reloadDocumentsBrowser( successFn ) {
		documents = undefined;
		deepestLevel = 0;
		init_documentBrowser( successFn );
	}



	$.fn.buildDocumentBrowser = function( subtree, level ) {
		if( level == undefined )
			level = 1;
		if( level > deepestLevel )
			deepestLevel = level;

		var container = this;
		var browser = container.closest('.browser');

		for( index in subtree ) {
			var node = subtree[index];
			var nodeName = node.name === null ? '-' : node.name;
			var nodeElement = container.subordinate('li.node#node-'+ node.id, nodeName);
//			if( node.children.length > 0 ) {
				nodeElement.addClass('has-children').append('<span class="icon triangle-right"></span>');
				browser.append('<li class="col3"><ul class="subtree" id="subtree-'+ node.id +'" data-level="'+ level +'"></ul></li>');
				var subtreeContainer = browser.find('ul:last');
				subtreeContainer.buildDocumentBrowser( node.children, level+1 );
//			} else {
				subtreeContainer.parent().prepend( '<div class="nodeinfo" id="nodeinfo-'+ node.id +'"></div>');
				var infoContainer = subtreeContainer.parent().find('.nodeinfo');
				buildDocumentInfo( node, infoContainer );
//			}
		}
		if( browser.hasClass('editable') && (level > 1 || parseInt( browser.attr('data-root') ) > 0) )
			buildDocumentBrowserSubtreeOptions( container );
	};

	function buildDocumentBrowserSubtreeOptions( container ) {
		var subtreeId = container.closest('ul').attr('id').substring(8);
		var buttonNewDocument = container.subordinate('li.no-document > button.btn btn-default btn-xs', "Neues Dokument");
		buttonNewDocument.on('click', function(){
			newDocument( subtreeId );
		});
	}

	function buildDocumentInfo( document, container ) {
		var documentName = document.name === null ? '-' : document.name;
		var title = container.subordinate('div.title', documentName);
		var buttonGroup = container.subordinate( 'div' );
		buttonGroup.subordinate('button.btn btn-primary btn-xs open-document', "Bearbeiten");
		buttonGroup.subordinate('button.btn btn-default btn-xs delete-document', "Löschen");
	}


	function selectDocument( container, nodeId ) {
		var browser = container.find('.browser');
		var selectedDocument = browser.find('li#node-'+ nodeId);
		var selectedDocumentLevel = selectedDocument.closest('ul').attr('data-level');

		for( var level = selectedDocumentLevel; level <= deepestLevel; level++ ) {
			$('ul[data-level="'+ level +'"] li.selected').each(function(){
				var documentId = $(this).attr('id').substring(5);
				$(this).removeClass('selected');
				$('ul#subtree-'+ documentId).closest('li').removeClass('visible');
			});
		}
		browser.find('li.selected.active').removeClass('active');
		browser.find('li.nodeinfo').removeClass('visible');

		selectedDocument.addClass('selected').addClass('active');
		if( selectedDocument.hasClass('has-children') )
			$('#Documents .browser ul#subtree-'+ nodeId).closest('li').addClass('visible');
		else
			$('#Documents .browser #nodeinfo-'+ nodeId).closest('li').addClass('visible');

/*
		var curSubtree = selectedDocument.closest('ul.subtree');
		while( curSubtree ) {
			var subtreeId = curSubtree.attr('id').substring(8);
			var prevColumn = curSubtree.closest('li').prev();
			if( prevColumn ) {
				prevColumn.addClass('visible');
				var curSubtreeNode = prevColumn.find('li#node-'+ subtreeId);
				curSubtreeNode.addClass('selected');
				curSubtree = curSubtreeNode.closest('ul.subtree');
			}
			else
				curSubtree = null;
		}
*/

		browser.animate({
			scrollLeft: browser.width()
		});
	}


	function getDocumentById( documentId, subtree ) {
		if( subtree == undefined )
			subtree = documents;
		documentId = parseInt( documentId );
		if( documentId > 0 ) {
			for( var i in subtree ) {
				var document = subtree[i];
				if( document.id == documentId )
					return document;
				if( document.children && document.children.length > 0 ) {
					var subdocument = getDocumentById( documentId, document.children );
					if( subdocument ) {
						if( !subdocument.parents )
							subdocument.parents = [];
						subdocument.parents.push( document );
						return subdocument;
					}
				}
			}
		}
		return null;
	}

	function buildAccessPath( document, language ) {
		return 'document-'+ document.id +(language != undefined ? '-'+language : '');
	}

	function openDocument( documentId, language ) {
		documentId = parseInt( documentId );
		var documentIdentifier = documentId +(language != undefined ? '-'+language : '');
		if( $.inArray( documentIdentifier, openDocuments ) >= 0 ) {
			selectByAccessPath( buildAccessPath( getDocumentById( documentId ), language ) );
			return;
		}
		openDocuments.push( documentIdentifier );
		openDocumentsLanguages[documentIdentifier] = language;
		buildOpenDocumentsList();
		buildDocumentEditor( documentId, language );
		selectByAccessPath( buildAccessPath( getDocumentById( documentId ), language ) );
	}

	function closeDocument( documentId, language ) {
		documentId = parseInt( documentId );
		var documentIdentifier = documentId +(language != undefined ? '-'+language : '');
		var inArray = $.inArray( documentIdentifier, openDocuments );
		if( inArray < 0 )
			return;
		openDocuments[inArray] = null;
		openDocumentsLanguages[documentIdentifier] = null;
		buildOpenDocumentsList();
		closeDocumentEditor( documentId );
		selectFirstNavigatorItem();
	}

	function newDocument( parentId ) {
		$.brainstageAPI( 'brainstage/plugins/documents/create', {parent: parentId, language: _language}, function(json){
			reloadDocumentsBrowser(function(){
				//openDocument( json.result.id, _language );
				//selectDocument( container, json.result.id );
			});
		});
	}

	function saveDocument( documentId, fireingElement ) {
		var documentId = parseInt( documentId );
		var container = fireingElement.closest('#main > div');
		var content = getDocumentEditorContent( container.find('.editor.active') );
		var name = container.find('input[name="name"]').val();
		var template = container.find('select[name="template"]').val();
		var pathComponent = container.find('input[name="pathcomponent"]').val();
		var accessibility = container.find('select[name="accessibility"]').val();
		var editor = container.find('.editor-switch input[name="editor"]:checked').val();
		var language = _language;
		showDocumentProgressbar( documentId, "Speichern..." );
		if( fireingElement != undefined )
			fireingElement.insertLoadingSpinner();
		$.brainstageAPIPost('brainstage/plugins/documents/save', {
			id: documentId,
			name: name,
			content: content,
			template: template,
			pathcomponent: pathComponent,
			accessibility: accessibility,
			editor: editor,
			language: language
		}, function(json){
			hideDocumentProgressbar( documentId );
			if( fireingElement != undefined )
				fireingElement.removeLoadingSpinner();
			if( !json.success )
				alert( json.result );
		});
	}

	function deleteDocument( documentId ) {
		var doc = getDocumentById( documentId, documents );
		bootbox.dialog({
			title: "Dokument löschen?",
			message: doc.name,
			buttons: {
				main: {
					label: "Abbrechen",
					className: "btn-default",
				},
				success: {
					label: "Löschen",
					className: "btn-danger",
					callback: function() {
						$.brainstageAPI( 'brainstage/plugins/documents/delete', {id: documentId}, function(json){
							if( json.success )
								reloadDocumentsBrowser();
						});
					}
				}
			}
		});
	}


	function buildOpenDocumentsList() {
		var constructDocumentPath = function( nodes ) {
			var names = [];
			for( var i in nodes ) {
				var node = nodes[i];
				names.push( node.name );
			}
			return names.join(' / ');
		}

		var list = $('#navigator #open_tabs');
		var container = $('#main');
		list.html('');
		for( var i in openDocuments ) {
			var documentId = openDocuments[i];
			if( documentId != null ) {
				var document = getDocumentById( documentId );
				if( document.parents )
					document.parents.push( document );
				var documentName = document.name === null ? '-' : document.name;
				var documentPath = document.parents ? constructDocumentPath( document.parents ) : documentName;
				var language = openDocumentsLanguages[ documentId ];
				var accessPath = buildAccessPath( document, language );
				var navItem = list.subordinate( 'li' ).attr('data-document', documentId);
				navItem.subordinate( 'a', documentName )
					.attr('href', '#'+ accessPath )
					.attr('title', documentPath);
				navItem.subordinate( 'span.close-button' );
			}
		}
	}


	function buildDocumentEditor( documentId ) {
		var document = getDocumentById( documentId );
		var viewContainer = $('#main').subordinate( 'div.colset document#'+ buildAccessPath( document, _language ) ).subordinate( 'form' );
		var contentHead = viewContainer.subordinate( 'div.content-head' );
		var container = viewContainer.subordinate( 'div.columns' );
		var mainArea = container.subordinate( 'div.col-4 > div.main-content' );
		var contentArea = mainArea.subordinate( 'div.content' );
		var sidebar = container.subordinate( 'div.col4 sidebar > div.sidebar-content' );
		buildDocumentEditorSidebar( sidebar, document );
		
/*
		var maxInputGroupWidth = 0;
		sidebar.find('.input-group').each(function(){
			if( $(this).width() > maxInputGroupWidth )
				maxInputGroupWidth = $(this).width();
		});
		console.log( sidebar.innerWidth(), maxInputGroupWidth );
		if( sidebar.innerWidth() < maxInputGroupWidth ) {
*/
			sidebar.closest('.sidebar').hover(function(){
				container.find('.col4').removeClass('col4').addClass('col8');
				container.find('.col-4').removeClass('col-4').addClass('col-8');
			}, function(){
				container.find('.col8').removeClass('col8').addClass('col4');
				container.find('.col-8').removeClass('col-8').addClass('col-4');
			});
//		}

		$.brainstageAPI('brainstage/plugins/documents/get?id='+ document.id).done(function(json){
			var documentDetails = json.result;
			var version = documentDetails.versions[ _language ];
			var editorType = version ? version.editorType : _defaultEditorType;
			var simpleEditor = contentArea.subordinate( 'div.editor simple '+ (editorType == 'simple' ? 'active' : '') );
			var sourceEditor = contentArea.subordinate( 'div.editor source '+ (editorType == 'source' ? 'active' : '') );
			setupSimpleEditor( simpleEditor, documentDetails );
			setupSourceEditor( sourceEditor, documentDetails );
			buildDocumentEditorContentHead( contentHead, documentDetails );
		}).error(function(e,error){ console.log(error); });
	}

	function closeDocumentEditor( documentId ) {
		var document = getDocumentById( documentId );
		var container = $('#main div#'+ buildAccessPath( document ) );
		container.remove();
		$('.pen-menu').hide();
		destroySourceEditor( document );
	}


	function setupSourceEditor( container, document ) {
		var version = document.versions[ _language ];
		var content = version ? version.content : '';
		container.text( content );
		var editor = getSourceEditor( container );
		editor.setShowPrintMargin(false);
		editor.setTheme('ace/theme/chrome_light');
		editor.getSession().setMode('ace/mode/html');
		editor.getSession().setUseWorker(false);
	}

	function getSourceEditor( container ) {
		var editor = container.data('editorInstance');
		if( editor !== undefined )
			return editor;

		var id = container.attr('id');
		if( id == undefined ) {
			id = 'ace-'+ container.closest('.document').attr('id');
			container.attr('id', id);
		}
		var editor = ace.edit(id);
		container.data('editorInstance', editor);
		return editor;
	}

	function destroySourceEditor( document ) {
	}


	function setupSimpleEditor( container, document, focusEditor ) {
		var version = document.versions[ _language ];
		var content = version ? version.content : '';
		var editor = container.data('editorInstance');

		if( editor !== undefined )
			return;

		container.html( encodeSimpleEditorContent( content ) );

		var pen_options = {
			editor: container.get(0),
			stay: true,
			stayMsg: "ACHTUNG: Änderungen gehen verloren!"
		};
		var editor = new Pen(pen_options);
		container.data('editorInstance', editor);

		if( focusEditor || focusEditor == undefined )
			container.focus();
	}

	function encodeSimpleEditorContent( content ) {
		var stripTags = ['script', 'style'];
		for( var i=0; i < stripTags.length; i++ ) {
			var tag = stripTags[i];
			var expr;
			expr = new RegExp( '<'+ tag, 'gi' );
			content = content.replace(expr, '<!--//stripped-'+ tag);
			expr = new RegExp( '<\s*/\s*'+ tag +'\s*>', 'gi' );
			content = content.replace(expr, '/stripped-'+ tag +'//-->');
		}
		return content;
	}

	function decodeSimpleEditorContent( content ) {
		var stripTags = ['script', 'style'];
		for( var i=0; i < stripTags.length; i++ ) {
			var tag = stripTags[i];
			var expr;
			expr = new RegExp( '<!--//stripped-'+ tag, 'gi' );
			content = content.replace(expr, '<'+ tag);
			expr = new RegExp( '/stripped-'+ tag +'//-->', 'gi' );
			content = content.replace(expr, '</'+ tag +'>');
		}
		return content;
	}


	function buildDocumentEditorSidebar( container, document ) {
		buildTemplateSelector( container, document );
		buildPathComponentName( container, document );
		buildTagSelector( container, document );
		buildAccessibilitySelector( container, document );
	}

	function buildTemplateSelector( container, document ) {
		var section = container.subordinate( 'div.section' );
		section.subordinate( 'span.title', "Template" );
		var templateSelector = section.subordinate( 'select:template' );
		var curTemplates = templates;
		if( document.templateName != null && $.inArray( document.templateName, curTemplates ) < 0 )
			curTemplates.push( document.templateName );

		for( var index in curTemplates ) {
			var templateName = curTemplates[ index ];
			var option = templateSelector.subordinate( 'option='+ templateName, templateName );
			if( templateName == document.templateName )
				option.attr( 'selected', 'true' );
		}
		templateSelector.selectize();
	}

	function buildPathComponentName( container, document ) {
		var path = document.path[ _language ];
		var section = container.subordinate( 'div.section pathcomponent' );
		section.subordinate( 'span.title', "Path component" );
		var inputGroup = section.subordinate( 'label > div.input-group' );
		inputGroup.subordinate( 'span.input-group-addon', path.origin );
		var nameInput = inputGroup.subordinate( 'input(text):pathcomponent.form-control' ).attr( 'placeholder', "Path component name" ).val( path.component );
	}

	function buildTagSelector( container, document ) {
		var section = container.subordinate( 'div.section' );
		section.subordinate( 'span.title', "Tags" );
		var tagInput = section.subordinate( 'input(text)' );
		for( var index in document.tags ) {
			var tagName = document.tags[ index ];
			tagInput.val( tagInput.val() +','+ tagName );
		}
		tagInput.selectize({
			create: true,
			persist: false
		});
	}

	function buildAccessibilitySelector( container, document ) {
		var section = container.subordinate( 'div.section' );
		section.subordinate( 'span.title', "Accessibility" );
		var accessibilitySelector = section.subordinate( 'select:accessibility' );
		var options = ['public', 'hidden', 'private'];
		for( var index in options ) {
			var optionName = options[ index ];
			var option = accessibilitySelector.subordinate( 'option='+ optionName, optionName );
			if( optionName == document.accessibility )
				option.attr( 'selected', 'true' );
		}
		accessibilitySelector.selectize();
	}

	function addDocumentEditorSidebarItem( container ) {
		var item = container.subordinate( 'div' );
		return item;
	}

	function addDocumentEditorSidebarButton( container, label ) {
		var item = addDocumentEditorSidebarItem( container );
		var button = item.subordinate( 'button.btn btn-default', label );
		button.attr('type', 'button');
		return button;
	}

	function buildDocumentEditorContentHead( container, document ) {
		container = container.subordinate( 'div.columns' );
		var leftContainer = container.subordinate( 'div.col-4' );
		var rightContainer = container.subordinate( 'div.col4' );

		var leftContainerGroup = leftContainer.subordinate( 'div.row' );

		var language = _language;
		var version = document.versions[ language ];
		var title = version ? version.name : '';
		var languageNames = document.languages;
		var languageName = languageNames ? languageNames[ language ] : language;

		var titleGroup = leftContainerGroup.subordinate( 'div.col-md-8 > div.input-group' );
		var languageButton = titleGroup.subordinate( 'div.input-group-btn' );
		titleGroup.subordinate( 'input(text):name.form-control' ).attr( 'value', title ).attr( 'placeholder', "Titel" );
		languageButton.subordinate( 'button.btn btn-default dropdown-toggle', languageName +' ' ).attr('data-toggle', 'dropdown')
			.subordinate( 'span.caret' );
		var languageList = languageButton.subordinate( 'ul.dropdown-menu' );
		for( var languageCode in languageNames ) {
			var curLanguage = languageNames[ languageCode ];
			var option = languageList.subordinate( 'li > a', curLanguage );
		//	if( language == _language )
		//		option.attr( 'selected', 'true' );
		}

		buildDocumentEditorEditorSwitch( leftContainerGroup.subordinate( 'div.col-md-4' ), version );

	/*
		if( version.parents instanceof Array ) {
			var breadcrumb = container.subordinate( 'ul.uk-breadcrumb' );
			for( var i=0; i < version.parents.length; i++ )
				breadcrumb.subordinate( 'li > a', version.parents[i] );
		}
	*/

		var saveButton = rightContainer.subordinate( 'div.row > div.col-md-12 > button(button).btn btn-primary save-document', "Speichern" );
		saveButton.on('click', function() {
			saveDocument( document.id, $(this) );
		});
	}

	function buildDocumentEditorEditorSwitch( container, version ) {
		var editorType = version ? version.editorType : _defaultEditorType;
		var editorSwitch = container.subordinate( 'div.btn-group editor-switch' ).attr('data-toggle', 'buttons');

		var radio = editorSwitch.subordinate( 'label.btn btn-default'+(editorType == 'simple' ? ' active' : ''), "Editor" )
			.subordinate( 'input(radio):editor=simple' );
		if( editorType == 'simple' )
			radio.attr('checked', 'true');

		radio = editorSwitch.subordinate( 'label.btn btn-default'+(editorType == 'source' ? ' active' : ''), "Quelltext" )
			.subordinate( 'input(radio):editor=source' );
		if( editorType == 'source' )
			radio.attr('checked', 'true');

		editorSwitch.on('click', '.btn', function(){
			documentEditorSwitchClicked( $(this) );
		});
	}

	function documentEditorSwitchClicked( button ) {
		var selectedEditorType = button.find('input').val();
		var activeEditor = button.closest('.document').find('.editor.active');
		activeEditor.removeClass('active');
		var selectedEditor = button.closest('.document').find('.editor.'+ selectedEditorType);
		selectedEditor.addClass('active');
		setDocumentEditorContent( selectedEditor, getDocumentEditorContent( activeEditor ) );
	}

	function getDocumentEditorContent( editorContainer ) {
		var content = editorContainer.html();
		if( editorContainer.hasClass('simple') )
			content = decodeSimpleEditorContent( content );
		else if( editorContainer.hasClass('source') )
			content = getSourceEditor( editorContainer ).getSession().getValue();
		return content;
	}

	function setDocumentEditorContent( editorContainer, content ) {
		if( editorContainer.hasClass('simple') )
			content = encodeSimpleEditorContent( content );
		else if( editorContainer.hasClass('source') ) {
			getSourceEditor( editorContainer ).getSession().setValue( content );
			return;
		}
		editorContainer.html( content );
	}

	function showDocumentProgressbar( documentId, label ) {
		var container = $('#main #document-'+ documentId).find('.main-content');
		var footer = container.find('.content-foot');
		if( footer.length <= 0 )
			footer = container.subordinate( 'div.content-foot' );
		var progressbar = footer.subordinate( 'div.progress > div.progress-bar progress-bar-striped active' )
			.attr('aria-valuenow', '100')
			.attr('aria-valuemin', '0')
			.attr('aria-valuemax', '100');
		if( label )
			progressbar.text( label );
	}

	function hideDocumentProgressbar( documentId ) {
		var container = $('#main #document-'+ documentId).find('.main-content');
		var footer = container.find('.content-foot');
		if( footer.length > 0 )
			footer.remove();
	}


}(jQuery));
