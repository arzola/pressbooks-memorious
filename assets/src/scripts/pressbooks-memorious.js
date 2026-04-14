/* global PBMemorious */

import '../styles/pressbooks-memorious.css';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, pagination, refinementList, stats } from 'instantsearch.js/es/widgets';
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter';

document.addEventListener( 'DOMContentLoaded', () => {
	if ( typeof PBMemorious === 'undefined' || ! PBMemorious.typesense.nodes.length ) {
		return;
	}

	const node = PBMemorious.typesense.nodes[ 0 ];
	const typesenseBaseUrl = node.protocol + '://' + node.host + ':' + node.port;
	const typesenseApiKey = PBMemorious.typesense.apiKey;

	const typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter( {
		server: {
			nodes: PBMemorious.typesense.nodes,
			apiKey: PBMemorious.typesense.apiKey,
		},
		additionalSearchParameters: {
			query_by: 'title,content',
			highlight_start_tag: '<mark>',
			highlight_end_tag: '</mark>',
			snippet_threshold: 30,
			'content(snippet)': true,
			'content(elide)': true,
		},
	} );

	const searchClient = typesenseInstantsearchAdapter.searchClient;

	const theme = PBMemorious.theme ?? 'scholarly';

	const iconBtn = document.querySelector( '.pb-memorious-icon-btn' );
	const adminBarItem = document.querySelector( '#wp-admin-bar-pb-memorious-search .ab-item' );

	let searchBar, searchInput, dropdown;

	/**
	 *
	 */
	function openSearch() {
		if ( ! searchBar ) return;
		searchBar.classList.add( 'open' );
		iconBtn?.setAttribute( 'aria-expanded', 'true' );
		setTimeout( () => searchInput.focus(), 100 );
	}

	/**
	 *
	 */
	function closeSearch() {
		if ( ! searchBar ) return;
		searchBar.classList.remove( 'open' );
		dropdown.classList.remove( 'active' );
		searchInput.value = '';
		iconBtn?.setAttribute( 'aria-expanded', 'false' );
	}

	/**
	 *
	 */
	function toggleSearch() {
		if ( searchBar?.classList.contains( 'open' ) ) {
			closeSearch();
		} else {
			openSearch();
		}
	}

	if ( iconBtn ) {
		const wpbody = document.getElementById( 'wpbody-content' )?.parentElement;
		const wpbodyContent = document.getElementById( 'wpbody-content' );

		if ( wpbody && wpbodyContent ) {
			searchBar = document.createElement( 'div' );
			searchBar.id = 'pb-memorious-search-bar';
			searchBar.setAttribute( 'data-theme', theme );
			searchBar.innerHTML = '<div class="pb-memorious-search-inner"><input type="text" id="pb-memorious-search-input" placeholder="What are you looking for?" /><p class="pb-memorious-hint">Search across all your books \u2014 chapters, front matter, back matter, glossary terms, book titles, authors, and contributors.</p></div><div id="pb-memorious-dropdown"></div>';
			wpbody.insertBefore( searchBar, wpbodyContent );

			searchInput = searchBar.querySelector( '#pb-memorious-search-input' );
			dropdown = searchBar.querySelector( '#pb-memorious-dropdown' );
		}

		const clickTarget = adminBarItem ?? iconBtn;
		clickTarget.addEventListener( 'click', e => {
			e.preventDefault();
			e.stopPropagation();
			toggleSearch();
		} );

		clickTarget.addEventListener( 'keydown', e => {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				toggleSearch();
			}
		} );
	}

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
					const blogIds = PBMemorious.blogIds ?? [];
					const searches = [
						{
							collection: 'pb_contributors',
							q: query,
							query_by: 'name,slug',
							per_page: 3,
						},
						{
							collection: 'pb_books',
							q: query,
							query_by: 'title,authors,subjects,keywords',
							per_page: 3,
						},
						{
							collection: 'pb_sections',
							q: query,
							query_by: 'title,content,authors,book_title',
							per_page: 5,
						},
					];

					if ( blogIds.length ) {
						searches[1].filter_by = 'blog_id:=[' + blogIds.join( ',' ) + ']';
						searches[2].filter_by = 'blog_id:=[' + blogIds.join( ',' ) + '] && post_status:=[publish,private,draft]';
					}

					const resp = await fetch( typesenseBaseUrl + '/multi_search', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-TYPESENSE-API-KEY': typesenseApiKey,
						},
						body: JSON.stringify( { searches } ),
					} );

					if ( ! resp.ok ) {
						const err = await resp.json();
						throw new Error( err.message || resp.statusText );
					}

					const data = await resp.json();
					renderDropdown( data.results );
				} catch ( err ) {
					console.error( 'Memorious search error:', err );
				}
			}, 300 );
		} );

		searchInput.addEventListener( 'keydown', e => {
			if ( e.key === 'Escape' ) {
				closeSearch();
				iconBtn?.focus();
			}
		} );

		document.addEventListener( 'click', e => {
			if ( ! dropdown.contains( e.target ) && e.target !== searchInput && ! iconBtn?.contains( e.target ) ) {
				closeSearch();
			}
		} );

		searchBar.addEventListener( 'focusout', e => {
			const related = e.relatedTarget;
			if ( ! related ) {
				closeSearch();
				return;
			}
			if ( ! searchBar.contains( related ) && ! iconBtn?.contains( related ) ) {
				closeSearch();
			}
		} );
	}

	/**
	 * @param {Array} results - Search results from Typesense multi_search.
	 */
	function renderDropdown( results ) {
		if ( ! dropdown ) return;
		let html = '';

		const contributors = results[0]?.hits ?? [];
		const books = results[1]?.hits ?? [];
		const sections = results[2]?.hits ?? [];

		if ( contributors.length ) {
			html += '<div class="pb-memorious-group"><div class="pb-memorious-group-label">Contributors</div>';
			contributors.forEach( hit => {
				const nameHl = hit.highlights?.find( h => h.field === 'name' );
				const name = nameHl?.snippet ?? hit.document.name;
				const types = ( hit.document.contributor_type ?? [] ).join( ', ' );
				html += `<div class="pb-memorious-hit pb-memorious-hit--contributor">
					<span class="pb-memorious-hit-type">Person</span>
					<div class="pb-memorious-hit-body">
						<div class="pb-memorious-hit-title">${ name }</div>
						<div class="pb-memorious-hit-meta">${ types ? types + ' \u00b7 ' : '' }${ hit.document.book_count ?? 0 } books</div>
					</div>
				</div>`;
			} );
			html += '</div>';
		}

		if ( books.length ) {
			html += '<div class="pb-memorious-group"><div class="pb-memorious-group-label">Books</div>';
			books.forEach( hit => {
				const titleHl = hit.highlights?.find( h => h.field === 'title' );
				const title = titleHl?.snippet ?? hit.document.title;
				const link = hit.document.book_url ?? '#';
				const authors = ( hit.document.authors ?? [] ).join( ', ' );
				html += `<a href="${ link }" class="pb-memorious-hit pb-memorious-hit--book">
					<span class="pb-memorious-hit-type">Book</span>
					<div class="pb-memorious-hit-body">
						<div class="pb-memorious-hit-title">${ title }</div>
						<div class="pb-memorious-hit-meta">${ authors }</div>
					</div>
				</a>`;
			} );
			html += '</div>';
		}

		if ( sections.length ) {
			html += '<div class="pb-memorious-group"><div class="pb-memorious-group-label">Sections</div>';
			sections.forEach( hit => {
				const titleHl = hit.highlights?.find( h => h.field === 'title' );
				const title = titleHl?.snippet ?? hit.document.title;
				const contentHl = hit.highlights?.find( h => h.field === 'content' );
				const snippet = contentHl?.snippet ?? '';
				const link = hit.document.edit_url ?? '#';
				const typeLabel = formatPostType( hit.document.post_type );
				html += `<a href="${ link }" class="pb-memorious-hit pb-memorious-hit--section">
					<span class="pb-memorious-hit-type">${ typeLabel }</span>
					<div class="pb-memorious-hit-body">
						<div class="pb-memorious-hit-title">${ title }</div>
						<div class="pb-memorious-hit-meta">${ hit.document.book_title ?? '' }</div>
						${ snippet ? `<div class="pb-memorious-hit-snippet">${ snippet }</div>` : '' }
					</div>
				</a>`;
			} );
			html += '</div>';
		}

		const totalFound = ( results[0]?.found ?? 0 ) + ( results[1]?.found ?? 0 ) + ( results[2]?.found ?? 0 );

		if ( totalFound === 0 ) {
			html = '<div class="pb-memorious-empty">No matches in the library.</div>';
		} else {
			html += `<div class="pb-memorious-see-all">
				<a href="${ PBMemorious.resultsPageUrl }&q=${ encodeURIComponent( searchInput.value ) }">View all ${ totalFound } results \u2192</a>
			</div>`;
		}

		dropdown.innerHTML = html;
		dropdown.classList.add( 'active' );
	}

	/**
	 * @param {string} type - The post type slug.
	 * @returns {string} Abbreviated label.
	 */
	function formatPostType( type ) {
		const map = {
			'chapter': 'Ch.',
			'front-matter': 'F.M.',
			'back-matter': 'B.M.',
			'glossary': 'Glos.',
		};
		return map[ type ] ?? type;
	}

	/**
	 * @param {string} html - The HTML snippet, possibly with <mark> tags.
	 * @param {number} maxLen - Max character length.
	 * @returns {string} Truncated snippet.
	 */
	function truncateSnippet( html, maxLen ) {
		const text = html.replace( /<\/?mark>/g, '' );
		if ( text.length <= maxLen ) {
			return html;
		}
		const marks = [];
		const placeholder = '\u200b';
		const plain = html.replace( /<mark>(.*?)<\/mark>/g, ( _, c ) => {
			marks.push( c );
			return placeholder + marks.length + placeholder;
		} );
		const truncated = plain.slice( 0, maxLen ).split( placeholder ).map( ( part, i ) => {
			if ( i % 2 === 1 && marks[ parseInt( part, 10 ) ] !== undefined ) {
				return '<mark>' + marks[ parseInt( part, 10 ) ] + '</mark>';
			}
			return part;
		} ).join( '' );
		return truncated + '\u2026';
	}

	const searchPage = document.getElementById( 'pb-memorious-search-page' );
	if ( searchPage ) {
		searchPage.setAttribute( 'data-theme', theme );

		const hitsContainer = document.getElementById( 'pb-memorious-hits' );
		if ( hitsContainer ) {
			let skeletonHtml = '<div class="pb-memorious-skeleton">';
			for ( let i = 0; i < 5; i++ ) {
				skeletonHtml += `<div class="pb-memorious-skeleton-item">
					<div class="pb-memorious-skeleton-line" style="width:${ 60 + Math.round( Math.random() * 30 ) }%"></div>
					<div class="pb-memorious-skeleton-line pb-memorious-skeleton-meta"></div>
					<div class="pb-memorious-skeleton-line pb-memorious-skeleton-text" style="width:${ 80 + Math.round( Math.random() * 15 ) }%"></div>
					<div class="pb-memorious-skeleton-line pb-memorious-skeleton-text" style="width:${ 50 + Math.round( Math.random() * 30 ) }%"></div>
				</div>`;
			}
			skeletonHtml += '</div>';
			hitsContainer.innerHTML = skeletonHtml;
		}

		const urlParams = new URLSearchParams( window.location.search );
		const initialQuery = urlParams.get( 'q' ) ?? '';

		const search = instantsearch( {
			searchClient,
			indexName: 'pb_sections',
		} );

		search.addWidgets( [
			searchBox( {
				container: '#pb-memorious-searchbox',
				/**
				 *
				 * @param query
				 * @param search
				 */
				queryHook( query, search ) {
					search( query );
				},
			} ),
			stats( { container: '#pb-memorious-stats' } ),
			refinementList( {
				container: '#pb-memorious-filter-post-type',
				attribute: 'post_type',
			} ),
			refinementList( {
				container: '#pb-memorious-filter-book',
				attribute: 'book_title',
				searchable: true,
			} ),
			refinementList( {
				container: '#pb-memorious-filter-authors',
				attribute: 'authors',
				searchable: true,
			} ),
			refinementList( {
				container: '#pb-memorious-filter-license',
				attribute: 'section_license',
			} ),
			hits( {
				container: '#pb-memorious-hits',
				templates: {
					/**
					 *
					 * @param hit
					 */
					item( hit ) {
						const raw = hit._highlightResult?.content?.value ?? '';
						const snippet = raw ? truncateSnippet( raw, 180 ) : '';
						const link = hit.edit_url ?? '#';
						const typeLabel = formatPostType( hit.post_type );
						return `<div class="pb-memorious-result">
							<h3><a href="${ link }">${ hit._highlightResult?.title?.value ?? hit.title }</a></h3>
							<div class="pb-memorious-result-meta">${ typeLabel } \u00b7 ${ hit.book_title ?? '' }</div>
							${ snippet ? `<p class="pb-memorious-result-snippet">${ snippet }</p>` : '' }
						</div>`;
					},
				},
			} ),
			pagination( { container: '#pb-memorious-pagination' } ),
		] );

		search.start();

		if ( initialQuery ) {
			const input = document.querySelector( '#pb-memorious-searchbox input' );
			if ( input ) {
				input.value = initialQuery;
				input.dispatchEvent( new Event( 'input' ) );
			}
		}
	}
} );
