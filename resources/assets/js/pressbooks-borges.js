/* global PBBorges */

import '../styles/pressbooks-borges.css';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, pagination, refinementList, stats } from 'instantsearch.js/es/widgets';
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter';

document.addEventListener( 'DOMContentLoaded', () => {
	if ( typeof PBBorges === 'undefined' || ! PBBorges.typesense.nodes.length ) {
		return;
	}

	const typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter( {
		server: {
			nodes: PBBorges.typesense.nodes,
			apiKey: PBBorges.typesense.apiKey,
		},
		additionalSearchParameters: {
			query_by: 'title,content,authors,book_title',
		},
	} );

	const searchClient = typesenseInstantsearchAdapter.searchClient;

	const searchInput = document.getElementById( 'pb-borges-search-input' );
	const dropdown = document.getElementById( 'pb-borges-dropdown' );

	if ( searchInput && dropdown ) {
		let debounceTimer;

		searchInput.addEventListener( 'input', e => {
			clearTimeout( debounceTimer );
			const query = e.target.value.trim();

			if ( query.length < 2 ) {
				dropdown.innerHTML = '';
				dropdown.classList.remove( 'active' );
				return;
			}

			debounceTimer = setTimeout( async () => {
				try {
					const results = await searchClient.search( [
						{
							indexName: 'pb_contributors',
							params: {
								query,
								hitsPerPage: 3,
							},
						},
						{
							indexName: 'pb_books',
							params: {
								query,
								hitsPerPage: 3,
							},
						},
						{
							indexName: 'pb_sections',
							params: {
								query,
								hitsPerPage: 5,
							},
						},
					] );
					renderDropdown( results.results );
				} catch ( err ) {
					console.error( 'Borges search error:', err );
				}
			}, 300 );
		} );

		searchInput.addEventListener( 'keydown', e => {
			if ( e.key === 'Escape' ) {
				dropdown.classList.remove( 'active' );
				searchInput.blur();
			}
		} );

		document.addEventListener( 'click', e => {
			if ( ! dropdown.contains( e.target ) && e.target !== searchInput ) {
				dropdown.classList.remove( 'active' );
			}
		} );
	}

	/**
	 * @param {Array} results - The search results.
	 */
	function renderDropdown( results ) {
		if ( ! dropdown ) return;
		let html = '';

		const contributors = results[0]?.hits ?? [];
		const books = results[1]?.hits ?? [];
		const sections = results[2]?.hits ?? [];

		if ( contributors.length ) {
			html += '<div class="pb-borges-group"><div class="pb-borges-group-label">People</div>';
			contributors.forEach( hit => {
				html += `<div class="pb-borges-hit pb-borges-hit--contributor">
					<span class="pb-borges-icon">&#128100;</span>
					<div class="pb-borges-hit-body">
						<div class="pb-borges-hit-title">${ hit._highlightResult?.name?.value ?? hit.document.name }</div>
						<div class="pb-borges-hit-meta">${ ( hit.document.contributor_type ?? [] ).join( ', ' ) } &middot; ${ hit.document.book_count ?? 0 } books</div>
					</div>
				</div>`;
			} );
			html += '</div>';
		}

		if ( books.length ) {
			html += '<div class="pb-borges-group"><div class="pb-borges-group-label">Books</div>';
			books.forEach( hit => {
				html += `<div class="pb-borges-hit pb-borges-hit--book">
					<span class="pb-borges-icon">&#128214;</span>
					<div class="pb-borges-hit-body">
						<div class="pb-borges-hit-title">${ hit._highlightResult?.title?.value ?? hit.document.title }</div>
						<div class="pb-borges-hit-meta">${ ( hit.document.authors ?? [] ).join( ', ' ) }</div>
					</div>
				</div>`;
			} );
			html += '</div>';
		}

		if ( sections.length ) {
			html += '<div class="pb-borges-group"><div class="pb-borges-group-label">Sections</div>';
			sections.forEach( hit => {
				const snippet = hit._highlightResult?.content?.value ?? '';
				html += `<div class="pb-borges-hit pb-borges-hit--section">
					<span class="pb-borges-icon">&#128214;</span>
					<div class="pb-borges-hit-body">
						<div class="pb-borges-hit-title">${ hit._highlightResult?.title?.value ?? hit.document.title }</div>
						<div class="pb-borges-hit-meta">${ hit.document.book_title ?? '' } &middot; ${ hit.document.post_type }</div>
						${ snippet ? `<div class="pb-borges-hit-snippet">${ snippet }</div>` : '' }
					</div>
				</div>`;
			} );
			html += '</div>';
		}

		const totalFound = ( results[0]?.found ?? 0 ) + ( results[1]?.found ?? 0 ) + ( results[2]?.found ?? 0 );

		if ( totalFound === 0 ) {
			html = '<div class="pb-borges-empty">No results found.</div>';
		} else {
			html += `<div class="pb-borges-see-all">
				<a href="${ PBBorges.resultsPageUrl }&q=${ encodeURIComponent( searchInput.value ) }">See all ${ totalFound } results &rarr;</a>
			</div>`;
		}

		dropdown.innerHTML = html;
		dropdown.classList.add( 'active' );
	}

	const searchPage = document.getElementById( 'pb-borges-search-page' );
	if ( searchPage ) {
		const urlParams = new URLSearchParams( window.location.search );
		const initialQuery = urlParams.get( 'q' ) ?? '';

		const search = instantsearch( {
			searchClient,
			indexName: 'pb_sections',
		} );

		search.addWidgets( [
			searchBox( {
				container: '#pb-borges-searchbox',
				/**
				 * @param {string} query - The search query.
				 * @param {Function} search - The search function.
				 */
				queryHook( query, search ) {
					search( query );
				},
			} ),
			stats( { container: '#pb-borges-stats' } ),
			refinementList( {
				container: '#pb-borges-filter-post-type',
				attribute: 'post_type',
			} ),
			refinementList( {
				container: '#pb-borges-filter-book',
				attribute: 'book_title',
				searchable: true,
			} ),
			refinementList( {
				container: '#pb-borges-filter-authors',
				attribute: 'authors',
				searchable: true,
			} ),
			refinementList( {
				container: '#pb-borges-filter-license',
				attribute: 'section_license',
			} ),
			hits( {
				container: '#pb-borges-hits',
				templates: {
					/**
					 * @param {object} hit - The search hit.
					 * @returns {string} The HTML string for the hit.
					 */
					item( hit ) {
						const snippet = hit._highlightResult?.content?.value ?? '';
						return `<div class="pb-borges-result">
							<h3>${ hit._highlightResult?.title?.value ?? hit.title }</h3>
							<div class="pb-borges-result-meta">${ hit.book_title ?? '' } &middot; ${ hit.post_type }</div>
							${ snippet ? `<p class="pb-borges-result-snippet">${ snippet }</p>` : '' }
						</div>`;
					},
				},
			} ),
			pagination( { container: '#pb-borges-pagination' } ),
		] );

		search.start();

		if ( initialQuery ) {
			const input = document.querySelector( '#pb-borges-searchbox input' );
			if ( input ) {
				input.value = initialQuery;
				input.dispatchEvent( new Event( 'input' ) );
			}
		}
	}
} );
