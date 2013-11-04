jQuery(document).ready(function($) {

	var tktHelper = {

		ticketRow : 1,
		dateTimeRow: 1,
		priceRow: 1,
		context: 'datetime',
		itemdata: {},
		currentDOMElement: {},

		/**
		 * this property is used to indicate whether editing is being done after a "create" so that if cancel is pushed we remove the row that was created in the UI.  Otherwise cancel button just toggles the edit view.
		 * @type {Boolean}
		 */
		creating: false,

		/**
		 * used to hold the items that are currently being created so if other dom elements are interacted with that typically do different stuff with created items we ONLY apply that if the current item is in the created array (rows)
		 * @type {Array}
		 */
		createdItems: [],

		/**
		 * sets the context property on this object (by default context is 'datetime')
		 * @param  {string} context what context the event is being initiated in
		 * @return {tktHelper} tktHelper object for chaining
		 */
		setcontext : function(context) {
			if ( typeof(context) !== 'undefined' )
				this.context = context;
			return this;
		},




		/**
		 * sets the creating property (defaults to true if no value is included (or value is not boolean))
		 * @param {boolean} val
		 */
		setCreating: function(val) {
			this.creating = typeof(val) !== 'undefined' && _.isBoolean(val) ? val : true;
			return this;
		},



		/**
		 * sets the dateTimeRow property value
		 * @param  {int}    num dttrownum
		 * @return {tktHelper} tktHelper object for chaining
		 */
		setdateTimeRow : function( num ) {
			if ( typeof(num) !== 'undefined' )
				this.dateTimeRow = num;
			return this;
		},


		/**
		 * sets the ticketRow property value
		 * @param  {int}    num ticket row number to set
		 * @return {tktHelper} tktHelper object for chaining
		 */
		setticketRow : function( num ) {
			if ( typeof(num) !== 'undefined' )
				this.ticketRow = num;
			return this;
		},



		/**
		 * sets the priceRow property value
		 * @param  {int} num        price row number to set
		 * @return {tktHelper}     tktHelper object for chaining.
		 */
		setpriceRow : function( num ) {
			if ( typeof(num) !== 'undefined' )
				this.priceRow = num;
			return this;
		},


		/**
		 * sets the data property value
		 * @param  {obj}    num data
		 * @return {tktHelper} tktHelper object for chaining
		 */
		setitemdata : function( data ) {
			if ( typeof(data) !== 'undefined' )
				this.itemdata = data;
			return this;
		},



		setcurrentDOMElement: function( element ) {
			if ( typeof(element) !== 'undefined' )
				this.currentDOMElement = element;
			return this;
		},



		/**
		 * gets the total row count jQuery object for the given context
		 * @return {jQuery obj}     jQuery obj that contains the row count
		 */
		getrowcountobject: function( fordecrease ) {
			var rowcountobj;

			fordecrease = typeof(fordecrease) === 'undefined' ? false : true;
			
			switch ( this.context ) {
				case 'datetime' :
					rowcountobj = fordecrease ?  $('.event-datetime-row') : $('#datetime-total-rows');
					break;
				case 'ticket' :
					rowcountobj = fordecrease ? $('.ticket-row') : $('#ticket-total-rows');
					break;
				case 'price' :
					rowcountobj = fordecrease ? $('.ee-active-price',  '#edit-ticketrow-' + this.ticketRow ) : $('#price-total-rows-' + this.ticketRow);
					break;
			}

			return rowcountobj;
		},


		/**
		 * gets the total rows for a given context in the ui
		 * @return {int}            the row count
		 */
		getrowcount: function() {
			return this.getrowcountobject().val();
		},



		/**
		 * This increments the row count of a given context by one and returns the new row count
		 * @return {int}            the new row count
		 */
		increaserowcount: function() {
			var countobj = this.getrowcountobject();
			var newcount = countobj.val();
			newcount++;
			countobj.val(newcount);

			switch ( this.context ) {
				case 'datetime' :
					this.dateTimeRow = newcount;
					break;

				case 'ticket' :
					this.ticketRow = newcount;
					break;

				case 'price' :
					this.priceRow = newcount;
			}

			return newcount;
		},




		/**
		 * This decrements the row count of a given context by one and returns the new row count
		 * note we do NOT UPDATE the value of the rowcountobject because otherwise we have to update any other existing rows.  We want to make sure we ALWAYS increment row counts during the current session.
		 * @return {int}            the new row count
		 */
		decreaserowcount: function() {
			var countobj = this.getrowcountobject(true);
			var newcount = countobj.length;
			var existingcount = newcount;
			newcount--;
			switch ( this.context ) {
				case 'datetime' :
					this.dateTimeRow = newcount;
					break;

				case 'ticket' :
					this.ticketRow = newcount;
					break;

				case 'price' :
					this.priceRow = newcount;
					break;
			}
			return newcount;
		},




		/**
		 * gets the Ticket Title for the given ticketrow value
		 * @param {int} rownum The rownumber for the we're retrieving.  If not present then we use whats set in the ticketRow property.
		 * @return {string}           The title of the ticket.
		 */
		getTicketTitle: function( rownum ) {
			if ( typeof(rownum) === 'undefined' )
				rownum = this.ticketRow;
			return $('.edit-ticket-TKT_name', '#edit-ticketrow-' + rownum).val();
		},



		/**
		 * This takes in incoming item that existing ticket or datetime list items get appended to and takes care of grabbing existing items and doing the append.
		 * Note since we're using clone.  This method also sets the data() properly on those list-items.
		 *
		 * @param  {string}  itmtoappendto This is the item we append the existing_list_container to.
		 * @return {tktHelper}            This object for chainability
		 */
		applyExistingTKTorDTTitems: function(itmtoappendto) {
			var existing_list = this.context == 'datetime' ? $('#dtt-existing-available-ticket-list-items-holder').clone().html().replace(/DTTNUM/g,this.dateTimeRow) : $('#dtt-existing-available-datetime-list-items-holder').clone().html().replace(/TICKETNUM/g, this.ticketRow).replace(/TICKETNAMEATTR/g, 'edit_tickets');
			var existing_list_container = this.context == 'datetime' ? $('#edit-datetime-available-tickets-holder').clone().html().replace(/DTTNUM/g, this.dateTimeRow) : '';

			existing_list_container = existing_list_container !== '' ? $(existing_list_container).appendTo(itmtoappendto) : '';

			//are there existing items?  if so we'll use them, if not then nope.
			if ( existing_list.length > 0 ) {
				if ( existing_list_container !== '' )
					$(existing_list_container).find('.datetime-tickets-list').html(existing_list);
				else
					$(existing_list).appendTo(itmtoappendto);

				//and make sure the data for each list item is setup correctly
				$(existing_list).each( function() {
					$(this).data('context', $(this).attr('[data-context]'));
					$(this).data('ticketRow', $(this).attr('[data-ticket-row]'));
					$(this).data('datetimeRow', $(this).attr('[data-datetime-row]'));
				});
			}

			return this;
		},




		/**
		 * This creates a completely new row for an event Datetime and adds it to the UI.
		 * @return {tktHelper obj}  return "this" to allow for possible chaining.
		 */
		newDTTrow: function() {
			var inputid, inputvalue, DTT_start_time, DTT_end_time, newDTTrow;
			this.context = 'datetime';
			var row = this.increaserowcount();

			var DTT_row_container = $('#edit-datetime-form-container-holder').clone().html().replace(/DTTNUM/g, row).replace(/DTTNAMEATTR/g, 'edit_event_datetimes');
			DTT_row_container = $(DTT_row_container).appendTo('.event-datetimes-container');

			//edit form stuff
			var DTTeditform = $('#edit-datetime-form-holder').clone().html().replace(/DTTNUM/g, row).replace(/DTTNAMEATTR/g, 'edit_event_datetimes');
			DTTeditform = $(DTTeditform).appendTo(DTT_row_container);

			//make sure all input vals get their values from the create form!
			DTTeditform.find('input').each( function() {
				inputid = $(this).attr('id').replace('-'+row,'');
				//first handle values we know AREN'T in the add-new-datetime form
				switch (inputid) {
					case 'event-datetime-DTT_ID' :
						$(this).val('0');
						break;
					
					case 'event-datetime-DTT_is_primary' :
						$(this).val(row === 1 ? '1' : '0');
						break;

					case 'event-datetime-DTT_EVT_start' :
						DTT_start_time = $('#add-new-' + inputid, '#add-event-datetime').val();
						DTT_start_time = DTT_start_time === '' ? moment().add('weeks', 1).hours(8).minutes(0).format('YYYY-MM-DD h:mm a') : DTT_start_time;
						$(this).val(DTT_start_time);
						break;

					case 'event-datetime-DTT_EVT_end' :
						DTT_end_time = $('#add-new-' + inputid, '#add-event-datetime').val();
						DTT_end_time = DTT_end_time === '' ? moment(DTT_start_time, 'YYYY-MM-DD h:mm a').add('hours', 4 ).format('YYYY-MM-DD h:mm a') : DTT_end_time;
						$(this).val(DTT_end_time);
						break;

					default :
						inputvalue = $('#add-new-' + inputid, '#add-event-datetime').val();
						$(this).val(inputvalue);
						break;
				}

			});

			//clear addnewdtt inputs
			$('#add-event-datetime').find('input').each(function() { $(this).val(''); });

			//create new DTT display row.
			var DTT_display_row = $('#dtt_new_display_row_holder').clone().html().replace(/DTTNUM/g, row);
			DTT_display_row = $(DTT_display_row).prependTo(DTT_row_container);

			//replace Date text
			DTT_display_text = this.DTT_display_text(DTT_start_time, DTT_end_time);
			$(DTT_display_row).find('.datetime-title').text(DTT_display_text);

			//apply existing tickets
			this.applyExistingTKTorDTTitems(DTT_row_container);

			//we need to make sure this new DTT has a related DTT_list_row created and added to all existing tickets and helper containers
			$('.edit-ticket-row', '.event-tickets-container').each( function() {
				tktHelper.newDTTListRow( this );
			});

			
			//on brand new events the ticket-container is hidden (making sure a datetime gets created first).  let's show that now
			if ( $('.event-tickets-container').is(':hidden') )
				$('.event-tickets-container').fadeIn();
			this.context = 'ticket';


			//verify that there isn't only one DTT row.  If there is then let's remove the trash icon from this element.  If there ISNT' then let's show all trash elements.
			if ( $('.event-datetime-row', '.event-datetimes-container').length === 1 ) {
				DTT_display_row.find('.trash-icon').hide();
			} else {
				$('.event-datetime-row', '.event-datetimes-container').find('.trash-icon', '.datetime-summary').show();
			}
			return this;
		},





		/**
		 * This clones a given datetime row and adds the cloned datetime to the ui (updating all ui elements that have datetime references)
		 * @param  {int} row    The given row that we are cloning (DTT)
		 * @return {tktHelper obj}     The tktHelper obj that allows chaining.
		 */
		cloneDateTime: function( row ) {
			this.dateTimeRow = row;
			this.context = 'datetime';
			var newrownum = this.increaserowcount();
			var newDTTrow = $('#event-datetime-' + row).clone().attr('id','event-datetime-' + newrownum).appendTo('.event-datetimes-container');
			var newid, newname, curid, curclass, data, curname, ticketsold, tickettitle;

			/*replace all old row values with newrownum*/
			//first let's do the <sections>.
			newDTTrow.find('section').each( function() {
				curid = $(this).attr('id');
				curclass = $(this).attr('class');
				newid = curid.replace(row,newrownum);
				$(this).attr('id', newid);
			});

			//next let's do the spans
			newDTTrow.find('span').each( function() {
				curclass = $(this).attr('class');
				tktHelper.itemdata = $(this).data();
				
				//handle data-datetime-row properties
				if ( typeof(tktHelper.itemdata) !== 'undefined' && typeof(tktHelper.itemdata.datetimeRow) !== 'undefined' ) {
					$(this).attr( 'data-datetime-row', newrownum ); //not using jquery .data() to set the value because that doesn't change the actual data attribute.
					$(this).data('datetimeRow', newrownum); //we still need to change the data on the element otherwise it remains as the value set on the previous element.
				}

				//handle ticket counts!
				if ( curclass == 'datetime-tickets-sold' ) {
					ticketsold = $(this).text().replace(/[0-9]/g,'') + '0';
					$(this).text(ticketsold);
				}

				if ( curclass == 'ticket-list-ticket-name' ) {
					//find out what ticket we are looking at.
					var parentdata = $(this).parent().data();
					
					//now we can get the ticket title from the tickets list and simply replace what's in the datetime ticket with it.  Why do we do this instead of just replacing the numbers?  Because the ticket title may have a number in it.
					tickettitle = tktHelper.getTicketTitle( parentdata.ticketRow );
					$(this).text(tickettitle + ': 0');
				}

			});


			//table id updates
			newDTTrow.find('table').each( function() {
				curid = $(this).attr('id');

				if ( typeof(curid) !== 'undefined' ) {
					newid = curid.replace(row,newrownum);
					$(this).attr('id', newid);
				}
			});

			//buttons
			newDTTrow.find('button').each( function() {
				data = $(this).data();
				if ( typeof(data) !== 'undefined' && typeof(data.datetimeRow) !== 'undefined' ) {
					$(this).attr('data-datetime-row', newrownum);
					$(this).data('datetimeRow', newrownum);
				}
			});


			//li
			newDTTrow.find('li').each( function() {
				data = $(this).data();
				if ( typeof(data) !== 'undefined' && typeof(data.datetimeRow) !== 'undefined' ) {
					$(this).attr('data-datetime-row', newrownum);
					$(this).data('datetimeRow', newrownum);
				}
			});


			//div
			newDTTrow.find('div').each( function() {
				curid = $(this).attr('id');
				if ( typeof(curid) !== 'undefined' ) {
					newid = curid.replace(row, newrownum);
					$(this).attr('id', newid);
				}
			});


			//labels
			newDTTrow.find('label').each( function() {
				curid = $(this).attr('for');
				newid = curid.replace(row, newrownum);
				$(this).attr('for', newid);
			});


			//the big enchilda inputs!
			newDTTrow.find('input').each( function() {
				curclass = $(this).attr('class');
				curid = $(this).attr('id');
				curname = $(this).attr('name');

				switch ( curclass ) {
					case 'event-datetime-DTT_ID' :
					case 'event-datetime-DTT_is_primary' :
						newname = curname.replace(row, newrownum);
						$(this).attr('name', newname);
						$(this).val('0');
						break;

					case 'event-datetime-DTT_EVT_start' :
					case 'event-datetime-DTT_EVT_end' :
					case 'event-datetime-DTT_reg_limit' :
						newname = curname.replace(row, newrownum);
						newid = curid.replace(row, newrownum);
						$(this).attr('name', newname);
						$(this).attr('id', newid);
						break;

					case 'datetime-ticket-checkbox' :
						newname = tktHelper.replaceRowValueByPosition(row, newrownum, 1, curname);
						$(this).attr('name', newname);
						break;
				}
			});


			// update ALL existing TKT edit forms with the new DTT li element.
			$('.edit-ticket-row', '.event-tickets-container').each( function() {
				tktHelper.newDTTListRow( this );
			});

			//set the context for any potential chains on this.
			this.context = 'datetime';

			return this;
		},



		/**
		 * This clones a ticket from a given row and updates all ui elements
		 * @param  {string} row what row we're cloning
		 * @return {this}       tktHelper obj for chainability.
		 */
		cloneTicket: function(row) {
			var newid, newname, curid, curclass, data, curname, ticketsold, tickettitle;
			this.ticketRow = row;
			this.context = 'ticket';
			var newrownum = this.increaserowcount();
			var newTKTrow = $('#display-ticketrow-' + row).clone().attr('id', 'display-ticketrow-' + newrownum).add( $('#edit-ticketrow-' + row ).clone().attr('id', 'edit-ticketrow-' + newrownum));

			newTKTrow = newTKTrow.appendTo($('.ticket-table', '.event-tickets-container').find('tbody').first() );

			/*replace all old row values with newrownum*/

			//divs
			newTKTrow.find('div').each( function() {
				curid = $(this).attr('id');
				if ( typeof(curid) !== 'undefined' ) {
					newid = curid.replace(row, newrownum);
					$(this).attr('id', newid);
				}
			});

			//first the trs
			newTKTrow.find('tr').each( function() {
				curid = $(this).attr('id');

				// first make sure this isn't a vanilla tr
				if ( typeof(curid) === 'undefined' )
					return true;

				//second make sure this isn't a price tr if it is we handle differently.
				if ( $(this, '.price-table').length > 0 ) {
					newid = tktHelper.replaceRowValueByPosition(row, newrownum, 1, curid);
				} else {
					newid = curid.replace(row, newrownum);
				}

				$(this).attr('id', newid);
			});

			//spans
			newTKTrow.find('span').each( function() {
				curclass = $(this).attr('class');
				curid = $(this).attr('id');
				tktHelper.itemdata = $(this).data();

				if ( curclass === 'ticket-display-row-TKT_sold' )
					$(this).text('0');

				if ( typeof(tktHelper.itemdata) !== 'undefined' && typeof(tktHelper.itemdata.ticketRow) !== 'undefined' ) {
					$(this).attr('data-ticket-row', newrownum);
					$(this).data('ticketRow', newrownum);
				}

				if ( typeof(curid) !== 'undefined' )
					$(this).attr('id', curid.replace(row, newrownum) );
			});

			//fieldset
			var fieldsetid = newTKTrow.find('fieldset').attr('id');
			newTKTrow.find('fieldset').attr('id', fieldsetid.replace(row, newrownum ) );

			//inputs
			newTKTrow.find('input').each(function() {
				curname = $(this).attr('name');
				curid = $(this).attr('id');

				//are we in the price rows?
				if ( $(this, '.price-table').length > 0 ) {
					newname = tktHelper.replaceRowValueByPosition(row, newrownum, 1, curname);
				//are we in the dtt list-items?
				} else if ( $(this, '.datetime-tickets-list').length > 0 ) {
					newname = tktHelper.replaceRowValueByPosition(row, newrownum, 2, curname);
				} else {
					newname = curname.replace(row, newrownum);
				}

				//ids?
				if ( typeof(curid) !== 'undefined' ) {
					newid = curid.replace(row, newrownum);
					$(this).attr('id', newid);
				}

				$(this).attr('name', newname);
			});

			//textarea
			newTKTrow.find('textarea').each( function() {
				curname = $(this).attr('name');

				//are we in the price rows?
				if ( $(this, '.price-table').length > 0 ) {
					newname = tktHelper.replaceRowValueByPosition(row, newrownum, 1, curname);
				} else {
					newname = curname.replace(row, newrownum);
				}
				$(this).attr('name', newname);
			});

			//select
			newTKTrow.find('select').each( function() {
				curname = $(this).attr('name');
				newname = tktHelper.replaceRowValueByPosition(row, newrownum, 1, curname);
				$(this).attr('name', newname);
			});

			//li
			newTKTrow.find('li').each( function() {
				$(this).attr('data-ticket-row', newrownum);
				$(this).data('ticketRow', newrownum);
			});

			//button
			newTKTrow.find('button').each( function() {
				$(this).attr('data-ticket-row', newrownum);
				$(this).data('ticketRow', newrownum);
			});

			//okay all the elements have the ticketrownums changed now let's update the related DTT items!
			//update all existing DTT edit forms with the new TKT li element (note we're also making sure that we match the active tickets).
			$('.edit-dtt-row', '.event-datetimes-container').each( function() {
				tktHelper.newTKTListRow( this );
			});


			//set the context for any potential chains on this.
			this.context = 'ticket';

			return this;

		},



		/**
		 * Simply updates the ui for the updated row.
		 * NOTE: the save button only updates the ui and ensures that the autosave will get the data correctly.  ACTUAL edits to tickets and datetimes etc will NOT be attached to the "main" event post until the user clicks the "publish" or "update" button for the entire post.  That way the user can do manipulations and editing WIHOUT worrying about a "live" event being modified.
		 *
		 * @param  {int} dttrow        this is the dttrow being "updated"
		 * @return {tktHelper}        this object for chainability
		 */
		updateDTTrow: function( dttrow ) {
			var lidttitem;
			this.dateTimeRow = dttrow;

			//need to update the displayed datetime string for the datetime title...
			var DTT_display_text = this.DTT_display_text( $('.event-datetime-DTT_EVT_start', '#edit-event-datetime-' + this.dateTimeRow).val(), $('.event-datetime-DTT_EVT_end', '#edit-event-datetime-' + this.dateTimeRow).val() );

			$('.datetime-title', '#display-event-datetime-' + this.dateTimeRow).text(DTT_display_text);

			//... and in all related dtt list rows!
			$('.datetime-tickets-list').find('li[data-datetime-row="' + tktHelper.dateTimeRow + '"]').each( function() {
				if ( typeof(this) !== 'undefined' && $(this).attr('data-context') == 'ticket-datetime' )
					$('.ticket-list-ticket-name', this).text(DTT_display_text);
			});

			this.context = 'datetime';
			this.DateTimeEditToggle();
			return this;
		},




		/**
		 * updates the ui for the updated row
		 * NOTE: the save button only updates the ui and ensures that the autosave will get the data correctly.  ACTUAL edits to tickets and datetimes etc will NOT be attached to the "main" event post until the user clicks the "publish" or "update" button for the entire post.  That way the user can do manipulations and editing WIHOUT worrying about a "live" event being modified.
		 * @param  {int}    tktrow this is the ticket being "updated"
		 * @return {tktHelper}    this object for chainability
		 */
		updateTKTrow: function( tktrow ) {
			var tktsold,
				displayRowName,
				displayRowPrefix = '.ticket-display-row-',
				displayRowContext = '#display-ticketrow-' + this.ticketRow;
			this.ticketRow = tktrow;


			//need to update the displayed TKT name
			var TKT_name = $('.edit-ticket-TKT_name', '#edit-ticketrow-' + this.ticketRow).val();
			tktsold = $('.ticket-display-row-TKT_sold', '#display-ticketrow-' + this.ticketRow).text();
			$('.ticket-display-row-TKT_name', '#display-ticketrow-' + this.ticketRow).text(TKT_name);

			//..and in all related tkt list rows!
			$('.datetime-tickets-list').find('li[data-ticket-row="' + tktHelper.ticketRow + '"]').each( function() {
				if ( $(this).attr('data-context') == 'datetime-ticket' )
					$('.ticket-list-ticket-name', this).text( TKT_name + ': ' + tktsold );
			});

			//other tkt values
			tktsold = $('.ticket-display-row-TKT_sold', '#display-ticket-row-' + this.ticketRow).text();
			
			$('.basic-ticket-info', '#edit-ticketrow-' + this.ticketRow).find('input').each( function() {
				displayRowName = displayRowPrefix + $(this).attr('class').split(' ')[0].replace('edit-ticket-', '');
				if ( $(displayRowName, displayRowContext).length > 0 )
					$(displayRowName, displayRowContext).text($(this).val());
			});

			//calculate the status
			var tktstatus = this.getTKTstatus($('.edit-ticket-TKT_start_date', '#edit-ticketrow-' + this.ticketRow).val(), $('.edit-ticket-TKT_end_date', '#edit-ticketrow-' + this.ticketRow).val() );
			$(displayRowPrefix + 'TKT_status', displayRowContext).text(tktstatus);

			this.context = 'ticket';

			//apply total price
			$('.ticket-display-row-TKT_price',  '#display-ticketrow-' + this.ticketRow).text('$' + this.getTotalPrice().finalTotal);

			//if we're updating then let's make sure this ticket is removed from the createdItems property
			this.createdItems = _.without(this.createdItems, this.ticketRow);

			this.TicketEditToggle();
			return this;
		},



		/**
		 * generates a new DTT list row for available ticket rows.
		 * @param  {string} ticketrow this ticket row the dtt list item is being added to
		 * @return {tktHelper obj}   for chainability
		 */
		newDTTListRow: function(ticketrowitm) {
			var ticketrownum = $(ticketrowitm).attr('id').replace('edit-ticketrow-', '');
			
			if ( typeof(ticketrownum) === 'undefined' )
				return true; //we may have a blank ticket row.

			var new_dtt_list_row = $('#dtt-new-available-datetime-list-items-holder').clone().html();


			var active_tkts_on_dtt = $('.datetime-tickets-list', '#event-datetime-' + this.dateTimeRow).find('.ticket-selected').data('ticketRow');
			var default_list_row_for_dtt;

			//replace all instances of DTTNUM with dttrow
			new_dtt_list_row = new_dtt_list_row.replace(/DTTNUM/g, this.dateTimeRow);
			//get name for dtt and add to the new li item
			var dttname = $('.datetime-title', '#display-event-datetime-' + this.dateTimeRow ).text();
			new_dtt_list_row = new_dtt_list_row.replace(/DTTNAME/g,dttname);
			default_list_row_for_dtt = new_dtt_list_row; //without TICKET_NUM replaced.
			//replace all instances of TICKETNUM with ticketrownum
			new_dtt_list_row = new_dtt_list_row.replace(/TICKETNUM/g,ticketrownum);



			//is this ticketrow in the active tickets list? if so then we toggle.
			if ( $.inArray(ticketrownum, active_tkts_on_dtt) > -1 ) {
				this.toggleTicketSelect(new_dtt_list_row);
			}


			//append new_dtt_list_row to the ul for datetime-tickets attached to ticket.
			new_dtt_list_row = $(ticketrowitm).find('.datetime-tickets-list').append(new_dtt_list_row);

			//we need to update the jQuery.data() element for new row
			$(new_dtt_list_row).data('datetimeRow', this.dateTimeRow);
			$(new_dtt_list_row).data('ticketRow', ticketrownum);
			$(new_dtt_list_row).data('context', 'ticket-datetime');


			//append new dtt_list_row to the available dtts row BUT we need to make the ticketnum generic! (and only append if it isn't already present!)
			if ( $('li', '#dtt-existing-available-datetime-list-items-holder').find('[data-datetime-row="'+this.dateTimeRow+'"]').length < 1 )
				$('#dtt-existing-available-datetime-list-items-holder').append(default_list_row_for_dtt);

			return this;
		},


		/**
		 * generates a new TKT list row for available dtt rows
		 * @param  {string} dttrowitm this dtt row the tkt list item is being added to
		 * @return {tktHelper obj}           for chainability
		 */
		newTKTListRow: function(dttrowitm) {
			var dttrownum = $(dttrowitm).attr('id').replace('event-datetime-', '');
			var new_tkt_list_row = $('#dtt-new-available-ticket-list-items-holder').clone().html();
			var active_dtts_on_tkt = $('.datetime-tickets-list', '#edit-ticketrow-' + this.ticketRow).find('.ticket-selected').data('dateTimeRow');
			var default_list_row_for_tkt;

			//replace all instances of TICKETNUM with ticketRow
			new_tkt_list_row = new_tkt_list_row.replace(/TICKETNUM/g, this.ticketRow );
			//get name for ticket and add to the new li item
			var TKT_name = $('.ticket-display-row-TKT_name', '#display-ticketrow-' + this.ticketRow ).text();
			TKT_name += ': 0';
			new_tkt_list_row = new_tkt_list_row.replace(/TKTNAME/g, TKT_name);
			default_list_row_for_tkt = new_tkt_list_row; //without DTTNUM replaced.
			//replace all instance of DTTNUM with  dttrownum
			new_tkt_list_row = new_tkt_list_row.replace(/DTTNUM/g, dttrownum);


			//is this ticketrow in the active datetimes list? if so then we toggle.
			if ( $.inArray(dttrownum, active_dtts_on_tkt ) > -1 ) {
				this.toggleTicketSelect(new_tkt_list_row);
			}

			//append new_tkt_list_row to the ul for datetime-tickets attached to datetime
			$(dttrowitm).find('.datetime-tickets-list').append(new_tkt_list_row);


			//we need to update the jQuery.data() element for new row
			$(new_tkt_list_row).data('datetimeRow', dttrownum);
			$(new_tkt_list_row).data('ticketRow', this.ticketRow);
			$(new_tkt_list_row).data('context', 'datetime-ticket');

			//append new_tkt_list_row to the available tkts row BUT keeping the DTTNUM generic BUT only if existing row isn't already present
			if ( $('li', '#dtt-existing-available-ticket-list-items-holder').find('[data-ticket-row="'+this.ticketRow+'"]').length < 1 )
				$('#dtt-existing-available-ticket-list-items-holder').append(default_list_row_for_tkt);

			return this;
		},


		/**
		 * Takes care of trashing a datetime in the UI and updating all the relevant ui elements
		 * @param  {int} row dtt row num being trashed
		 * @return {obj}     This object (allows for chaining)
		 */
		trash: function(row) {

			var previousrow = this.decreaserowcount(row);

			switch ( this.context ) {
				case 'datetime' :

					var dodelete = this.verifyDTTsOnTickets(row);

					if ( dodelete ) {

						//if this datetime is the primary dtt then we need to make sure that the next dtt in the list becomes the primary
						if ( $( '#edit-event-datetime-' + row ).find('.event-datetime-DTT_is_primary').val() === '1' ) {
							var nextrow = row + 1;
							$('#edit-event-datetime-' + nextrow ).find('.event-datetime-DTT_is_primary').val('1');
						}

						$('#event-datetime-' + row).remove();
						this.ticketRow = 0; //set to 0 so we remove dtts for all tickets.
						this.dateTimeRow = row;


						//if we've only got one row then we need to remove trash on that row.
						if ( $('.event-datetime-row', '.event-datetimes-container').length == 1 )
							$('.event-datetime-row', '.event-datetimes-container').find('.trash-icon').hide();
						this.toggleActiveDTTorTicket(this.context, true);
					}
					break;

				case 'ticket' :
					$('#edit-ticketrow-' + row).remove();
					$('#display-ticketrow-' + row).remove();
					this.dateTimeRow = 0; //set to 0 so we remove tkts for all datetimes.
					this.ticketRow = row;
					//if we've only got one row then we need to remove trash on that row.
					if ( $('.ticket-row', '.event-tickets-container').length === 1 )
						$('.trash-icon', '.event-tickets-container .ticket-row').hide();
					this.toggleActiveDTTorTicket(this.context, true);
					this.createdItems = _.without(this.createdItems, this.ticketRow);
					break;

				case 'price' :
					//show create button for previous row
					var last_row = $('.ee-active-price', '#edit-ticketrow-' + this.ticketRow ).last();
					if ( last_row[0] == $('#price-row-' + this.ticketRow + '-' + row )[0] )
						$('#price-row-' + this.ticketRow + '-' + previousrow ).find('.ee-create-button').show();
					$('#price-row-' + this.ticketRow + '-' + row ).remove();
					$('#extra-price-row-' + this.ticketRow + '-' + row).remove();
					//recalculate totals and apply.
					this.applyTotalPrice();
			}
			
			return this;
		},


		/**
		 * This simply verfies that there is not only ONE active dtt on the row and if there is then we halt the deactivation of that DTT.
		 * @return {bool} true if DTT can be deacivated. False if its the only DTT on the ticket.
		 */
		verifyLastDTT: function() {
			var dtt_items = $('.datetime-tickets-list', '#edit-ticketrow-' + this.ticketRow ).find('li.ticket-selected');
			if ( dtt_items.length > 1 ) {
				return true;
			} else {
				var htmlcontent = '<p>' + DTT_TRASH_BLOCK.single_warning + '</p><div class="save-cancel-button-container">' + DTT_TRASH_BLOCK.dismiss_button + '</div>';

			
				dialogHelper.displayModal().addContent(htmlcontent);
				return false;
			}
		},


		/**
		 * verify whether the trashed dtt is the ONLY dtt remaining on a ticket and if it is then we display a modal with options.
		 * @return {bool} true if DTT can be trashed (i.e. it isn't the only dtt on any tickets) false, if its the only dtt on any ticket.
		 */
		verifyDTTsOnTickets: function(row) {
			//get all ticket rows that have this dtt active on them.
			var tktrow,
				tktdata,
				singleDTTTKTs = [],
				activeTKTs = $('.ticket-selected', '#event-datetime-' + row);

			//foreach of these tickets lets check if this datetime is the ONLY dtt active.
			activeTKTs.each( function() {
				tktdata = $(this).data();
				tktrow = tktdata.ticketRow;
				if ( $('.ticket-selected', '#edit-ticketrow-' + tktrow).length === 1 )
					singleDTTTKTs[tktrow] = $('.edit-ticket-TKT_name', '#edit-ticketrow-' + tktrow).val();
				});

			if ( singleDTTTKTs.length === 0 )
				return true; //we're okay

			//otherwise let's throw up the dialog and prompt
			var htmlcontent = '<p>' + DTT_TRASH_BLOCK.main_warning + ' <strong>' + singleDTTTKTs.join('</strong>, <strong>') + '</strong></p>' + '<p>' + DTT_TRASH_BLOCK.after_warning + '</p><div class="save-cancel-button-container">' + DTT_TRASH_BLOCK.cancel_button + '</div>';

			
			dialogHelper.displayModal().addContent(htmlcontent);
			return false;
		},




		toggleActiveDTTorTicket: function(what, remove) {
			remove = typeof(remove) === 'undefined' ? false : remove;
			var li_item;
			switch (what) {
				// toggling a dtt attached to a ticket.
				case 'datetime' :
					li_item = tktHelper.ticketRow === 0 ? $('.datetime-tickets-list', '.event-tickets-container').find('li[data-datetime-row="'+tktHelper.dateTimeRow+'"]') : $('.datetime-tickets-list', '#edit-ticketrow-' + tktHelper.ticketRow ).find('li[data-datetime-row="'+tktHelper.dateTimeRow+'"]');
					break;

				// toggling a ticket attached to a dtt
				case 'ticket' :
					li_item = tktHelper.dateTimeRow === 0 ? $('.datetime-tickets-list', '.event-datetimes-container').find('li[data-ticket-row="'+tktHelper.ticketRow+'"]') : $('.datetime-tickets-list', '#edit-event-datetime-tickets-' + tktHelper.dateTimeRow).find('li[data-ticket-row="'+tktHelper.ticketRow+'"]');
					break;
			}

			li_item.each( function() {tktHelper.toggleTicketSelect(this, remove);});
		},




		/**
		 * generates a new ticketRow
		 * @return {object} this object (for chainability)
		 */
		newTicketRow: function() {
			var idref, curval, newval, price_amount, pricename, errormsg;
			var incomingcontext = this.context;
			//replace all instances of TICKETNUM with new generated row number
			this.context = 'ticket';

			var row = this.increaserowcount();
			this.createdItems.push(row);
			//edit form stuff
			var newTKTrow = $('#ticket-row-form-holder').find('tbody').clone().html().replace(/TICKETNUM/g, row ).replace(/TICKETNAMEATTR/g, 'edit_tickets');
			var initialPRCrow = incomingcontext == 'short-ticket' ? $('#ticket-edit-row-initial-price-row').find('tbody').clone().html().replace(/PRICENUM/g, '1').replace(/TICKETNUM/g, row).replace(/PRICENAMEATTR/g, 'edit_prices') : $('#ticket-edit-row-default-price-rows').find('tbody').clone().html().replace(/TICKETNUM/g, row);


			//append to existing TKTrows
			var currow = $('.ticket-table', '.event-tickets-container').find('tbody').first();
			newTKTrow = $(newTKTrow).appendTo(currow);

			initialPRCrow = incomingcontext == 'short-ticket' ? $(initialPRCrow).appendTo(newTKTrow.find('.ticket-price-rows')) : $(newTKTrow).find('.ticket-price-rows').html(initialPRCrow);

			//if this is triggered via the "short-ticket" context then we need to get the values from the create ticket form and add to the new row.
			if ( incomingcontext == 'short-ticket' ) {
				// inputs
				$('.add-datetime-ticket-container','#edit-event-datetime-tickets-' + this.dateTimeRow ).find('input').each( function() {
					curval='';
					if ( $(this).hasClass('add-new-ticket-PRC_amount') ) {
						price_amount = $(this).val();
						price_amount = price_amount !== '' ? parseFloat(price_amount) : 0;
						price_amount = price_amount.toFixed(2);
					}

					if ( $(this).hasClass('add-new-ticket-TKT_name') ) {
						pricename = $(this).val();
						if ( pricename === '' ) {
							pricename = curval = DTT_ERROR_MSG.no_ticket_name;
						}
					}

					if ( $(this).hasClass('add-new-ticket-TKT_start_date') ) {
						if ( $(this).val() === '' ) {
							curval = moment().add('hours', 2).format('YYYY-MM-DD h:mm a');
						}
					}

					if ( $(this).hasClass('add-new-ticket-TKT_end_date') ) {
						if ( $(this).val() === '' ) {
							curval = $('.event-datetime-DTT_EVT_start', '#edit-event-datetime-' + tktHelper.dateTimeRow).val();
						}
					}

					idref = $(this).attr('class').replace('add-new-', '.edit-');
					//we also need to strip out any datepicker classes that might have got added
					idref = idref.replace('ee-datepicker', '');
					idref = idref.replace('hasDatepicker', '');
					idref = $.trim(idref);
					curval = curval === '' ? $(this).val() : curval;
					newTKTrow.find(idref).val(curval);

					$(this).val('');
				});

				newTKTrow.find('.edit-price-PRC_amount', '.price-row-' +row).val(price_amount);

				// selectors
				var selected_price_type_val = 1;

				newTKTrow.find('.price-title-text', '.price-row-' + row).text('Base Price');
				newTKTrow.find('.edit-price-PRC_name', '.price-row-' + row).val(pricename);
				$('add-datetime-ticket-container', '#edit-event-datetime-tickets-' + this.dateTimeRow ).find('.gear-icon').hide(); //reset cog visibility.

				//if there are multiple ticket rows after creating this then let's show all trash icons
				if ( $('.ticket-row', '.event-tickets-container').length > 1 )
					$('.trash-icon', '.event-tickets-container .ticket-row' ).show();

			}

			//update totals on the form.
			price_amount = typeof(price_amount) !== 'undefined' ? price_amount : this.getTotalPrice().finalTotal;
			newTKTrow.find('#price-total-amount-' + row).text('$' + price_amount);
			newTKTrow.find('.ticket-price-amount').text('$' + price_amount);
			newTKTrow.find('.ticket-display-row-TKT_price').text('$' + price_amount);

			//now let's setup the display row!
			newTKTrow.find('.ticket-display-row-TKT_name').text(newTKTrow.find('.edit-ticket-TKT_name').val());
			newTKTrow.find('.ticket-display-row-TKT_start_date').text(
				this.TKT_DTT_display_text(
					newTKTrow.find('.edit-ticket-TKT_start_date').val(),
					'MMM D[,] YYYY'
					)
				);
			newTKTrow.find('.ticket-display-row-TKT_end_date').text(
				this.TKT_DTT_display_text(
					newTKTrow.find('.edit-ticket-TKT_end_date').val(),
					'MMM D[,] YYYY'
					)
				);
			newTKTrow.find('.ticket-display-row-TKT_status').text(
				this.getTKTstatus(
					newTKTrow.find('.edit-ticket-TKT_start_date').val(),
					newTKTrow.find('.edit-ticket-TKT_end_date').val()
					)
				);
			
			newTKTrow.find('.ticket-display-row-TKT_qty').text(newTKTrow.find('.edit-ticket-TKT_qty').val());

			//add li items
			
			this.applyExistingTKTorDTTitems(newTKTrow.find('.datetime-tickets-list'));


			$('.edit-dtt-row', '.event-datetimes-container').each( function() {
				tktHelper.newTKTListRow(this);
			});

			if ( incomingcontext == 'short-ticket' ) {
				this.toggleActiveDTTorTicket('datetime');
			} else {
				//we need to toggle ALL the datetimes
				this.context = 'datetime';
				var rowcount = this.getrowcount() + 1;
				this.context = 'ticket';
				for ( i=1; i<rowcount; i++ ) {
					tktHelper.setdateTimeRow(i);
					tktHelper.toggleActiveDTTorTicket('datetime');
				}
			}

			return this;
		},



		/**
		 * This sets up a new price row for attaching to a ticket.
		 * @return {tktHelper} this object for chaining.
		 */
		newPriceRow: function() {
			var curid, newid, curname, newname;
			var row = this.itemdata.priceRow;
			this.ticketRow = this.itemdata.ticketRow;
			this.increaserowcount();
			var newPRCrow = $('tbody', '#ticket-edit-row-initial-price-row').clone().html().replace(/TICKETNUM/g,this.ticketRow).replace(/PRICENUM/g,this.priceRow).replace(/PRICENAMEATTR/g, 'edit_prices');

			newPRCrow = $(newPRCrow).appendTo( $('.ticket-price-rows', '#edit-ticketrow-' + this.ticketRow) );

			//clear out existing inputs
			newPRCrow.find('input').each( function() {
				$(this).val('');
			});

			//show trash icon
			newPRCrow.find('.trash-icon').show();

			//hide add-new button on previous row.
			newPRCrow.prev().prev().find('.ee-create-button').hide();

			//replace first column with the price modifier selector
			newPRCrow.find('td').first().html( $('#ticket-edit-row-price-modifier-selector').clone().html().replace(/TICKETNUM/g,this.ticketRow).replace(/PRICENUM/g,this.priceRow).replace(/PRICENAMEATTR/g, 'edit_prices') );


			//focus on select element
			$('.edit-price-PRT_ID', newPRCrow).focus();

			return this;
		},


		/**
		 * Toggle a datetime ticket or ticket datetime list item from active to inactive (and the related attachments to the datetime or ticket).
		 * @param  {obj}        itm   the selected item
		 * @param  {bool}       trash are we TRASHING this item? then it needs to be removed from the dom.
		 * @return {tktHelper}       this object for chainability
		 */
		toggleTicketSelect: function(itm, trash) {
			this.itemdata = $(itm).data();
			trash = typeof(trash) === 'undefined' ? false : trash;
			var toggle = true;
			var selecting = $(itm).hasClass('ticket-selected') ? false : true;
			var relateditm = this.itemdata.context == 'datetime-ticket' ? $('.datetime-tickets-list', '#edit-ticketrow-' + this.itemdata.ticketRow).find('li[data-datetime-row="' + this.itemdata.datetimeRow + '"]') : $('.datetime-tickets-list', '#edit-event-datetime-tickets-' + this.itemdata.datetimeRow).find('li[data-ticket-row="' + this.itemdata.ticketRow + '"]');
			var available_list_row = this.itemdata.context === 'datetime-ticket' ? $('li', '#dtt-existing-available-datetime-list-items-holder').find('[data-datetime-row="'+this.itemdata.datetimeRow+'"]') : $('li', '#dtt-existing-available-ticket-list-items-holder').find('[data-ticket-row="' + this.itemdata.ticketRow +'"]' );

			if ( !selecting && this.itemdata.context == 'ticket-datetime' ) {
				toggle = tktHelper.verifyLastDTT();
			}

			if ( toggle ) {

				if ( !selecting || trash ) {
					$(itm).removeClass('ticket-selected');
					$('input', itm).prop('checked',false);
					relateditm.removeClass('ticket-selected');
					$('input', relateditm).prop('checked',false);
					this.removeTicket();
					if ( trash ) {
						$(itm).remove();
						available_list_row.remove();
					}
				} else  {
					$(itm).addClass('ticket-selected');
					$('input', itm).prop('checked',true);
					relateditm.addClass('ticket-selected');
					$('input', relateditm).prop('checked',true);
					//update selected tracking in various contexts
					this.addTicket();
				}
			}
			return this;
		},




		/**
		 * wrapper for tktHelper.changeTicket that makes sure we're ADDING a ticket to an item
		 * @return {tktHelper} this object for chainability
		 */
		addTicket: function() {
			this.changeTicket(this.itemdata.ticketRow, this.itemdata.datetimeRow, 'ticket-datetime');
			return this;
		},



		/**
		 * wrapper for tktHelper.changeTicket that makes sure we're REMOVING a ticket from an item
		 * @return {tktHelper}       this object for chainability
		 */
		removeTicket: function() {
			this.changeTicket(this.itemdata.ticketRow, this.itemdata.datetimeRow, 'ticket-datetime', true);
			return this;
		},



		/**
		 * Makes sure that the hidden row recording selected tickets for the current session is updated accordingly
		 * @param  {string} idrow    the row id for the row being updated.
		 * @param  {string} valuerow the row id for the value being recorded.
		 * @param  {string} context  the context that helps us determine which recording id we're using
		 * @param  {bool}   remove   Indicate whether we're rmoving the valuerow or adding it.
		 * @return {tktHelper}      This obj for chainability.
		 */
		changeTicket: function(idrow, valuerow, context, remove) {
			remove = typeof(remove) === 'undefined' ? false : remove;
			context = typeof(context) === 'undefined' ? 'datetime-ticket' : context;
			var changeid = '#' + context + '-rows-' + idrow;
			var curitems = $(changeid).val();
			if ( typeof(curitems) !== 'undefined' && curitems !== '' )
					curitems = curitems.split(',');
			else {
				if ( !remove )
					$(changeid).val(valuerow);
				return this;
			}

			if ( remove ) {
				$.each( curitems, function(i, val) {
					if ( val == valuerow ) {
						curitems = tktHelper.removeFromArray(curitems, val);
					}
				});
			} else {
				curitems.push(valuerow);
			}
			if ( curitems )
				curitems = curitems.join(',');
		
			$(changeid).val(curitems);

			return this;
		},



		/**
		 *  Used to replace a row value in a given string.  So something like "datetime-ticket[row][row]" could have "row" replaced by "newrow" via doing tktHelper.replaceRowValueByPosition(row, 2, 1, 'datetime-ticket[row][row]'); and getting the resulting string of "datetime-ticket[1][row]";
		 * @param  {string} searchstring   What we're replacing
		 * @param  {string} newvalue       What we're replacing with
		 * @param  {int}    position       What position in the stringtosearch we're replacing.
		 * @param  {string} stringtosearch What is being searched
		 * @return {string}                The string with the replaced values
		 */
		replaceRowValueByPosition: function(searchstring, newvalue, position, stringtosearch) {
			var pattern = new RegExp( searchstring, 'g' );
			var pnth = 0;
			var parsedstr = stringtosearch.replace(pattern, function( match, i, original ) {
				pnth++;
				return ( pnth === position ) ? newvalue : match;
			});
			return parsedstr;
		},




		/**
		 * This simply retrieves a human friendly DTT string for display in the DTT_display row
		 * @param {string} start DTT_start_date
		 * @param {string} end   DTT_end_date
		 * @return {string} human friendly formatted DTT string combining start and end.
		 */
		DTT_display_text: function(start, end) {
			var datedisplaytext;
			var fullstartdate = moment( start, 'YYYY-MM-DD h:mm a' );
			var fullenddate = moment( end, 'YYYY-MM-DD h:mm a' );

			//first are months equal?
			if ( fullstartdate.month() != fullenddate.month() ) {
				datedisplaytext = fullstartdate.format("MMM D[,] YYYY h:mm a") + " - " + fullenddate.format("MMM D[,] YYYY h:mm a");
			} else if ( fullstartdate.month() == fullenddate.month() && fullstartdate.date() != fullenddate.date() ) {
				datedisplaytext = fullstartdate.format("MMM D[,] h:mm a") + " - " + fullenddate.format("MMM D[,] h:mm a YYYY");
			} else {
				datedisplaytext = fullstartdate.format("MMMM D[,] YYYY") + ' @ ' + fullstartdate.format("h:mm a") + ' - ' + fullenddate.format("h:mm a");
			}

			return datedisplaytext;
		},



		/**
		 * takes incoming date and returns the date in the given format
		 * @param {string} date   incoming date (in format YYYY-MM-DD h:mm a) (needs to be in a format that the moment.js library accepts see @link http://momentjs.com/docs/#/displaying/format/)
		 * @param {string} format format date is returned in.
		 */
		TKT_DTT_display_text: function(date, dttformat) {
			var fulldate = moment( date, 'YYYY-MM-DD h:mm a' );
			return fulldate !== null ? fulldate.format(dttformat) : '';

		},




		/**
		 * calculates what the status for the ticket is based on the incoming start and end date
		 * @param  {string} startdate start date in format (yyyy-MM-dd h:mm a)
		 * @param  {string} enddate   end date in format (yyyy-MM-dd h:mm a)
		 * @return {string}           one of three statuses depending on dates (On Sale, Pending, Expired)
		 */
		getTKTstatus: function(startdate, enddate) {
			startdate = moment(startdate, 'YYYY-MM-DD h:mm a');
			enddate = moment(enddate, 'YYYY-MM-DD h:mm a');

			if ( startdate === null || enddate === null )
				return '';
			var now = moment();

			if ( startdate.isAfter(now) )
				return 'Pending';

			if ( enddate.isBefore(now ) )
				return 'Expired';

			if ( startdate.isBefore(now) && enddate.isAfter(now) )
				return 'On Sale';

		},



		/**
		 * calculate the total from the prices in the ticket row.
		 * @return {fixed} The total amount by decimal.
		 */
		getTotalPrice: function( dotaxes ) {
			var runningtotal = 0, priceAmount, operator, is_percent, totals=[];
			dotaxes = typeof(dotaxes) === 'undefined' ? true : false;
			//loop through all the prices for a given ticket
			$('.ticket-price-rows', '#edit-ticketrow-' + this.ticketRow ).find('tr.ee-active-price').each( function() {
				priceAmount = parseFloat($('.edit-price-PRC_amount', this).val());
				operator = $('.ee-price-selected-operator', this).val();
				is_percent = $('.ee-price-selected-is-percent', this ).val();
				is_percent = parseInt(is_percent, 10);

				if ( typeof( priceAmount ) === 'undefined' || typeof( operator ) === 'undefined' || typeof( is_percent) === 'undefined' || isNaN(priceAmount) )
					return 0;

				if ( is_percent ) {
					runningtotal = operator == '+' ? runningtotal + (runningtotal*(priceAmount/100)) : runningtotal - (runningtotal*(priceAmount/100));
				} else {
					runningtotal = operator == '+' ? runningtotal + priceAmount : runningtotal - priceAmount;
				}
			});

			totals.subtotal = runningtotal.toFixed(2);

			//apply taxes?
			if ( dotaxes && $('#edit-ticket-TKT_taxable-' + this.ticketRow + ':checked').length > 0 ) {
				this.applyTaxes();
				//get tax amounts and add to running total
				$('.TKT-tax-amount', '#edit-ticketrow-' + this.ticketRow ).each( function(){
					runningtotal += parseFloat( $(this).val() );
				});
			}

			totals.finalTotal = runningtotal.toFixed(2);

			return totals; //todo eventually this can be dynamic according to currency setting
		},


		/**
		 * applies the total price to all places in the current ticket row that it is displayed
		 * @return {tktHelper} this object for chainability
		 */
		applyTotalPrice: function() {
			var TKTrow = $( '#edit-ticketrow-' + this.ticketRow );
			var price_amount = this.getTotalPrice();
			TKTrow.find('#price-total-amount-' + this.ticketRow).text('$' + price_amount.finalTotal);
			TKTrow.find('.ticket-price-amount').text('$' + price_amount.finalTotal);
			TKTrow.find('.edit-ticket-TKT_price').val(price_amount.subtotal);
			//$('.ticket-display-row-TKT_price',  '#display-ticketrow-' + this.ticketRow).text('$' + price_amount);
		},




		applyTaxes: function() {
			//get subtotal
			var tax,
				id,
				subtotal = this.getTotalPrice(false).finalTotal,
				editTicketRow = '#edit-ticketrow-' + this.ticketRow;
			//make sure subtotal shows the right total
			$('.TKT-taxable-subtotal-amount-display', editTicketRow ).text('$' + subtotal);
			$('.TKT-taxable-subtotal-amount', editTicketRow).val(subtotal);
			$('.TKT-tax-percentage', editTicketRow ).each( function() {
				id = $(this).attr('id').replace('TKT-tax-percentage-', '');
				tax = parseFloat($(this).val());
				tax = subtotal * tax/100;
				tax = tax.toFixed(2);
				$('#TKT-tax-amount-' + id).val(tax);
				$('#TKT-tax-amount-display-' + id).text('$' + tax);
			});
		},



		/**
		 * This toggles the display of the edit form for a dtt row depending on the context and row num properties set.
		 *
		 */
		DateTimeEditToggle: function() {
			if ( this.context == 'ticket' || this.context == 'short-ticket' ) {
				this.selector = $('#edit-event-datetime-tickets-' + this.dateTimeRow );
				this.selector.slideToggle( 250 );
			} else if ( this.context == 'datetime' ) {
				this.selector = $('#edit-event-datetime-' + this.dateTimeRow );
				this.selector.slideToggle( 250 );
			}
			return this;
		},




		/**
		 * This toggles the display of the edit form for a Ticket row.
		 *
		 * @param {bool} trash default is false This property combined with the value of the "creating" property determine whether we also trash the ticket item when toggled (typically by a "cancel" button trigger)
		 */
		TicketEditToggle: function( trash ) {
			trash = typeof(trash) === 'undefined' ? false : trash;
			this.selector = $('#fieldset-edit-ticketrow-' + this.ticketRow );
			this.selector.slideToggle(250);

			/**
			 * if creating is true, then we need to remove the existing row and related items from the dom.
			 */
			if ( this.creating && trash &&  _.indexOf(this.createdItems, this.ticketRow) > -1 )
				this.setcontext('ticket').trash(this.ticketRow);

			//reset creating
			this.creating = false;
			return this;
		},



		/**
		 * This toggles the display of the edit form for a Price Row.
		 */
		PriceEditToggle: function() {
			this.selector = $('.extra-price-row', '#extra-price-row-' + this.ticketRow + '-' + this.priceRow);
			this.selector.slideToggle(250);
			return this;
		},



		/**
		 * This helper method simply removes any matching items from a js array.
		 * @param  {array} arr js array to remove items from
		 * @param  {string}   ind value of what element is being removed
		 * @return {array}    new array with removed items
		 */
		removeFromArray: function( arr, ind ) {
			return arr.filter( function(i) {
				return i != ind;
			});
		},




		/**
		 * handy helper method for scrolling to an item.
		 * @param  {jQuery obj}    the selector obj that we want to scroll to in the DOM
		 * @return {tktHelper}    this obj for chainability
		 */
		scrollTo: function( selector ) {
			//do we need to build the selector?
			if ( typeof(selector) === 'undefined' ) {
				selector = this.selector;
			}
			$("html,body").animate({
				scrollTop: selector.offset().top - 80
			}, 2000);
			return this;
		}




	};


	/**
	 * all event datetims and tickets triggers go below here
	 */

	/**
	 * add datetime/ticket
	 */
	$('#event-and-ticket-form-content').on('click', '.ee-create-button', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		switch ( data.context ) {
			case 'datetime' :
				tktHelper.newDTTrow().setcontext('ticket').DateTimeEditToggle().scrollTo();
				break;
			case 'short-ticket' :
				tktHelper.setcontext('short-ticket').setdateTimeRow(data.datetimeRow).newTicketRow().setCreating();
				break;
			case 'ticket' :
				tktHelper.setcontext('ticket').newTicketRow().TicketEditToggle().setCreating().scrollTo();
				break;
			case 'price' :
				tktHelper.setcontext('price').setitemdata(data).newPriceRow();
				break;
		}
		return false;
	});


	/**
	 * update datetime/ticket
	 */
	$('#event-and-ticket-form-content').on('click', '.ee-save-button', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		switch ( data.context ) {
			case 'datetime' :
				tktHelper.updateDTTrow(data.datetimeRow);
				break;

			case 'ticket' :
				tktHelper.updateTKTrow(data.ticketRow);
		}
		return false;
	});



	/**
	 * handle cancel button clicks
	 */
	$('#event-and-ticket-form-content').on('click', '.ee-cancel-button', function(e) {
		e.preventDefault();
		var data = $(this).data();

		switch ( data.context ) {
			case 'ticket' :
				tktHelper.setcontext('ticket').setticketRow(data.ticketRow).TicketEditToggle(true);
				break;
			case 'short-ticket' :
				tktHelper.setcontext('short-ticket').setdateTimeRow(data.datetimeRow).DateTimeEditToggle();
				break;
			case 'datetime' :
				tktHelper.setcontext('datetime').setdateTimeRow(data.datetimeRow).DateTimeEditToggle();
				break;
		}
		return false;
	});

	/**
	 * edit datetime/ticket
	 */
	$('#event-and-ticket-form-content').on('click', '.gear-icon', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		switch ( data.context ) {
			case 'datetime' :
				tktHelper.setcontext('datetime').setdateTimeRow(data.datetimeRow).DateTimeEditToggle();
				break;

			case 'ticket-datetime' :
				tktHelper.setcontext('datetime').setdateTimeRow(data.datetimeRow).DateTimeEditToggle().scrollTo();
				break;

			case 'datetime-ticket' :
				tktHelper.setcontext('ticket').setticketRow(data.ticketRow).TicketEditToggle().scrollTo();
				break;

			case 'ticket' :
				tktHelper.setticketRow(data.ticketRow).TicketEditToggle();
				break;
			
			case 'short-ticket' :
				tktHelper.setcontext('short-ticket').setdateTimeRow(data.datetimeRow).setticketRow(data.ticketRow).newTicketRow().DateTimeEditToggle().setcontext('ticket').TicketEditToggle().scrollTo();
				break;

			case 'price' :
				tktHelper.setcontext('price').setticketRow(data.ticketRow).setpriceRow(data.priceRow).PriceEditToggle();
				break;
		}
		return false;
	});


	/**
	 * assigned tickets toggle
	 */
	$('#event-and-ticket-form-content').on('click', '.ticket-icon', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		tktHelper.setdateTimeRow(data.datetimeRow).setcontext('ticket').DateTimeEditToggle();
		UNSAVED_DATA_MSG.inputChanged=1;
		return false;
	});


	/**
	 * clone icon toggle
	 */
	$('#event-and-ticket-form-content').on('click', '.clone-icon', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		switch ( data.context ) {
			case 'datetime' :
				tktHelper.cloneDateTime(data.datetimeRow).DateTimeEditToggle();
				break;

			case 'ticket' :
				tktHelper.cloneTicket(data.ticketRow).TicketEditToggle();
				break;
		}
		UNSAVED_DATA_MSG.inputChanged=1;
		return false;
	});



	/**
	 * trash icon click
	 */
	$('#event-and-ticket-form-content').on('click', '.trash-icon', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).data();
		switch ( data.context ) {
			case 'datetime' :
				tktHelper.setcontext('datetime').trash(data.datetimeRow);
				break;

			case 'ticket' :
				tktHelper.setcontext('ticket').trash(data.ticketRow);
				break;

			case 'price' :
				tktHelper.setcontext('price').setticketRow(data.ticketRow).trash(data.priceRow);
				break;
		}
		UNSAVED_DATA_MSG.inputChanged=1;
		return false;
	});



	/**
	 * datetime/ticket list item clicked to attach detach from related item.
	 */
	$('#event-and-ticket-form-content').on('click', '.datetime-ticket', function(e) {
		e.preventDefault();
		e.stopPropagation();
		tktHelper.toggleTicketSelect(this);
		UNSAVED_DATA_MSG.inputChanged=1;
		return false;
	});


	/**
	 * toggle cog for add-new-ticket row
	 */
	$('#event-and-ticket-form-content').on('keyup', '.add-new-ticket-TKT_name', function(e) {
		e.preventDefault();
		e.stopPropagation();
		if ( $(this).val() !== '' )
			$(this).parent().parent().find('.gear-icon').show();
		else
			$(this).parent().parent().find('.gear-icon').hide();
	});


	/**
	 * toggle price modifier selection
	 */
	$('#event-and-ticket-form-content').on('change', '.edit-price-PRT_ID', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var parent = $(this).parent(); //parent (td)
		var parentContainer = parent.parent();
		var selected = $(this).find(':selected').val();
		var operator = $('#price-option-' + selected, parent).find('.ee-price-operator').text();
		var is_percent = parseInt($('#price-option-' + selected, parent).find('.ee-PRT_is_percent').text(), 10);

		//now set selected operator
		$('.ee-price-selected-operator', parent).val(operator);
		$('.ee-price-selected-is-percent', parent).val(is_percent);

		//set display
		$('.ticket-price-info-display', parentContainer).hide();
		if ( operator == '+' ) {
			$('.ticket-price-plus', parentContainer).show();
		} else {
			$('.ticket-price-minus', parentContainer).show();
		}
		
		if ( is_percent )
			$('.ticket-price-percentage-char-display', parentContainer).show();
		else
			$('.ticket-price-dollar-sign-display', parentContainer).show();

		//recalculate price
		var data = $(this).parent().parent().find('.gear-icon').data();
		tktHelper.setticketRow(data.ticketRow).applyTotalPrice();
	});

	/**
	 * Toggle price amount change calculations
	 */
	$('#event-and-ticket-form-content').on('keyup', '.edit-price-PRC_amount', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var data = $(this).parent().parent().find('.gear-icon').data();
		tktHelper.setticketRow(data.ticketRow).applyTotalPrice();
	});


	/**
	 * toggling of TKT_taxable checkbox
	 */
	$('#event-and-ticket-form-content').on('click', '.TKT-taxable-checkbox', function(e) {
		var tktrow = $(this).attr('id').replace('edit-ticket-TKT_taxable-', '');
		$('.TKT-taxes-display', '#edit-ticketrow-' + tktrow).slideToggle();
		tktHelper.setticketRow(tktrow).applyTotalPrice();
	});



	/**
	 * Datepicker functionality
	 */
	
	$('#event-and-ticket-form-content').on('focusin', '.ee-datepicker', function(e) {
		e.preventDefault();
		var data = $(this).data();
		var start = data.context == 'start-dtt' || data.context == 'start-ticket' ? $(this, data.dateFieldContext ) : $(data.relatedField, data.dateFieldContext);
		var end = data.context == 'end-dtt' || data.context == 'end-ticket' ? $(this, data.dateFieldContext) : $(data.relatedField, data.dateFieldContext);
		var next = $(data.nextField, data.dateFieldContext);
		var doingstart = data.context == 'start-dtt' || data.context == 'start-ticket' ? true : false;

		//@todo: intelligently create min and max values for the ticket dates according to any attached dtts.  This will be tricky tho so leaving for a future iteration.

		dttPickerHelper.resetpicker().picker(start, end, next, doingstart);
	});
});