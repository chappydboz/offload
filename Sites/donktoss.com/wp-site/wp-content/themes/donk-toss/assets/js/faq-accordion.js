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

				categoryGroups.forEach(function (group) {
					let visibleInGroup = 0;
					const groupItems = group.querySelectorAll('.donktoss-faq-item');

					groupItems.forEach(function (item) {
						const questionText = item.querySelector('.donktoss-faq-question-text')?.textContent.toLowerCase() || '';
						const answerText = item.querySelector('.donktoss-faq-answer')?.textContent.toLowerCase() || '';

						if (searchTerm === '' || questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
							item.classList.remove('is-hidden-by-search');
							visibleInGroup++;
						} else {
							item.classList.add('is-hidden-by-search');
						}
					});

					// Hide category heading if no visible items in category
					const categoryHeading = group.querySelector('.donktoss-faq-category-heading');
					if (categoryHeading) {
						if (visibleInGroup === 0 && searchTerm !== '') {
							categoryHeading.style.display = 'none';
						} else {
							categoryHeading.style.display = '';
						}
					}
				});
			});
		}
	});
});
