/**
 * External imports
 */
import { __ } from '@eventespresso/i18n';

/**
 * Internal dependencies
 */
import { EditorDateListItem } from './';
import './editor-dates-list-view.css';

/**
 * EditorDatesListView
 * Displays dates in a standard list table like view
 *
 * @function
 * @param {Array} entities 	array of JSON objects defining the Event Dates
 * @param {string} htmlClass
 * @param {mixed} otherProps
 * @return {Component} 			list of rendered Event Dates
 */
const EditorDatesListView = ( {
	entities,
	htmlClass,
	...otherProps
} ) => {
	htmlClass = htmlClass ?
		`${ htmlClass } ee-dates-list-list-view` :
		'ee-dates-list-list-view';

	return (
		<div className={ htmlClass }>
			<div key={ 0 } className="ee-editor-date-list-items">
				<div className="ee-date-list-item"></div>
				<div className="ee-date-list-item">
					{ __( 'ID', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Name', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Start Date', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'End Date', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Sold', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Reserved', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Capacity', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Registrants', 'event_espresso' ) }
				</div>
				<div className="ee-date-list-item">
					{ __( 'Actions', 'event_espresso' ) }
				</div>
			</div>
			{
				entities.map(
					function( eventDate ) {
						return (
							<EditorDateListItem
								key={ eventDate.id }
								eventDate={ eventDate }
								{ ...otherProps }
							/>
						);
					}
				)
			}
		</div>
	);
};

export default EditorDatesListView;