/**
 * Donk Toss FAQ Accordion Script
 * Handles single-open mode accordion toggles and instant search filtering.
 */

document.addEventListener('DOMContentLoaded', function () {
	// Initialize all FAQ accordion blocks on page
	const faqBlocks = document.querySelectorAll('.donktoss-faq-block-container');

	faqBlocks.forEach(function (block) {
		const accordionMode = block.getAttribute('data-accordion-mode');
		const items = block.querySelectorAll('.donktoss-faq-item');
		const searchInput = block.querySelector('.donktoss-faq-search-input');

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
			searchInput.addEventListener('input', function (e) {
				const searchTerm = e.target.value.toLowerCase().trim();
				const categoryGroups = block.querySelectorAll('.donktoss-faq-category-group');
				let totalVisible = 0;

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

				// Optional: Handle empty state if no questions match across all groups
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
			});
		}
	});
});
