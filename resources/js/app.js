import './bootstrap';
import './firebase';

document.addEventListener('DOMContentLoaded', () => {
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
		const fields = {
			guest: reservationForm.querySelector('[name="guest_name"]'),
			room: reservationForm.querySelector('[name="room"]'),
			checkIn: reservationForm.querySelector('[name="check_in"]'),
			checkOut: reservationForm.querySelector('[name="check_out"]'),
			nights: reservationForm.querySelector('#new_number_of_nights'),
			rate: reservationForm.querySelector('#new_room_rate'),
			paid: reservationForm.querySelector('[name="amount_paid"]'),
			paymentMethod: reservationForm.querySelector('[name="payment_method"]'),
		};
		const summary = (name) => document.querySelector(`[data-reservation-summary] [data-summary="${name}"]`);
		const money = (value) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
		const updateSummary = () => {
			const rate = Number(fields.room?.selectedOptions[0]?.dataset.rate || 0);
			const nights = fields.checkIn?.value && fields.checkOut?.value ? Math.max((new Date(fields.checkOut.value) - new Date(fields.checkIn.value)) / 86400000, 1) : 0;
			const total = rate * nights;
			const paid = Number(fields.paid?.value || 0);
			const balance = Math.max(total - paid, 0);
			const status = paid <= 0 ? 'Unpaid' : paid >= total ? 'Paid' : 'Partially Paid';
			if (summary('guest')) summary('guest').textContent = fields.guest?.value || '-';
			if (summary('mobile')) summary('mobile').textContent = phone?.value || '-';
			if (summary('room')) summary('room').textContent = fields.room?.selectedOptions[0]?.textContent.split(' · ')[0] || '-';
			if (summary('rate')) summary('rate').textContent = money(rate);
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
			if (phone && !/^09\d{9}$/.test(phone.value)) {
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
