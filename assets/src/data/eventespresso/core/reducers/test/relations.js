/**
 * External imports
 */
import { Set, Map } from 'immutable';
import cuid from 'cuid';

/**
 * Internal imports
 */
import {
	normalizedReceiveAndRemoveRelations,
	updateEntityIdForRelations,
	removeRelatedEntitiesForEntity,
} from '../relations';
import { mockStateForTests } from '../../test/fixtures';
import { EventEntities, DateTimeEntities } from '../../../test/fixtures/base';
import { ACTION_TYPES } from '../../actions/action-types';

const { relations: types } = ACTION_TYPES;
const testCuid = cuid();
const originalState = mockStateForTests.relations
	.setIn(
		[ 'index', 'datetimes', 55 ],
		Map().set( 'event', Set.of( 10, testCuid ) )
	).setIn(
		[ 'entityMap', 'event', 10, 'datetimes' ],
		Set.of( 52, 55 )
	).setIn(
		[ 'entityMap', 'event', testCuid, 'datetimes' ],
		Set.of( 55 )
	);

describe( normalizedReceiveAndRemoveRelations.name + '()', () => {
	const testAction = (
		modelName,
		relationName,
		entityId,
		relatedEntityIds,
		type
	) => (
		{
			modelName,
			relationName,
			entityId,
			relatedEntityIds,
			type,
		}
	);
	[ types.RECEIVE_RELATED_ENTITY_IDS, types.REMOVE_RELATED_ENTITY_IDS ]
		.forEach( ( type ) => {
			describe( type + ' action type', () => {
				[
					[
						'correctly normalize incoming relation and return expected state',
						'datetime',
						'event',
						52,
						[ 10 ],
						originalState,
						originalState
							.deleteIn( [ 'index', 'datetimes', 52 ] )
							.setIn(
								[ 'entityMap', 'event', 10, 'datetimes' ],
								Set.of( 55 )
							),
					],
					[
						'correctly normalize incoming relation and return expected state' +
						'for datetime for when index has multiple relation ids and ' +
						'entity map does not',
						'datetime',
						'event',
						55,
						[ testCuid ],
						originalState,
						originalState
							.setIn(
								[ 'index', 'datetimes', 55, 'event' ],
								Set.of( 10 ),
							).deleteIn( [ 'entityMap', 'event', testCuid ] ),
					],
					[
						'expected state for existing entities relations in state',
						'event',
						'datetime',
						10,
						[ 52 ],
						originalState,
						originalState
							.deleteIn( [ 'index', 'datetimes', 52 ] )
							.setIn(
								[ 'entityMap', 'event', 10, 'datetimes' ],
								Set.of( 55 )
							),
					],
					[
						'expected state for new entities relations in state',
						'event',
						'datetime',
						10,
						[ 60, 52 ],
						originalState
							.setIn(
								[ 'index', 'datetimes', 60, 'event' ],
								Set.of( 10 )
							)
							.setIn(
								[ 'entityMap', 'event', 10, 'datetimes' ],
								Set.of( 52, 60, 55 )
							),
						originalState
							.deleteIn( [ 'index', 'datetimes', 52 ] )
							.setIn(
								[ 'entityMap', 'event', 10, 'datetimes' ],
								Set.of( 55 )
							),
					],
				].forEach( ( [
					description,
					modelName,
					relationName,
					entityId,
					relationIds,
					expectedReceiveState,
					expectedRemoveState,
				] ) => {
					const expectedState =
						type === types.RECEIVE_RELATED_ENTITY_IDS ?
							expectedReceiveState :
							expectedRemoveState;
					it( description, () => {
						const resultState = normalizedReceiveAndRemoveRelations(
							originalState,
							testAction(
								modelName,
								relationName,
								entityId,
								relationIds,
								type
							)
						);
						expect( resultState ).toEqual( expectedState );
					} );
				} );
			} );
		} );
} );

describe( updateEntityIdForRelations.name + '()', () => {
	const testAction = ( modelName, oldEntityId, newEntityId ) => ( {
		modelName,
		oldEntityId,
		newEntityId,
	} );
	[
		[
			'returns original state if there are no existing entries for the ' +
			'id',
			[ 'events', 70, 10 ],
			originalState,
		],
		[
			'returns original state if the model is not in the state',
			[ 'ticket', 10, 20 ],
			originalState,
		],
		[
			'replaces old Id with new id',
			[ 'events', EventEntities.d.id, 90 ],
			originalState
				.deleteIn( [ 'index', 'datetimes', DateTimeEntities.d.id ] )
				.setIn(
					[ 'index', 'datetimes', DateTimeEntities.d.id ],
					Map().set( 'event', Set.of( 90 ) )
				)
				.deleteIn( [ 'entityMap', 'event', EventEntities.d.id ] )
				.setIn(
					[ 'entityMap', 'event', 90 ],
					Map().set( 'datetimes', Set.of( DateTimeEntities.d.id ) )
				),
		],
		[
			'replaces old id with new id with normalized id',
			[ 'events', EventEntities.d.id, '90' ],
			originalState
				.deleteIn( [ 'index', 'datetimes', DateTimeEntities.d.id ] )
				.setIn(
					[ 'index', 'datetimes', DateTimeEntities.d.id ],
					Map().set( 'event', Set.of( 90 ) )
				)
				.deleteIn( [ 'entityMap', 'event', EventEntities.d.id ] )
				.setIn(
					[ 'entityMap', 'event', 90 ],
					Map().set( 'datetimes', Set.of( DateTimeEntities.d.id ) )
				),
		],

	].forEach( ( [
		description,
		args,
		expectedState,
	] ) => {
		it( description, () => {
			expect(
				updateEntityIdForRelations( originalState, testAction( ...args ) )
			).toEqual( expectedState );
		} );
	} );
} );
describe( removeRelatedEntitiesForEntity.name + '()', () => {
	const testAction = ( modelName, entityId ) => ( {
		modelName,
		entityId,
	} );
	[
		[
			'returns original state if there are no existing entries for the ' +
			'id',
			[ 'events', 70 ],
			originalState,
		],
		[
			'returns original state if the model is not in the state',
			[ 'ticket', 10 ],
			originalState,
		],
		[
			'removes requested cuid',
			[ 'events', EventEntities.d.id ],
			originalState
				.deleteIn( [ 'index', 'datetimes', DateTimeEntities.d.id ] )
				.deleteIn( [ 'entityMap', 'event', EventEntities.d.id ] ),
		],
		[
			'removes requested normalized id',
			[ 'events', '10' ],
			originalState
				.deleteIn( [ 'index', 'datetimes', 52 ] )
				.setIn(
					[ 'index', 'datetimes', 55, 'event' ],
					Set.of( testCuid )
				)
				.deleteIn( [ 'entityMap', 'event', 10 ] ),
		],
	].forEach( ( [
		description,
		args,
		expectedState,
	] ) => {
		it( description, () => {
			expect(
				removeRelatedEntitiesForEntity(
					originalState,
					testAction( ...args )
				)
			).toEqual( expectedState );
		} );
	} );
} );
