/**
 * External imports
 */
import { isGenerator } from '@eventespresso/validators';

/**
 * Internal imports
 */
import {
	createRelation,
	createRelations,
} from '../create-relations-generators';
import {
	receiveRelatedEntities,
	receiveDirtyRelationAddition,
} from '../receive-relations';
import { EventEntities } from '../../../test/fixtures/base';

describe( 'createRelation()', () => {
	describe( 'yields with expected response', () => {
		const TestEvent = EventEntities.b;
		let fulfillment;
		const reset = ( event ) => fulfillment = createRelation(
			'datetime',
			40,
			'event',
			event
		);
		it( 'throws a console error when provided entity is not a BaseEntity',
			() => {
				reset( { EVT_name: 'test event' } );
				const { value, done } = fulfillment.next();
				expect( console ).toHaveErrored();
				expect( value ).toBeUndefined();
				expect( done ).toBe( true );
			} );
		it( 'yields an generator for receiving and resolving entities', () => {
			reset( TestEvent );
			const { value } = fulfillment.next();
			expect( isGenerator( value ) ).toBe( true );
		} );
		it( 'yields an action object for receiving related entities', () => {
			const { value } = fulfillment.next();
			expect( value ).toEqual(
				receiveRelatedEntities(
					'datetime',
					40,
					'events',
					[ 20 ],
				)
			);
		} );
		describe( 'yields action objects for receiving dirty relations', () => {
			const args = [ 'events', 20, 'datetime', 40 ];
			[ receiveDirtyRelationAddition ].forEach(
				( actionMethod ) => {
					it( actionMethod.name + '()', () => {
						const { value } = fulfillment.next();
						expect( value ).toEqual( actionMethod( ...args ) );
					} );
				}
			);
		} );
		it( 'yields generator for resolving entity id ' +
			'selector', () => {
			const { value } = fulfillment.next();
			expect( isGenerator( value ) ).toBe( true );
		} );
	} );
} );

describe( 'createRelations()', () => {
	const TestEvents = [
		EventEntities.b,
		EventEntities.c,
	];
	let fulfillment;
	const reset = ( entities, relationName ) => fulfillment = createRelations(
		'datetime',
		40,
		relationName,
		entities
	);
	it( 'throws a console error if the entities are not for the given ' +
		'model', () => {
		reset( TestEvents, 'datetime' );
		const { value, done } = fulfillment.next();
		expect( console ).toHaveErroredWith(
			'Warning: Incoming relation Entities do not contain BaseEntity ' +
			'instances for the given relation model (datetime)'
		);
		expect( value ).toBeUndefined();
		expect( done ).toBe( true );
	} );
	it( 'yields an generator for receiving entity records', () => {
		reset( TestEvents, 'events' );
		const { value } = fulfillment.next();
		expect( isGenerator( value ) ).toBe( true );
	} );
	it( 'yields an action object for receiving related entity ids', () => {
		const { value } = fulfillment.next();
		expect( value ).toEqual(
			receiveRelatedEntities(
				'datetime',
				40,
				'events',
				[ 20, 30 ]
			)
		);
	} );
	describe( 'yields action objects for receiving dirty relations (for all ' +
		'expected ids)', () => {
		[ 30, 20 ].forEach( ( relationId ) => {
			[ receiveDirtyRelationAddition ].forEach(
				( actionMethod ) => {
					it( actionMethod.name + '()', () => {
						const { value } = fulfillment.next();
						const args = [ 'events', relationId, 'datetime', 40 ];
						expect( value ).toEqual( actionMethod( ...args ) );
					} );
				}
			);
		} );
	} );
} );
