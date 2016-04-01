var usersTable;
var groupsTable;
var groups;
var users;
var rights;

$(document).ready(function(){

	var container = $('#main #Users');
	var limit = 20;
	var start = 0;

	var table = container.find('#usersTable tbody');
	var groupsTable = container.find('#groupsTable tbody');
	table.buildUsersTable( limit, start );
	groupsTable.buildGroupTable( limit, start );

	container.brainstageAPI('rights-list', function(json){
		if( json.success )
			rights = json.result;
	});

	container.on('navItemDoubleClicked', function(){
		refreshUsersTable( table, limit, start );
		refreshGroupTable( groupsTable, limit, start );
		container.find('.details').hide();
	});

	$('#userView').on('appear', function(){
		refreshUsersTable( table, limit, start );
	});

	$('#groupView').on('appear', function(){
		refreshGroupTable( groupsTable, limit, start );
	});

	table.on('click', 'tr', function(){
		if( !$(this).hasClass('selected') ) {
			var userId = parseInt( $(this).attr('data-userid') );
			var detailsContainer = $(this).closest('.row').find('.details');
			$(this).closest('table').find('tr.selected').removeClass('selected');
			$(this).addClass('selected');
			showUserDetails( userId, detailsContainer );
		}
	});

	groupsTable.on('click', 'tr', function(){
		if( !$(this).hasClass('selected') ) {
			var groupId = parseInt( $(this).attr('data-groupid') );
			var detailsContainer = $(this).closest('.row').find('.details');
			$(this).closest('table').find('tr.selected').removeClass('selected');
			$(this).addClass('selected');
			showGroupDetails( groupId, detailsContainer );
		}
	});

	container.find('.details .section').on('click', 'h1, h2, h3, h4', function(){
		var section = $(this).closest('.section');
		section.toggleClass('expanded');
		section.find('.collapse').toggleClass('in');
	});

	container.find('.details .userPasswordForm').on('change', 'input', function(){
		var form = $(this).closest('.userPasswordForm').removeClass('validInput');
		var inputs = form.find('input[type="password"]');
		var valid = true;
		for( var i=1; i<inputs.length; i++ ) {
			var curInput = $(inputs[i]);
			var prevInput = $(inputs[i-1]);
			if(	curInput.val() != prevInput.val() )
				valid = false;
		}
		for( var i=0; i<inputs.length; i++ ) {
			var input = $(inputs[i]);
			var pattern = new RegExp( input.attr('pattern') );
			if( !pattern.test( input.val() ) )
				valid = false;
		}
		if( valid )
			form.addClass('validInput');
	});

	container.find('#userCreationModal').on('change', 'input', function(){
		var form = $(this).closest('#userCreationModal').removeClass('validInput');
		var inputs = form.find('input[type="password"]');
		var valid = true;
		for( var i=1; i<inputs.length; i++ ) {
			var curInput = $(inputs[i]);
			var prevInput = $(inputs[i-1]);
			if(	curInput.val() != prevInput.val() )
				valid = false;
		}
		for( var i=0; i<inputs.length; i++ ) {
			var input = $(inputs[i]);
			var pattern = new RegExp( input.attr('pattern') );
			if( !pattern.test( input.val() ) )
				valid = false;
		}
		if( valid )
			form.addClass('validInput');
	});

	container.on('click', '.saveDetails', function(){
		var detailsContainer = $(this).closest('.tab-pane').find('.details');
		saveDetails( $(this), detailsContainer );
	});

	container.on('click', '.saveNewUser', function(){
		var form = $(this).closest('.modal');
		if( form.hasClass('validInput') )
			saveDetails( $(this), form, function(json){
				if( json.success ) {
					form.find('.alert').removeClass('alert-danger').addClass('alert-info');
					form.find('input[type="text"], input[type="password"]').val('');
					form.modal('hide');
					refreshUsersTable( table, limit, start, function(){
						showUserDetails( json.result.id, form.closest('.tab-pane').find('.details') )
					});
				} else {
					alert( json.error );
				}
			});
		else {
			form.find('.alert').removeClass('alert-info').addClass('alert-danger');
			form.find('input[type="password"]:first').focus();
		}
	});

	container.on('click', '.saveNewGroup', function(){
		var form = $(this).closest('.modal');
		saveDetails( $(this), form, function(json){
			if( json.success ) {
				form.modal('hide');
				form.find('input[type="text"], input[type="password"]').val('');
				refreshGroupTable( groupsTable, limit, start, function(){
					showGroupDetails( json.result.id, form.closest('.tab-pane').find('.details') )
				});
			} else {
				alert( json.error );
			}
		});
	});

	container.on('click', '.removeGroup', function(){
		var detailsContainer = $(this).closest('.tab-pane').find('.details');
		bootbox.dialog({
			title: detailsContainer.find('[name="name"]').val(),
			message: "Sind Sie sicher, dass Sie diese Gruppe löschen möchten?",
			buttons: {
				main: {
					label: "Abbrechen",
					className: "btn-default",
				},
				success: {
					label: "Löschen",
					className: "btn-danger",
					callback: function() {
						$.brainstageAPIPost( 'users/groups-delete', {id: detailsContainer.find('[name="id"]').val()}, function(){
							refreshGroupTable( groupsTable, limit, start );
							detailsContainer.hide();
						})
					}
				}
			}
		});
	});

	container.on('click', '.removeUser', function(){
		var detailsContainer = $(this).closest('.tab-pane').find('.details');
		bootbox.dialog({
			title: detailsContainer.find('[name="name"]').val(),
			message: "Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?",
			buttons: {
				main: {
					label: "Abbrechen",
					className: "btn-default",
				},
				success: {
					label: "Löschen",
					className: "btn-danger",
					callback: function() {
						$.brainstageAPIPost( 'users/delete', {id: detailsContainer.find('[name="id"]').val()}, function(){
							refreshUsersTable( table, limit, start );
							detailsContainer.hide();
						})
					}
				}
			}
		});
	});

});


(function($) {
	$.fn.buildUsersTable = function( limit, start, successFn ) {
		var table = this;
		table.html('');
		fillUsersTable( table, limit, start, successFn );
	};

	function fillUsersTable( table, limit, start, successFn ) {
		var container = table.closest('#main > div');
		var modalSpace = getModalSpace( container ).html('');
		var isEditable = table.closest('table').hasClass('editable');
		var data = {limit: parseInt(limit), start: parseInt(start)};
		container.brainstageAPI('list', data, function(json){
			users = json.result;
			for( var index in users ) {
				var user = users[index];
				var row = table.subordinate( 'tr', {'data-userid': user.id} );
				row.subordinate( 'td', user.name );
				row.subordinate( 'td', user.email );
			//	row.subordinate( 'td', user.lastLogin );
			}
			if( successFn != undefined )
				successFn();
		});
	}

	function getModalSpace( container ) {
		var selector = 'div.modal-space';
		var modalSpace = container.children(selector);
		if( modalSpace == undefined || modalSpace.length < 1 )
			modalSpace = container.subordinate(selector);
		return modalSpace;
	}
}(jQuery));


(function($) {
	$.fn.buildGroupTable = function( limit, start, successFn ) {
		var table = this;
		table.html('');
		fillGroupTable( table, limit, start, successFn );
	};

	function fillGroupTable( table, limit, start, successFn ) {
		var container = table.closest('#main > div');
		var isEditable = table.closest('table').hasClass('editable');
		var data = {limit: parseInt(limit), start: parseInt(start)};
		container.brainstageAPI('groups-list', data, function(json){
			groups = json.result;
			for( var index in groups ) {
				var group = groups[index];
				var row = table.subordinate( 'tr', {'data-groupid': group.id} );
				row.subordinate( 'td', group.name );
				row.subordinate( 'td', group.memberCount );
			}
			if( successFn != undefined )
				successFn();
		});
	}

	function getModalSpace( container ) {
		var selector = 'div.modal-space';
		var modalSpace = container.children(selector);
		if( modalSpace == undefined || modalSpace.length < 1 )
			modalSpace = container.subordinate(selector);
		return modalSpace;
	}
}(jQuery));


(function($) {
	$.fn.buildRightsTable = function(individuum) {
		var table = this;
		table.html('');

		for( var right in rights ) {
			var type = rights[right];
			var rightDescriptor = right.replace( /:/gi, ' &mdash; ' ).replace( /Brainstage\/Plugins\//, '' );
			var row = table.subordinate( 'tr > td > label' );

			var checkbox = row.subordinate( 'input(checkbox)', {name: 'rights['+ right +']'} );
			if( $.inArray( right, Object.keys(individuum.rights) ) >= 0 )
				checkbox.attr('checked', 'checked');

			row.append( rightDescriptor );

			if( type == 'list' || type == 'integers' ) {
				row.parent().subordinate( 'span.form-inline form-group form-group-sm > input(text).form-control', {name: 'rightValues['+ right +']', value: individuum.rights[right]} );
			}

			if( type == 'documents' ) {
				var formGroup = row.parent().subordinate( 'span.form-inline form-group form-group-sm > span.input-group' );
				formGroup.subordinate( 'input(text).form-control', {name: 'rightValues['+ right +']', value: individuum.rights[right]} );
				formGroup.subordinate( 'span.input-group-btn > button(button).btn btn-default btn-sm', "Auswählen" );
			}
		}

		return table;
	};
}(jQuery));


(function($) {
	$.fn.buildGroupMembersTable = function(memberIds) {
		var table = this;
		table.html('');

		for( var index in memberIds ) {
			var userId = memberIds[index];
			var user = users[ parseInt( userId ) ];
			var row = table.subordinate( 'tr > td', user.name );
		}

		return table;
	};
}(jQuery));


function refreshUsersTable( table, limit, start, successFn ) {
	var container = table.closest('#main > div');
	container.removeClass('editing');
	table.buildUsersTable( limit, start, successFn );
}


function refreshGroupTable( table, limit, start, successFn ) {
	var container = table.closest('#main > div');
	container.removeClass('editing');
	table.buildGroupTable( limit, start, successFn );
}


function showUserDetails( userId, detailsContainer ) {
	var listContainer = detailsContainer.closest('.row').find('.list');
	listContainer.find('tr.selected').removeClass('selected');
	listContainer.find('tr[data-userid="'+ userId +'"]').addClass('selected');

	var user = users[ userId ];

	detailsContainer.find('.title h1:first').text( user.name );
	detailsContainer.find('[name="id"]').val( user.id );
	detailsContainer.find('[name="name"]').val( user.name );
	detailsContainer.find('[name="email"]').val( user.email );
	detailsContainer.find('.lastLogin').text( user.lastLogin );

	detailsContainer.find('[name="password"], [name="password2"]').val('');
	detailsContainer.find('.validInput').removeClass('validInput');

	detailsContainer.show();

	var userGroupsTable = detailsContainer.find('.userGroupsTable tbody').html('');
	for( var index in groups ) {
		var group = groups[index];
		var isMember = $.inArray( group.id, user.groups ) >= 0;
		var row = userGroupsTable.subordinate( 'tr > td > label' );
		var checkbox = row.subordinate( 'input(checkbox)', {name: 'groups['+ group.id +']'} );
		if( isMember )
			checkbox.attr('checked', 'checked');
		row.append( group.name );
	}

	var userRightsTable = detailsContainer.find('.userRightsTable tbody').buildRightsTable(user);
}


function showGroupDetails( groupId, detailsContainer ) {
	var listContainer = detailsContainer.closest('.row').find('.list');
	listContainer.find('tr.selected').removeClass('selected');
	listContainer.find('tr[data-groupid="'+ groupId +'"]').addClass('selected');

	var group = groups[ groupId ];

	detailsContainer.find('.title h1:first').text( group.name );
	detailsContainer.find('[name="id"]').val( group.id );
	detailsContainer.find('[name="name"]').val( group.name );

	detailsContainer.show();

	var groupMembersTable = detailsContainer.find('.groupMembersTable tbody').buildGroupMembersTable(group.members);
	var groupRightsTable = detailsContainer.find('.groupRightsTable tbody').buildRightsTable(group);
}
