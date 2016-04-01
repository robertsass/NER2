var treeData;
var currentlyShownNode;
var graphObject;
var graphHeight, graphWidth;

function showDetailPopup( graphObj, selectedNode ) {
	currentlyShownNode = selectedNode;
	graphObject = graphObj;
	var nodeId = selectedNode.id;
	var nodeName = selectedNode.text;
	var nodeCluster = selectedNode.cluster;

	var detailPopup = $('#detailPopup');
	detailPopup.attr( 'data-cluster', nodeCluster );
	detailPopup.find('h1').text( nodeName );
	detailPopup.slideDown();

	graphObject.opts.height = graphHeight - detailPopup.height() / 2;
	graphObject.zoom(1).center( selectedNode );
}

$('#detailPopup .shortDetails').click(function(){
  graphObject.focus( currentlyShownNode.id )
  	.zoom(1)
  	.center( currentlyShownNode )
  	.update();
});

$('#detailPopup .hideDetailsButton').on('click', function(){
  $('#detailPopup .hideDetailsButton').hide();
  $('#detailPopup .showDetailsButton').show();
  $('#detailPopup .fullDetails').slideUp(function(){
		var container = $(this).find('#subtree');
		container.html('');
  });
});

$('#detailPopup .showDetailsButton').on('click', function(){
  $('#detailPopup .hideDetailsButton').show();
  $('#detailPopup .showDetailsButton').hide();
  $('#detailPopup .fullDetails').slideDown(function(){
		var container = $(this).find('#subtree');
		var height = container.height();
		var width = container.width();
		var subtree = cutTree( findSubtree( treeData, currentlyShownNode.text ), 1 ).pop();
		container.buildTreeGraph( subtree, {duration: 500} );
  });
});


$.getJSON("flare.json", function(tree) {
	treeData = tree;
	setTimeout('showNextFilterbutton()', 2000);
	var flower = $('#visualization').buildFlowerGraph( tree, {
		width: $('#visualization').width() +100,
		height: $('#visualization').height() -50,
		onClick: function( graphObject, clickedNode ) {
			showDetailPopup( graphObject, clickedNode );
		}
	} );
	graphObject = flower.graphObject;
	graphHeight = flower.options.height;
	graphWidth = flower.options.width;
});


function showNextFilterbutton() {
	$('#filter li.hidden:first').show(function(){
		$(this).removeClass('hidden');
		setTimeout('showNextFilterbutton()', 3000);
	});
}

$('#filter li').click(function(){
	  $(this).toggleClass('inactive');

  var filterCluster = [];
  $('#filter ul li').each(function(){
  	if( !$(this).hasClass('inactive') )
	  	filterCluster.push( $(this).attr('data-filterkey') );
  });

  if( filterCluster.length == 0 ) {
	  $('#filter ul li').removeClass('inactive');
  	  graphObject.reset();
  }
  else {
  	console.log(filterCluster);
	  graphObject.filter({ cluster: filterCluster }).update();
  }
});