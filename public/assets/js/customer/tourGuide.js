document.addEventListener("DOMContentLoaded", () => {
	const pageElement = document.querySelector("[data-page]");

	if (!pageElement) {
		console.warn("No element with 'data-page' attribute found.");
		return;
	}

	const page = pageElement.getAttribute("data-page");
	console.log("Page detected:", page);

	switch (page) {
		case "dashboard":
			initializeDashboardTour();
			break;

		case "orders":
			initializeOrder();
			break;

		case "edit-order":
			initializeEditOrder();
			break;

		case "support":
			initializeSupport();
			break;

		case "subscription":
			initializeSubscription();
			break;

		case "invoices":
			initializeInvoices();
			break;

		default:
			console.warn("No tour configured for this page.");
			break;
	}
});

function initializeDashboardTour() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	// Add all your tour steps
	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.tour-btn', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.reward', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.inbox', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.recent', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.review', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.support', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.history', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}


function initializeOrder() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	// Add all your tour steps
	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.counters', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.table-responsive', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.dropdown button', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}


function initializeEditOrder() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.import-btn', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.domain-forwarding', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.domain-hosting', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.platform', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.domains', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.sending-platform', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.sending-platform-fields', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.inboxes-per-domain', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.total-inbox', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.remaining', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.first-name', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.last-name', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.prefix-variants', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.email-password', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.email-picture-link', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.master-inbox', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.purchase-btn', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}


function initializeSupport() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	// Add all your tour steps
	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.counters', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.create-ticket', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.fa-eye', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}


function initializeSubscription() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	// Add all your tour steps
	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.counters', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.filter', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.dropdown button', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}


function initializeInvoices() {
	const tour = new Shepherd.Tour({
		defaultStepOptions: {
			classes: 'shepherd-theme-arrows',
			scrollTo: { behavior: 'smooth', block: 'center' },
			cancelIcon: { enabled: true },
		},
		useModalOverlay: true,
	});

	// Add all your tour steps
	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.counters', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.table-responsive', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Creating a project mailbox Tour',
		text: 'Creating a project mailbox tour is easy. too! Just create a "Tour" instance, and add as many steps as you want.',
		attachTo: { element: '.dropdown button', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	// Manual start button (optional)
	const startTourButton = document.getElementById("start-tour");
	if (startTourButton) {
		startTourButton.addEventListener("click", (event) => {
			event.preventDefault();
			tour.start();
		});
	}

	// Auto-start if first visit
	const hasSeenTour = localStorage.getItem('hasSeenTour');
	if (!hasSeenTour) {
		tour.start();
		localStorage.setItem('hasSeenTour', 'true');
	}
}