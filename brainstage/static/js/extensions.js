(function($) {
	var buildQueryURL = function( container, query ) {
		if( container ) {
			var identifier = container.getViewIdentifier();
			if( identifier )
				query = identifier.toLowerCase() +'/'+ query;
		}
		return query;
	};

	$.brainstageAPI = function( query, data, successFn ) {
		return $.getJSON( 'api.php/'+ query, data, successFn );
	};
	$.fn.brainstageAPI = function( query, data, successFn ) {
		query = buildQueryURL( this, query );
		return $.brainstageAPI( query, data, successFn );
	};
	
	$.brainstageAPIPost = function( query, data, successFn ) {
		return $.post( 'api.php/'+ query, data, successFn, 'json' );
	};
	$.fn.brainstageAPIPost = function( query, data, successFn ) {
		query = buildQueryURL( this, query );
		return $.brainstageAPIPost( query, data, successFn );
	};
}(jQuery));


(function($) {
	$.fn.getViewContainer = function(findMajorContainer) {
		if( findMajorContainer === undefined )
			findMajorContainer = false;
		var container = this;
		var view = container.closest('[data-identifier]');
		if( !view )
			view = container.closest('#main > div');
		return view;
	};
	
	$.fn.getViewIdentifier = function() {
		var container = this.getViewContainer(false);
		if( container ) {
			var identifier = container.attr('data-identifier');
			if( identifier )
				return identifier;
		}
		return undefined;
	};
}(jQuery));


(function($) {
	$.fn.getErrorSpace = function() {
		var container = this;
		var errorSpace = container.find('.error-space');
		if( errorSpace.length <= 0 ) {
			container.prepend( '<div class="error-space"></div>' );
			errorSpace = container.find('.error-space');
		}
		return errorSpace;
	};

	$.fn.insertErrorMessage = function( message, hideOnClick ) {
		var container = this;
		var errorSpace = container.getErrorSpace().html('');
		var errorBox = errorSpace.subordinate( 'div.alert alert-danger', message );
		if( hideOnClick || hideOnClick == undefined ) {
			errorBox.one('click', function(){
				$(this).remove();
			});
		}
	};
}(jQuery));


(function($) {
	$.fn.insertLoadingSpinner = function() {
		var element = this;
		element.append( '<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>' );
		if( element.hasClass('btn') ) {
			element.attr('disabled', 'disabled');
			element.addClass('active');
		}
	};

	$.fn.removeLoadingSpinner = function() {
		var element = this;
		element.children( 'span.glyphicon.glyphicon-refresh.glyphicon-refresh-animate' ).remove();
		if( element.hasClass('btn') ) {
			element.removeAttr('disabled');
			element.removeClass('active');
		}
	}
}(jQuery));


(function($) {
	$.dictionaryFirstKey = function( dictionary, onlyEnumerable ) {
		onlyEnumerable = onlyEnumerable == undefined ? true : onlyEnumerable;
		for( var prop in dictionary ) {
			if( !onlyEnumerable )
				return prop;
			if( dictionary.propertyIsEnumerable(prop) )
				return prop;
		}
	};

	$.dictionaryFirstItem = function( dictionary, onlyEnumerable ) {
		var firstKey = $.dictionaryFirstKey( dictionary, onlyEnumerable );
		return dictionary[ firstKey ];
	};
}(jQuery));


(function($) {
	var tableDefinition = 'table.table table-responsive table-hover table-striped';

	$.fn.buildTable = function( dictionary, columnsToIgnore ) {
		if( columnsToIgnore == undefined )
			columnsToIgnore = [];
		if( dictionary.constructor != Object )
			return;
		if( !$.isArray( dictionary ) || !$.isArray( $.dictionaryFirstItem( dictionary, false ) ) ) {
			dictionary = [dictionary];
		}

		var container = this;
		var table = container.subordinate( tableDefinition );
		if( dictionary.length && dictionary.length > 1 ) {
			buildTableHead( table.subordinate( 'thead > tr' ), dictionary, columnsToIgnore );
			fillTableBody( table.subordinate( 'tbody' ), dictionary, columnsToIgnore );
		} else {
			buildVerticalTable( table.subordinate( 'tbody' ), $.dictionaryFirstItem( dictionary ), columnsToIgnore );
		}
		return table;
	};

	function buildTableHead( tableRow, dictionary, columnsToIgnore ) {
		var row = $.dictionaryFirstItem( dictionary );
		for( var index in row ) {
			if( $.inArray( index, columnsToIgnore ) < 0 )
				tableRow.subordinate( 'th', index );
		}
		return tableRow.closest( 'table' );
	}

	function fillTableBody( table, dictionary, columnsToIgnore ) {
		for( var i in dictionary ) {
			var row = dictionary[i];
			var tableRow = table.subordinate( 'tr' );
			for( var index in row ) {
				var value = row[index];
				if( $.inArray( index, columnsToIgnore ) < 0 )
					tableRow.subordinate( 'td', value );
			}
		}
		return table.closest( 'table' );
	}

	function buildVerticalTable( table, dictionary, columnsToIgnore ) {
		var tableRow;
		for( var index in dictionary ) {
			var value = dictionary[index];
			if( $.inArray( index, columnsToIgnore ) < 0 ) {
				tableRow = table.subordinate( 'tr' );
				tableRow.subordinate( 'th', index );
				if( value instanceof Object ) {
					buildVerticalTable( tableRow.subordinate( 'td > '+ tableDefinition +' > tbody' ), value, columnsToIgnore )
						.removeClass( 'table-striped' )
						.addClass( 'table-bordered' );
				} else {
					tableRow.subordinate( 'td', value );
				}
			}
		}
		return table.closest( 'table' );
	}
}(jQuery));



function findSubtree( tree, nodeName ) {
	if( !(tree instanceof Array) )
		tree = [tree];

	for( var nodeKey in tree ) {
		var node = tree[nodeKey];
		if( node.name == nodeName ) {
			return node;
		}
	}

	for( var nodeKey in tree ) {
		var node = tree[nodeKey];
		if( node.children && node.children.length > 0 )
			var subtree = findSubtree( node.children, nodeName );
		if( subtree != null )
			return subtree;
	}

	return null;
}

function cutTree( tree, depth ) {
	if( !(tree instanceof Array) )
		tree = [tree];

	var cutted = [];
	for( var nodeKey in tree ) {
		var node = tree[nodeKey];
		if( node && ('children' in node) && node.children.length > 0 ) {
			if( depth > 0 )
				node.children = cutTree( node.children, depth-1 );
			else
				node.children = [];
		}
		cutted.push( node );
	}
	return cutted;
}



(function($) {
	$.fn.getSelector = function() {
	  var el = this[0];
	  if (!el.tagName) {
	    return '';
	  }

	  // If we have an ID, we're done; that uniquely identifies this element
	  var el$ = $(el);
	  var id = el$.attr('id');
	  if (id) {
	    return '#' + id;
	  }

	  var classNames = el$.attr('class');
	  var classSelector;
	  if (classNames) {
	    classSelector = '.' + $.trim(classNames).replace(/\s/gi, '.');
	  }

	  var selector;
	  var parent$ = el$.parent();
	  var siblings$ = parent$.children();
	  var needParent = false;
	  if (classSelector && siblings$.filter(classSelector).length == 1) {
	     // Classes are unique among siblings; use that
	     selector = classSelector;
	  } else if (siblings$.filter(el.tagName).length == 1) {
	     // Tag name is unique among siblings; use that
	     selector = el.tagName;
	  } else {
	     // Default to saying "nth child"
	     selector = ':nth(' + $(this).index() + ')';
	     needParent = true;
	  }

	  // Bypass ancestors that don't matter
	  if (!needParent) {
	    for (ancestor$ = parent$.parent();
	         ancestor$.length == 1 && ancestor$.find(selector).length == 1;
	         parent$ = ancestor$, ancestor$ = ancestor$.parent());
	    if (ancestor$.length == 0) {
	       return selector;
	    }
	  }

	  return parent$.getSelector() + ' > ' + selector;
	}
}(jQuery));

(function($) {
	$.fn.subordinate = function( element, attr, content ) {
		var container = this;

		if( typeof element == 'string' && element.substr(0, 1) != '<' ) {

			var insertedElement = container;
			var elements = element.split( '>' );
			for( var k in elements ) {
				var element = elements[ k ];
				insertedElement = insertedElement.subordinate( parseShortElementDefinition( element ) );
			}

		} else {

			var insertedElement = container.append( element ).children(':last');

		}

		if( attr != undefined ) {
			if( typeof attr == 'string' )
				insertedElement.append( attr );
			if( typeof attr == 'object' )
				insertedElement.attr( attr );
		}

		if( content != undefined )
			insertedElement.append( content );

		return insertedElement;

	};

	// parse short element definition
	function parseShortElementDefinition( shortDefinition ) {
		var symbols = {
			'name'	: ':',
			'value'	: '=',
			'type'	: '(',
			'id'	: '#',
			'class'	: '.'
		};
		var ignoreCharacters = [ ')' ];
		var isSelfClosing = false;
		var selfClosingContainers = [ 'img', 'input', 'br', 'link', 'meta' ];

		// removing characters that should be ignored
		for( var i in ignoreCharacters ) {
			var character = ignoreCharacters[i];
			shortDefinition = shortDefinition.replace( new RegExp( character.escapeRegExp(), 'g' ), '' );
		}
		// splitting each definition
		var defs = shortDefinition;
		for( var attr in symbols ) {
			var symbol = symbols[ attr ];
			defs = defs.replace( new RegExp( symbol.escapeRegExp(), 'g' ), '\\'+symbol );
		}
		defs = defs.split( '\\' );
		// parsing definitions & building element
		var containerAttributes = [];
		var containerName = shortDefinition;
		for( var k in defs ) {
			var def = defs[k];
			def = $.trim( def );
			var defSymbol = def.substr(0, 1);
			var defValue = def.substr(1);
			for( var attr in symbols ) {
				var symbol = symbols[ attr ];
				if( symbol == defSymbol ) {
					containerAttributes.push( attr +'="'+ defValue +'"' );
					containerName = containerName.replace( def, '' );
					break;
				}
			}
		}
		isSelfClosing = (containerName in selfClosingContainers);
		var containerAttributeDefinition = containerAttributes.join(' ');
		var containerDefinition = '<'+ $.trim( containerName +' '+ containerAttributeDefinition ) +'>'
								+ (isSelfClosing ? '' : '</'+ containerName +'>');

		return containerDefinition;
	}
}(jQuery));

(function($) {
	$.fn.colorToHex = function( rgb ) {
		// extract color values
		rgb = rgb.replace( /rgba?\((.*)\)/ig, '$1' );
		// get channel values
		rgb = rgb.split(',');

		return "#" + componentToHex( rgb[0] ) + componentToHex( rgb[1] ) + componentToHex( rgb[2] );
	};

	function componentToHex(c) {
		c = parseInt( $.trim( c ) );
		var hex = c.toString(16);
		return hex.length == 1 ? "0" + hex : hex;
	}

	$.fn.colorToRGB = function( hex ) {
		// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
		var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
		hex = hex.replace(shorthandRegex, function(m, r, g, b) {
			return r + r + g + g + b + b;
		});

		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
	};
}(jQuery));

/*	Flower Graph	*/
(function($) {
	$.fn.buildFlowerGraph = function( treeData, options ) {
		var container = this;
		var containerElement = container.get(0);
    	var containerSelector = container.getSelector();

    	// Default options
    	options = $.extend({
	    	width: container.width(),
	    	height: container.height(),
			collisionAlpha: 0.2,
			linkDistance: 180,
			zoomScaleExtent: [0.5, 3],
			showTitle: false,
			curvedLinks: false,
			initialZoom: 0.8,
			colors: {
				"0": "#00A7FF",
			},
			onRendered: function(){},
			onClick: function(){},
			onMouseOver: function(){},
			onMouseOut: function(){}
    	}, options);

		var indexCounter = 1;
		function convertDatasource(tree, parentId, depthLevel) {
			if( !(tree instanceof Array) )
				tree = [tree];
			if( depthLevel == undefined )
				depthLevel = 0;

			var nodes = [];
			var links = [];

			for( var key in tree ) {
				var nodeData = tree[key];
		  	var node = {
			  	id: indexCounter,
			  	text: nodeData.name,
			  	size: 1,
			  	cluster: (nodeData.nodeType ? nodeData.nodeType : depthLevel)
		  	};
		  	indexCounter++;

		  	if( nodeData.children && typeof(nodeData.children) === 'object' && nodeData.children !== null ) {
			  	var subtree = convertDatasource(nodeData.children, node.id, depthLevel+1);
			  	for( var key in subtree.nodes ) {
			  		var subnode = subtree.nodes[key];
				  	if( subnode instanceof Object )
			  			nodes.push( subnode );
			  	}
			  	for( var key in subtree.links ) {
			  		var sublink = subtree.links[key];
			  		links.push( sublink );
			  	}
			  	node.size = Math.ceil(subtree.nodes.length / 10);
		  	}

		  	if( node instanceof Object )
			  	nodes.push( node );
		  	if( parentId !== undefined )
			  	links.push( [parentId, node.id] );
			}

			return {nodes: nodes, links: links};
		}

		treeData = convertDatasource(treeData);
		var graph = new Insights(containerElement, treeData.nodes, treeData.links, options)
		//          .filter({ cluster: 0, size: [500, null] })
		  .zoom( options.initialZoom )
		//          .focus({ text: "color" }, { in: 1 })
		  .center()
		  .render();

		graph.on('rendered', function() {
			options.onRendered(graph);
		})

		graph.on("node:click", function(d) {
			options.onClick(graph, d);
		});

		graph.on("node:mouseover", function(d, offset) {
			options.onMouseOver(graph, d, offset);
		});

		graph.on("node:mouseout", function(d, offset) {
			options.onMouseOut(graph, d, offset);
		});

		return {graphObject: graph, options: options};

	};
}(jQuery));



String.prototype.addSlashes = function() {
	return this.replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

String.prototype.escapeRegExp = function() {
	return this.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
}

String.prototype.htmlEntities = function() {
    return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}