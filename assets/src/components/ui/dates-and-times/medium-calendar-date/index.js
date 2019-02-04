/**
 * External imports
 */
import moment from 'moment-timezone';
import { Fragment } from 'react';

/**
 * Internal dependencies
 */
import './style.css';

/**
 * Displays a full calendar date... just not so bigly
 *
 * @function
 * @param {Moment|Date|string} date
 * @param {string} headerText
 * @param {string} footerText
 * @param {string} htmlClass
 * @param {string} position
 * @param {boolean} addWrapper
 * @return {string} rendered date
 */
const MediumCalendarDate = ( {
	date,
	headerText,
	footerText,
	htmlClass = '',
	position = 'left',
	addWrapper = false,
} ) => {
	console.log( 'MediumCalendarDate', date );
	if ( ! moment.isMoment( date ) ) {
		const originalDate = date;
		date = moment( date );
		if ( ! date.isValid() ) {
			date = moment( new Date( originalDate ) );
		}
	}
	htmlClass = htmlClass ?
		`${ htmlClass } medium-calendar-date-wrapper` :
		'medium-calendar-date-wrapper';
	htmlClass = position === 'left' ?
		`${ htmlClass } mcd-pos-left` :
		`${ htmlClass } mcd-pos-right`;
	const mediumDate = (
		<Fragment>
			{
				headerText && (
					<div className="medium-calendar-date-header">
						{ headerText }
					</div>
				)
			}
			<div className="medium-calendar-date">
				<div className="weekday">
					{ date.format( 'dddd' ) }
				</div>
				<div className="month-day-year">
					{ date.format( 'MMM DD' ) }
					<span>{ '/' }</span>
					{ date.format( 'YY' ) }
				</div>
				{
					footerText && (
						<div className="medium-calendar-date-footer">
							{ footerText }
						</div>
					)
				}
			</div>
		</Fragment>
	);
	return addWrapper ? (
		<div className={ htmlClass }>{ mediumDate }</div>
	) : mediumDate;
};

export default MediumCalendarDate;