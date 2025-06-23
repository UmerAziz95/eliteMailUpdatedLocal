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
		attachTo: { element: '.reward', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
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
