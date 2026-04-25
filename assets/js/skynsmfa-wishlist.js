(() => {
	'use strict';

	const cfg = window.skynsmfaWishlist || null;
	if (!cfg || !cfg.ajax_url || !cfg.nonce) {
		return;
	}

	const settings = (cfg.settings && typeof cfg.settings === 'object') ? cfg.settings : {
		notification_type: 'toast',
		display_mode: 'button',
	};

	function toFormBody(obj) {
		const params = new URLSearchParams();
		Object.keys(obj).forEach((key) => {
			if (obj[key] === undefined || obj[key] === null) {
				return;
			}
			params.append(key, String(obj[key]));
		});
		return params.toString();
	}

	function post(action, data) {
		const body = toFormBody(
			Object.assign(
				{
					action,
					nonce: cfg.nonce,
				},
				data || {}
			)
		);

		return fetch(cfg.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body,
		}).then((r) => r.json());
	}

	function setButtonLoading(btn, loading) {
		btn.disabled = !!loading;
		btn.classList.toggle('is-loading', !!loading);
	}

	function createToastContainer() {
		let container = document.querySelector('.skynsmfa-toast-container');
		if (!container) {
			container = document.createElement('div');
			container.className = 'skynsmfa-toast-container';
			document.body.appendChild(container);
		}
		return container;
	}

	function notify(message, target) {
		if (settings.notification_type === 'none') {
			return;
		}

		if (settings.notification_type === 'tooltip' && target) {
			const tooltip = document.createElement('span');
			tooltip.className = 'skynsmfa-tooltip';
			tooltip.textContent = message;
			target.appendChild(tooltip);
			setTimeout(() => {
				target.removeChild(tooltip);
			}, 1800);
			return;
		}

		const container = createToastContainer();
		const toast = document.createElement('div');
		toast.className = 'skynsmfa-toast';
		toast.textContent = message;
		container.appendChild(toast);
		setTimeout(() => {
			toast.classList.add('skynsmfa-toast-hidden');
			setTimeout(() => {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 400);
		}, 2200);
	}

	function updateWishlistButton(btn, isAdded) {
		btn.classList.toggle('is-added', !!isAdded);
		if (!btn.classList.contains('skynsmfa-icon-button')) {
			btn.textContent = isAdded ? 'Already in wishlist' : 'Add to wishlist';
		}
		btn.setAttribute('aria-label', isAdded ? 'Remove from wishlist' : 'Add to wishlist');
	}

	function getWishlistKey(btn) {
		return btn.getAttribute('data-wishlist-key') || (cfg.current_wishlist_key || 'default');
	}

	function createWishlist(listName) {
		if (!listName || !listName.trim()) {
			return;
		}

		post('skynsmfa_create_wishlist', { list_name: listName.trim() })
			.then((res) => {
				if (res && res.success) {
					window.location.reload();
					return;
				}
				alert((res && res.data && res.data.message) || (cfg.i18n && cfg.i18n.error) || 'Error');
			})
			.catch(() => alert((cfg.i18n && cfg.i18n.error) || 'Error'));
	}

	function onDocumentClick(e) {
		const target = e.target;

		// Handle generic add/remove wishlist button (loop/single)
		if (target && target.closest && target.closest('.skynsmfa-wishlist-button')) {
			const btn = target.closest('.skynsmfa-wishlist-button');
			e.preventDefault();

			const productId = parseInt(btn.getAttribute('data-product-id') || '0', 10) || 0;
			if (!productId) return;

			const wishlistKey = getWishlistKey(btn);
			const action = btn.classList.contains('is-added') ? 'skynsmfa_remove_from_wishlist' : 'skynsmfa_add_to_wishlist';
			const successMessage = btn.classList.contains('is-added') ? (cfg.i18n && cfg.i18n.removed ? cfg.i18n.removed : 'Removed from wishlist.') : (cfg.i18n && cfg.i18n.added ? cfg.i18n.added : 'Added to wishlist.');

			setButtonLoading(btn, true);

			post(action, { product_id: productId, qty: 1, wishlist_key: wishlistKey })
				.then((res) => {
					if (res && res.success) {
						const isAdded = action === 'skynsmfa_add_to_wishlist';
						updateWishlistButton(btn, isAdded);
						notify(successMessage, btn);
						return;
					}
					alert((res && res.data && res.data.message) || (cfg.i18n && cfg.i18n.error) || 'Error');
				})
				.catch(() => alert((cfg.i18n && cfg.i18n.error) || 'Error'))
				.finally(() => setButtonLoading(btn, false));
			return;
		}

		// Handle create new wishlist button
		if (target && target.closest && target.closest('.skynsmfa-create-list-btn')) {
			const btn = target.closest('.skynsmfa-create-list-btn');
			e.preventDefault();

			const listName = window.prompt('Enter a name for your new wishlist:');
			if (!listName) {
				return;
			}

			createWishlist(listName);
			return;
		}

		// Handle Remove from Wishlist (Table)
		if (target && target.closest && target.closest('.skynsmfa-remove-btn')) {
			const btn = target.closest('.skynsmfa-remove-btn');
			e.preventDefault();
			
			const productId = parseInt(btn.getAttribute('data-product-id') || '0', 10) || 0;
			const wishlistKey = btn.getAttribute('data-wishlist-key') || 'default';
			if (!productId) return;

			btn.style.opacity = '0.5';
			post('skynsmfa_remove_from_wishlist', { product_id: productId, wishlist_key: wishlistKey })
				.then((res) => {
					if (res && res.success) {
						window.location.reload();
					} else {
						alert((res && res.data && res.data.message) || 'Error removing item.');
					}
				})
				.catch(() => alert('Error removing item.'));
			return;
		}

		// Handle Single Move to Cart
		if (target && target.closest && target.closest('.skynsmfa-move-to-cart-btn')) {
			const btn = target.closest('.skynsmfa-move-to-cart-btn');
			e.preventDefault();

			const productId = parseInt(btn.getAttribute('data-product-id') || '0', 10) || 0;
			const wishlistKey = btn.getAttribute('data-wishlist-key') || 'default';
			if (!productId) return;

			setButtonLoading(btn, true);
			post('skynsmfa_move_to_cart', { product_id: productId, wishlist_key: wishlistKey })
				.then((res) => {
					if (res && res.success) {
						window.location.reload();
					} else {
						alert((res && res.data && res.data.message) || 'Error moving to cart.');
						setButtonLoading(btn, false);
					}
				})
				.catch(() => {
					alert('Error moving to cart.');
					setButtonLoading(btn, false);
				});
			return;
		}

		// Handle Bulk Move to Cart
		if (target && target.closest && target.closest('.skynsmfa-bulk-move-btn')) {
			const btn = target.closest('.skynsmfa-bulk-move-btn');
			e.preventDefault();

			const wishlistKey = btn.getAttribute('data-wishlist-key') || 'default';
			const table = btn.closest('.skynsmfa-wishlist-box').querySelector('table');
			
			const checkboxes = table.querySelectorAll('.skynsmfa-item-select:checked');
			const productIds = Array.from(checkboxes).map(cb => parseInt(cb.value, 10));

			if (productIds.length === 0) {
				alert('Please select at least one item.');
				return;
			}

			setButtonLoading(btn, true);
			
			// Custom post parser for arrays since URLSearchParams doesn't array map intuitively in native fetch sometimes, 
			// but we can pass them as multiple fields: product_ids[]=1&product_ids[]=2
			const params = new URLSearchParams();
			params.append('action', 'skynsmfa_move_multiple_to_cart');
			params.append('nonce', cfg.nonce);
			params.append('wishlist_key', wishlistKey);
			productIds.forEach(id => params.append('product_ids[]', id));

			fetch(cfg.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString(),
			})
			.then(r => r.json())
			.then((res) => {
				if (res && res.success) {
					window.location.reload();
				} else {
					alert((res && res.data && res.data.message) || 'Error moving items to cart.');
					setButtonLoading(btn, false);
				}
			})
			.catch(() => {
				alert('Error moving items to cart.');
				setButtonLoading(btn, false);
			});
			return;
		}

		// Handle select all checkbox
		if (target && target.classList && target.classList.contains('skynsmfa-select-all')) {
			const table = target.closest('table');
			const checkboxes = table.querySelectorAll('.skynsmfa-item-select');
			checkboxes.forEach(cb => cb.checked = target.checked);
		}
	}

	document.addEventListener('click', onDocumentClick, false);
})();

