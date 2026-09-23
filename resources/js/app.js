import './bootstrap';
import './firebase';

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[data-new-reservation-form] [name="guest_email"], [data-new-reservation-form] [name="guest_phone"]').forEach((field) => field.removeAttribute('required'));
	document.querySelectorAll('[id^="reservation-"] .grid.gap-3 > div:nth-child(-n+2) strong').forEach((contact) => {
		if (!contact.textContent.trim()) contact.textContent = 'N/A';
	});

	document.querySelectorAll('[data-modal-open]').forEach((button) => {
		button.addEventListener('click', () => {
			const modal = document.getElementById(button.dataset.modalOpen);
			modal?.classList.remove('hidden');
			modal?.querySelector('input')?.focus();
		});
	});

	document.querySelectorAll('[data-modal-close]').forEach((button) => {
		button.addEventListener('click', () => {
			document.getElementById(button.dataset.modalClose)?.classList.add('hidden');
		});
	});

	document.querySelectorAll('[id^="payment-"]').forEach((modal) => {
		const form = modal.querySelector('form');
		if (!form || form.querySelector('[data-payment-exit]')) return;
		const exit = document.createElement('button');
		exit.type = 'button';
		exit.textContent = 'Exit';
		exit.className = 'btn-secondary';
		exit.dataset.paymentExit = 'true';
		exit.addEventListener('click', () => modal.classList.add('hidden'));
		const submit = form.querySelector('button[type="submit"], button:not([type])');
		submit?.parentElement?.insertBefore(exit, submit);
	});

	const search = document.querySelector('[data-reservation-search]');
	const filter = document.querySelector('[data-status-filter]');
	const rows = document.querySelectorAll('[data-reservation-row]');
	const applyFilters = () => {
		const term = search?.value.toLowerCase() ?? '';
		const status = filter?.value ?? 'all';
		rows.forEach((row) => {
			const matchesTerm = row.dataset.search.includes(term);
			const matchesStatus = status === 'all' || row.dataset.status === status;
			row.classList.toggle('hidden', !(matchesTerm && matchesStatus));
		});
	};
	search?.addEventListener('input', applyFilters);
	filter?.addEventListener('change', applyFilters);

	const reservationForm = document.querySelector('[data-new-reservation-form]');
	if (reservationForm) {
		const phone = reservationForm.querySelector('[data-mobile-input]');
		const catalog = JSON.parse(document.querySelector('[data-room-catalog]')?.textContent || '[]');
		const roomCount = reservationForm.querySelector('[name="room_count"]');
		const oldRoom = reservationForm.querySelector('[name="room"]');
		const roomSelections = document.createElement('div');
		roomSelections.className = 'sm:col-span-2 grid gap-3';
		roomSelections.dataset.roomSelections = 'true';
		oldRoom?.replaceWith(roomSelections);
		const roomTypes = catalog.filter((room) => room.available_units > 0);
		const selectedLabels = () => [...roomSelections.querySelectorAll('[data-room-number]:not(:disabled)')].map((select) => select.value).filter(Boolean);
		const refreshNumbers = () => {
			const selected = selectedLabels();
			roomSelections.querySelectorAll('[data-room-number]').forEach((select) => {
				[...select.options].forEach((option) => {
					option.disabled = option.value && option.value !== select.value && selected.includes(option.value);
				});
			});
		};
		const buildRoomBlock = (index) => {
			const block = document.createElement('div');
			block.className = 'grid gap-3 rounded-xl border border-stone-200 bg-white p-3 sm:grid-cols-2';
			block.dataset.roomBlock = String(index);
			const typeLabel = document.createElement('label');
			typeLabel.className = 'text-sm font-semibold';
			typeLabel.textContent = `Room ${index + 1} type`;
			const type = document.createElement('select');
			type.className = 'field mt-1';
			type.name = `rooms[${index}][type]`;
			type.required = true;
			type.dataset.roomType = 'true';
			type.innerHTML = '<option value="">Select room type</option>' + roomTypes.map((room) => `<option value="${room.name}" data-rate="${room.rate_min || 0}">${room.name}</option>`).join('');
			typeLabel.append(type);
			const numberLabel = document.createElement('label');
			numberLabel.className = 'text-sm font-semibold';
			numberLabel.textContent = 'Physical room';
			const number = document.createElement('select');
			number.className = 'field mt-1';
			number.name = `rooms[${index}][number]`;
			number.required = true;
			number.dataset.roomNumber = 'true';
			numberLabel.append(number);
			const updateNumbers = () => {
				const room = catalog.find((item) => item.name === type.value);
				number.innerHTML = '<option value="">Select room number</option>' + (room?.room_statuses || []).filter((status) => status.available).map((status) => `<option value="${status.number}">${status.number}</option>`).join('');
				refreshNumbers();
				updateSummary();
			};
			type.addEventListener('change', updateNumbers);
			number.addEventListener('change', () => { refreshNumbers(); updateSummary(); });
			block.append(typeLabel, numberLabel);
			return block;
		};
		for (let index = 0; index < 10; index += 1) roomSelections.append(buildRoomBlock(index));
		const fields = {
			guest: reservationForm.querySelector('[name="guest_name"]'),
			roomCount,
			checkIn: reservationForm.querySelector('[name="check_in"]'),
			checkOut: reservationForm.querySelector('[name="check_out"]'),
			nights: reservationForm.querySelector('#new_number_of_nights'),
			rate: reservationForm.querySelector('#new_room_rate'),
			paid: reservationForm.querySelector('[name="amount_paid"]'),
			paymentMethod: reservationForm.querySelector('[name="payment_method"]'),
		};
		const summary = (name) => document.querySelector(`[data-reservation-summary] [data-summary="${name}"]`);
		const money = (value) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
		const breakdown = summary('breakdown') || (() => {
			const element = document.createElement('div');
			element.dataset.summary = 'breakdown';
			element.className = 'sm:col-span-2 mt-2 space-y-1';
			document.querySelector('[data-reservation-summary] .grid')?.append(element);
			return element;
		})();
		const updateSummary = () => {
			const roomCountValue = Number(fields.roomCount?.value || 1);
			roomSelections.querySelectorAll('[data-room-block]').forEach((block, index) => {
				const hidden = index >= roomCountValue;
				block.classList.toggle('hidden', hidden);
				block.querySelectorAll('select').forEach((select) => { select.disabled = hidden; });
			});
			const selectedRooms = [...roomSelections.querySelectorAll('[data-room-block]')].slice(0, roomCountValue).map((block) => {
				const type = block.querySelector('[data-room-type]');
				const number = block.querySelector('[data-room-number]');
				const room = catalog.find((item) => item.name === type?.value);
				return { type: type?.value || '', number: number?.value || '', rate: Number(room?.rate_min || 0) };
			});
			const rate = selectedRooms.reduce((total, room) => total + room.rate, 0);
			const nights = fields.checkIn?.value && fields.checkOut?.value ? Math.max((new Date(fields.checkOut.value) - new Date(fields.checkIn.value)) / 86400000, 1) : 0;
			const total = rate * nights;
			const paid = Number(fields.paid?.value || 0);
			const balance = Math.max(total - paid, 0);
			const status = paid <= 0 ? 'Unpaid' : paid >= total ? 'Paid' : 'Partially Paid';
			if (summary('guest')) summary('guest').textContent = fields.guest?.value || '-';
			if (summary('mobile')) summary('mobile').textContent = phone?.value || '-';
			if (summary('rooms')) summary('rooms').textContent = roomCountValue;
			if (summary('rate')) summary('rate').textContent = money(rate);
			breakdown.innerHTML = selectedRooms.map((room, index) => `<div>Room ${index + 1} – ${room.type || 'Not selected'} – ${money(room.rate)}</div>`).join('');
			if (fields.nights) fields.nights.value = nights;
			if (fields.rate) fields.rate.value = money(rate);
			if (summary('check-in')) summary('check-in').textContent = fields.checkIn?.value || '-';
			if (summary('check-out')) summary('check-out').textContent = fields.checkOut?.value || '-';
			if (summary('nights')) summary('nights').textContent = nights;
			if (summary('booking-type')) summary('booking-type').textContent = reservationForm.querySelector('[name="booking_type"]:checked')?.value === 'check_in_now' ? 'Check-In Now' : 'Reservation Only';
			if (summary('total')) summary('total').textContent = money(total);
			if (summary('paid')) summary('paid').textContent = money(paid);
			if (summary('balance')) summary('balance').textContent = money(balance);
			if (summary('payment-status')) summary('payment-status').textContent = status;
			if (fields.paid) fields.paid.max = total.toFixed(2);
			if (fields.paymentMethod) fields.paymentMethod.required = paid > 0;
		};
		phone?.addEventListener('input', () => {
			phone.value = phone.value.replace(/\D/g, '').slice(0, 11);
			updateSummary();
		});
		reservationForm.addEventListener('input', updateSummary);
		reservationForm.addEventListener('change', updateSummary);
		reservationForm.addEventListener('submit', (event) => {
			const selected = selectedLabels();
			if (selected.length !== new Set(selected).size) {
				event.preventDefault();
				window.alert('Select a different physical room for each room block.');
			} else if (phone?.value && !/^09\d{9}$/.test(phone.value)) {
				event.preventDefault();
				phone.setCustomValidity('Please enter a valid Philippine mobile number using 11 digits (09XXXXXXXXX).');
				phone.reportValidity();
			} else if (phone) {
				phone.setCustomValidity('');
			}
		});
		updateSummary();
	}
});
import './bootstrap';
