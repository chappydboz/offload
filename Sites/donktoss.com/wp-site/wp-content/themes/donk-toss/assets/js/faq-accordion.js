/**
 * Donk Toss FAQ Accordion Script
 * Handles single-open mode accordion toggles and instant search filtering with clear button.
 */

document.addEventListener('DOMContentLoaded', function () {
	// Initialize all FAQ accordion blocks on page
	const faqBlocks = document.querySelectorAll('.donktoss-faq-block-container');

	faqBlocks.forEach(function (block) {
		const accordionMode = block.getAttribute('data-accordion-mode');
		const items = block.querySelectorAll('.donktoss-faq-item');
		const searchInput = block.querySelector('.donktoss-faq-search-input');
		const clearBtn = block.querySelector('.donktoss-faq-search-clear');

		// 1. Single Open Accordion Logic
		if (accordionMode === 'single') {
			items.forEach(function (item) {
				item.addEventListener('toggle', function (event) {
					if (item.open) {
						items.forEach(function (otherItem) {
							if (otherItem !== item && otherItem.open) {
								otherItem.removeAttribute('open');
							}
						});
					}
				});
			});
		}

		// 2. Instant Search Filter Logic
		if (searchInput) {
			const handleSearch = function () {
				const searchTerm = searchInput.value.toLowerCase().trim();
				const categoryGroups = block.querySelectorAll('.donktoss-faq-category-group');
				let totalVisible = 0;

				// Toggle Clear Button
				if (clearBtn) {
					clearBtn.style.display = searchTerm.length > 0 ? 'flex' : 'none';
				}

				categoryGroups.forEach(function (group) {
					let visibleInGroup = 0;
					const groupItems = group.querySelectorAll('.donktoss-faq-item');

					groupItems.forEach(function (item) {
						const questionText = item.querySelector('.donktoss-faq-question-text')?.textContent.toLowerCase() || '';
						const answerText = item.querySelector('.donktoss-faq-answer')?.textContent.toLowerCase() || '';

						if (searchTerm === '' || questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
							item.classList.remove('is-hidden-by-search');
							item.style.display = '';
							visibleInGroup++;
							totalVisible++;
						} else {
							item.classList.add('is-hidden-by-search');
							item.style.display = 'none';
						}
					});

					// Hide the entire category group container if no visible items match search
					if (visibleInGroup === 0 && searchTerm !== '') {
						group.classList.add('is-hidden-by-search');
						group.style.display = 'none';
					} else {
						group.classList.remove('is-hidden-by-search');
						group.style.display = '';
					}
				});

				// Handle empty state if no questions match across all groups
				let noResultsEl = block.querySelector('.donktoss-faq-no-results');
				if (totalVisible === 0 && searchTerm !== '') {
					if (!noResultsEl) {
						noResultsEl = document.createElement('div');
						noResultsEl.className = 'donktoss-faq-no-results donktoss-faq-category-group';
						noResultsEl.innerHTML = '<p style="margin:0; text-align:center; font-size:1.1rem; color:rgba(255,255,255,0.9);">No questions found matching your search.</p>';
						const listWrap = block.querySelector('.donktoss-faq-list') || block;
						listWrap.appendChild(noResultsEl);
					} else {
						noResultsEl.style.display = '';
					}
				} else if (noResultsEl) {
					noResultsEl.style.display = 'none';
				}
			};

			searchInput.addEventListener('input', handleSearch);

			// Clear Button click handler
			if (clearBtn) {
				clearBtn.addEventListener('click', function (e) {
					e.preventDefault();
					searchInput.value = '';
					handleSearch();
					searchInput.focus();
				});
			}
		}
	});

	// 3. Dynamic Sticky Subnav Scroll Offset & Smooth Anchor Scrolling
	const stickyNavWrap = document.querySelector('.donktoss-hub-nav-sticky-wrap, .donktoss-rr-nav-sticky-wrap');

	function updateDynamicScrollOffset() {
		if (!stickyNavWrap) return 120;
		const navBar = stickyNavWrap.querySelector('.donktoss-hub-nav-bar, .donktoss-rr-nav-bar') || stickyNavWrap;
		const navHeight = navBar.offsetHeight || 50;
		const siteHeader = document.querySelector('.site-header, #ast-mobile-header, .main-header-bar');
		const headerHeight = (siteHeader && (window.getComputedStyle(siteHeader).position === 'fixed' || window.getComputedStyle(siteHeader).position === 'sticky')) ? siteHeader.offsetHeight : 0;
		const totalOffset = navHeight + headerHeight + 24;
		document.documentElement.style.setProperty('--donktoss-scroll-offset', totalOffset + 'px');
		return totalOffset;
	}

	if (stickyNavWrap) {
		updateDynamicScrollOffset();
		window.addEventListener('resize', updateDynamicScrollOffset);
		window.addEventListener('orientationchange', updateDynamicScrollOffset);

		// Handle smooth in-page anchor jumps with dynamic offset
		document.querySelectorAll('a.donktoss-hub-nav-link, a.donktoss-rr-nav-link, a.donktoss-rr-quick-card').forEach(function (link) {
			const href = link.getAttribute('href');
			if (href && href.startsWith('#') && href.length > 1) {
				link.addEventListener('click', function (e) {
					const target = document.querySelector(href);
					if (target) {
						e.preventDefault();
						const offset = updateDynamicScrollOffset();
						const targetY = target.getBoundingClientRect().top + window.pageYOffset - offset;
						window.scrollTo({
							top: Math.max(0, targetY),
							behavior: 'smooth'
						});
						if (history.pushState) {
							history.pushState(null, null, href);
						}
					}
				});
			}
		});
	}
});

