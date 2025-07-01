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

		// case "edit-order":
		// 	initializeEditOrder();
		// 	break;

		// case "support":
		// 	initializeSupport();
		// 	break;

		// case "subscription":
		// 	initializeSubscription();
		// 	break;

		// case "invoices":
		// 	initializeInvoices();
		// 	break;

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
		title: 'Welcome to Your Dashboard Tour',
		text: `
			<p class="small">To begin, click the <strong class="text-white"> 'Start Tour'</strong> button located on the top-right or bottom corner (wherever it is placed in your layout).</p>
			<p class="small">You can follow the steps to understand your <em>Inbox Status</em>, <em>Subscription Overview</em>, <em>Orders</em>, <em>Support Tracker</em>, and more.</p>
			<p class="small">You can exit anytime or revisit the tour later.<br><br><strong class="theme-text">Let’s get started!</strong></p>
		`,
		attachTo: { element: '.tour-btn', on: 'bottom' },
		buttons: [
			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Earn Rewards with Project Inbox',
		text: `
			<p class="small">Join our affiliate program and start earning monthly recurring revenue!</p>
			<p class="small">Every time someone signs up using your referral link, you earn rewards — <strong>for a lifetime</strong>.</p>
			<p class="small">Click the <strong class="text-white">'Join Now!'</strong> button to get started and access your affiliate dashboard.</p>
		`,
		attachTo: { element: '.reward', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Inbox Status',
		text: `
			<p class="small">This section gives you a quick overview of all your inboxes.</p>
			<p class="small"><strong class="text-white">Total Inboxes</strong> shows the overall count, while the color-coded stats break down how many are <span style="color:#00d084"><strong>Active</strong></span> and how many have <span style="color:#f5a623"><strong>Pending Issues</strong></span>.</p>
			<p class="small">Monitor this section regularly to keep your inboxes optimized and running smoothly.</p>
		`,
		attachTo: { element: '.inbox', on: 'right' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Most Recent Subscription Overview',
		text: `
			<p class="small">This section displays the details of your latest subscription.</p>
			<p class="small">You can view the <strong>amount billed</strong>, <strong>billing status</strong>, and important dates like the <strong>next billing date</strong> and <strong>last billing date</strong>.</p>
			<p class="small">Hover over this card to explore additional features and manage your plan more efficiently.</p>
		`,
		attachTo: { element: '.recent', on: 'left' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Orders Overview',
		text: `
			<p class="small">Track the status of your recent orders right here.</p>
			<p class="small">This section summarizes your <strong>total orders</strong>, and highlights which ones are <span style="color:#00d084"><strong>fulfilled</strong></span> or still <span style="color:#f5a623"><strong>pending</strong></span>.</p>
			<p class="small">Use this snapshot to stay on top of order management and delivery updates.</p>
		`,
		attachTo: { element: '.review', on: 'top' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Support Tracker',
		text: `
			<p class="small">This section helps you stay updated on all your support tickets.</p>
			<p class="small">Quickly see how many tickets are <strong>open</strong>, <span style="color:#00d084"><strong>resolved</strong></span>, or <span style="color:#f5a623"><strong>pending</strong></span>.</p>
			<p class="small">Click on this card to dive deeper into each case or follow up directly with the support team.</p>
		`,
		attachTo: { element: '.support', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Next', action() { return this.next(); } },
		]
	});

	tour.addStep({
		title: 'Order History',
		text: `
			<p class="small">This section provides a detailed log of all your past orders.</p>
			<p class="small">You can review order <strong>dates</strong>, <strong>statuses</strong>, and <strong>amounts</strong> at a glance.</p>
			<p class="small">Use this history to keep track of previous transactions or download receipts when needed.</p>
		`,
		attachTo: { element: '.history', on: 'bottom' },
		buttons: [
			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
			{ text: 'Finish', action() { return this.complete(); } },
		]
	});

	tour.on('start', () => {
		const btn = document.getElementById("start-tour");
		if (btn) btn.disabled = true;
	});

	tour.on('complete', () => {
		const btn = document.getElementById("start-tour");
		if (btn) btn.disabled = false;
	});

	tour.on('cancel', () => {
		const btn = document.getElementById("start-tour");
		if (btn) btn.disabled = false;
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


// function initializeOrder() {
// 	const tour = new Shepherd.Tour({
// 		defaultStepOptions: {
// 			classes: 'shepherd-theme-arrows',
// 			scrollTo: { behavior: 'smooth', block: 'center' },
// 			cancelIcon: { enabled: true },
// 		},
// 		useModalOverlay: true,
// 	});

// 	// Add all your tour steps
// 	tour.addStep({
// 		title: 'Order Overview Counters',
// 		text: `
// 			<p class="small">This section provides a quick snapshot of your order performance.</p>
// 			<p class="small">Each counter shows the total number of orders by status — such as <strong>Total Orders</strong>, <span style="color:#f5a623"><strong>Pending</strong></span>, <span style="color:#00d084"><strong>Completed</strong></span>, and <span style="color:#d0021b"><strong>Cancelled</strong></span> etc.</p>
// 			<p class="small">Use these at-a-glance metrics to monitor your daily operations and prioritize actions.</p>
// 		`,
// 		attachTo: { element: '.counters', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Order Table',
// 		text: `
// 			<p class="small">This table lists all your orders with detailed information including <strong>Order ID</strong>, <strong>Customer Name</strong>, <strong>Status</strong>, <strong>Amount</strong>, and <strong>Date</strong>.</p>
// 			<p class="small">You can sort or filter the table to quickly find specific orders. Click on any row to view full order details or take action.</p>
// 		`,
// 		attachTo: { element: '.table-responsive', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Order Actions',
// 		text: `
// 			<p class="small">Each row includes an <strong>Actions</strong> button that lets you manage individual orders.</p>
// 			<p class="small">From here, you can <strong>view details</strong>, <strong>edit orders</strong>, <strong>change status</strong>, or <strong>delete</strong> them — depending on your permissions.</p>
// 			<p class="small">Use this menu to efficiently manage customer requests and update orders in real-time.</p>
// 		`,
// 		attachTo: { element: '.dropdown button', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Finish', action() { return this.complete(); } },
// 		]
// 	});

// 	// Manual start button (optional)
// 	const startTourButton = document.getElementById("start-tour");
// 	if (startTourButton) {
// 		startTourButton.addEventListener("click", (event) => {
// 			event.preventDefault();
// 			tour.start();
// 		});
// 	}

// 	// Auto-start if first visit
// 	const hasSeenTour = localStorage.getItem('hasSeenTour');
// 	if (!hasSeenTour) {
// 		tour.start();
// 		localStorage.setItem('hasSeenTour', 'true');
// 	}
// }


// function initializeEditOrder() {
// 	const tour = new Shepherd.Tour({
// 		defaultStepOptions: {
// 			classes: 'shepherd-theme-arrows',
// 			scrollTo: { behavior: 'smooth', block: 'center' },
// 			cancelIcon: { enabled: true },
// 		},
// 		useModalOverlay: true,
// 	});

// 	tour.addStep({
// 		text: `
// 			<p class="small">Click <strong>Import Order</strong> to quickly load order data from a file.</p>
// 			<p class="small">This is useful when you have multiple inboxes or domain data ready in a structured format.</p>
// 		`,
// 		attachTo: { element: '.import-btn', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
// 			<p class="small">Enter the <strong>Domain Forwarding Destination URL</strong>.</p>
// 			<p class="small">This is where visitors will be redirected when they access your project domain.</p>
// 		`,
// 		attachTo: { element: '.domain-forwarding', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Select your <strong>Domain Hosting Platform</strong> (e.g., GoDaddy, Namecheap).</p>
//   <p class="small">This helps the system identify how to interact with your domain settings.</p>
// `,
// 		attachTo: { element: '.domain-hosting', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Provide your login credentials for the selected hosting platform.</p>
//   <p class="small">This allows automated access when necessary.</p>
// `,
// 		attachTo: { element: '.platform', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Add the list of <strong>Domains</strong> you want to use in this order.</p>
//   <p class="small">You can paste them all in bulk (comma-separated). Make sure to stay within your plan’s inbox limit.</p>
// `,
// 		attachTo: { element: '.domains', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Choose the <strong>Email Sending Platform</strong> you want to connect (e.g., Mailgun, SendGrid).</p>
//   <p class="small">This is the service used to send campaign emails from the project.</p>
// `,
// 		attachTo: { element: '.sending-platform', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Enter the <strong>API credentials</strong> for your sending platform.</p>
//   <p class="small">Include your sequencer login and password to allow email automation.</p>
// `,
// 		attachTo: { element: '.sending-platform-fields', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Define how many <strong>inboxes per domain</strong> you need.</p>
//   <p class="small">This helps balance deliverability across multiple inboxes.</p>
// `,
// 		attachTo: { element: '.inboxes-per-domain', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Set the <strong>Total Number of Inboxes</strong> for this order.</p>
//   <p class="small">This value should match or be less than your current available limit.</p>
// `,
// 		attachTo: { element: '.total-inbox', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">This bar shows your <strong>Remaining Inbox Limit</strong>.</p>
//   <p class="small">If you exceed your plan quota, you’ll need to upgrade or reduce the number of inboxes.</p>
// `,
// 		attachTo: { element: '.remaining', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Enter the <strong>First Name</strong> to be used for this email identity.</p>
//   <p class="small">This name will appear in the "From" field of emails sent.</p>
// `,
// 		attachTo: { element: '.first-name', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Enter the <strong>Last Name</strong> of the email sender.</p>
//   <p class="small">Along with the first name, this creates a more personalized identity.</p>
// `,
// 		attachTo: { element: '.last-name', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Select an <strong>Inbox Prefix Variant</strong> (e.g., info@, mail@, support@).</p>
//   <p class="small">This defines how inbox addresses will be formatted.</p>
// `,
// 		attachTo: { element: '.prefix-variants', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Set the <strong>Email Account Password</strong> to secure inbox access.</p>
//   <p class="small">Make sure it meets the minimum security requirements.</p>
// `,
// 		attachTo: { element: '.email-password', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Optionally add a <strong>Profile Picture Link</strong> for the email persona.</p>
//   <p class="small">This will be used in supported email clients for better visual identity.</p>
// `,
// 		attachTo: { element: '.email-picture-link', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Enter the <strong>Master Inbox or Contact Email</strong>.</p>
//   <p class="small">This is where you will receive important alerts or communication summaries.</p>
// `,
// 		attachTo: { element: '.master-inbox', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		text: `
//   <p class="small">Click <strong>Purchase</strong> to finalize and create the mailboxes for this order.</p>
//   <p class="small">Make sure all fields are correctly filled in before proceeding.</p>
// `,
// 		attachTo: { element: '.purchase-btn', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	// Manual start button (optional)
// 	const startTourButton = document.getElementById("start-tour");
// 	if (startTourButton) {
// 		startTourButton.addEventListener("click", (event) => {
// 			event.preventDefault();
// 			tour.start();
// 		});
// 	}

// 	// Auto-start if first visit
// 	const hasSeenTour = localStorage.getItem('hasSeenTour');
// 	if (!hasSeenTour) {
// 		tour.start();
// 		localStorage.setItem('hasSeenTour', 'true');
// 	}
// }


// function initializeSupport() {
// 	const tour = new Shepherd.Tour({
// 		defaultStepOptions: {
// 			classes: 'shepherd-theme-arrows',
// 			scrollTo: { behavior: 'smooth', block: 'center' },
// 			cancelIcon: { enabled: true },
// 		},
// 		useModalOverlay: true,
// 	});

// 	// Add all your tour steps
// 	tour.addStep({
// 		title: 'Support Ticket Counters',
// 		text: `
// 			<p class="small">Here you can see a quick summary of your current support tickets.</p>
// 			<p class="small">Counters display the number of <span style="color:#f5a623"><strong>Open</strong></span>, <span style="color:#00d084"><strong>In Progress</strong></span>, and <span style="color:#d0021b"><strong>Closed</strong></span> tickets.</p>
// 			<p class="small">Use this overview to quickly assess the workload and prioritize ticket handling.</p>
// 		`,
// 		attachTo: { element: '.counters', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Create a New Ticket',
// 		text: `
// 			<p class="small">Need help or want to raise an issue?</p>
// 			<p class="small">Click the <strong>"Create New Ticket"</strong> button to open a support request. You can describe your problem, set priority, and attach any relevant files.</p>
// 			<p class="small">Our support team will get back to you as soon as possible.</p>
// 		`,
// 		attachTo: { element: '.create-ticket', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Support Ticket List',
// 		text: `
// 			<p class="small">This table displays all your support tickets in one place.</p>
// 			<p class="small">You can quickly check the <strong>ticket ID</strong>, <strong>subject</strong>, <strong>status</strong>, <strong>priority</strong>, and <strong>last update time</strong>.</p>
// 			<p class="small">Click on any ticket row to view its full details, reply to support, or track the resolution progress.</p>
// 		`,
// 		attachTo: { element: '.table-responsive', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'View Ticket Details',
// 		text: `
// 			<p class="small">Click the <strong>view icon</strong> in the Actions column to open a specific support ticket.</p>
// 			<p class="small">You’ll see the full conversation, attachments, ticket status, and any internal notes.</p>
// 			<p class="small">From there, you can reply, update the ticket status, or escalate the issue as needed.</p>
// 		`,
// 		attachTo: { element: '.fa-eye', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Finish', action() { return this.complete(); } },
// 		]
// 	});

// 	// Manual start button (optional)
// 	const startTourButton = document.getElementById("start-tour");
// 	if (startTourButton) {
// 		startTourButton.addEventListener("click", (event) => {
// 			event.preventDefault();
// 			tour.start();
// 		});
// 	}

// 	// Auto-start if first visit
// 	const hasSeenTour = localStorage.getItem('hasSeenTour');
// 	if (!hasSeenTour) {
// 		tour.start();
// 		localStorage.setItem('hasSeenTour', 'true');
// 	}
// }


// function initializeSubscription() {
// 	const tour = new Shepherd.Tour({
// 		defaultStepOptions: {
// 			classes: 'shepherd-theme-arrows',
// 			scrollTo: { behavior: 'smooth', block: 'center' },
// 			cancelIcon: { enabled: true },
// 		},
// 		useModalOverlay: true,
// 	});

// 	// Add all your tour steps
// 	tour.addStep({
// 		title: 'Subscription Overview Counters',
// 		text: `
// 			<p class="small">Get a snapshot of your subscription activity right here.</p>
// 			<p class="small">These counters display totals for <strong class="success">Active</strong>, <strong class="text-danger">Cancel</strong> subscriptions.</p>
// 			<p class="small">They help you quickly assess the overall status of your customers or plans at a glance.</p>
// 		`,
// 		attachTo: { element: '.counters', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Advanced Filters',
// 		text: `
// 			<p class="small">Use the <strong>Advanced Filters</strong> to quickly narrow down subscriptions based on criteria like <strong>status</strong>, <strong>plan type</strong>, <strong>start date</strong>, or <strong>expiry date</strong>.</p>
// 			<p class="small">This helps you find specific subscriptions faster and manage them more efficiently.</p>
// 			<p class="small">You can combine multiple filters for more precise results.</p>
// 		`,
// 		attachTo: { element: '.filter', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Subscription Table',
// 		text: `
// 			<p class="small">This table displays all your subscriptions in one place.</p>
// 			<p class="small">You can view details like <strong>Subscription ID</strong>, <strong>Customer Name</strong>, <strong>Plan</strong>, <strong>Status</strong>, <strong>Start & End Dates</strong>, and <strong>Billing Info</strong>.</p>
// 			<p class="small">Click on any row or use the action buttons to view, edit, or manage a subscription.</p>
// 		`,
// 		attachTo: { element: '.table-responsive', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Manage Subscriptions',
// 		text: `
// 			<p class"small">Each row includes an <strong>Action button</strong> that allows you to manage individual subscriptions.</p>
// 			<p class"small">Quick access to actions makes subscription management seamless and efficient.</p>
// 		`,
// 		attachTo: { element: '.dropdown button', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Finish', action() { return this.complete(); } },
// 		]
// 	});

// 	// Manual start button (optional)
// 	const startTourButton = document.getElementById("start-tour");
// 	if (startTourButton) {
// 		startTourButton.addEventListener("click", (event) => {
// 			event.preventDefault();
// 			tour.start();
// 		});
// 	}

// 	// Auto-start if first visit
// 	const hasSeenTour = localStorage.getItem('hasSeenTour');
// 	if (!hasSeenTour) {
// 		tour.start();
// 		localStorage.setItem('hasSeenTour', 'true');
// 	}
// }


// function initializeInvoices() {
// 	const tour = new Shepherd.Tour({
// 		defaultStepOptions: {
// 			classes: 'shepherd-theme-arrows',
// 			scrollTo: { behavior: 'smooth', block: 'center' },
// 			cancelIcon: { enabled: true },
// 		},
// 		useModalOverlay: true,
// 	});

// 	// Add all your tour steps
// 	tour.addStep({
// 		title: 'Invoice Overview',
// 		text: `
// 			<p class="small">These counters give you a quick snapshot of your billing activity.</p>
// 			<p class="small">They show totals for <strong>All Invoices</strong>, <span style="color:#00d084"><strong>Paid</strong></span>, <span style="color:#f5a623"><strong>Pending</strong></span>, and <span style="color:#d0021b"><strong>Overdue</strong></span> invoices.</p>
// 			<p class="small">Use these insights to monitor cash flow and identify invoices that may need your attention.</p>
// 		`,
// 		attachTo: { element: '.counters', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Cancel', action() { return this.cancel(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Invoice Table',
// 		text: `
// 			<p class="small">This table lists all your invoices in detail.</p>
// 			<p class="small">You can view key information like <strong>Invoice Number</strong>, <strong>Client Name</strong>, <strong>Status</strong>, <strong>Amount</strong>, <strong>Due Date</strong>, and <strong>Issue Date</strong>.</p>
// 			<p class="small">Click a row or use the actions menu to view, download, or manage each invoice.</p>
// 		`,
// 		attachTo: { element: '.table-responsive', on: 'top' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Next', action() { return this.next(); } },
// 		]
// 	});

// 	tour.addStep({
// 		title: 'Invoice Actions',
// 		text: `
// 			<p class="small">Use the <strong>Actions dropdown</strong> in each row to manage your invoices.</p>
// 			<p class="small">You can <strong>view details</strong>, <strong>download PDF</strong>, <strong>mark as paid</strong>, <strong>send a reminder</strong>, or even <strong>delete</strong> an invoice — all in one place.</p>
// 			<p class="small">This menu helps you take quick action without leaving the table.</p>
// 		`,
// 		attachTo: { element: '.dropdown button', on: 'bottom' },
// 		buttons: [
// 			{ text: 'Back', action() { return this.back(); }, classes: 'shepherd-button-secondary' },
// 			{ text: 'Finish', action() { return this.complete(); } },
// 		]
// 	});

// 	// Manual start button (optional)
// 	const startTourButton = document.getElementById("start-tour");
// 	if (startTourButton) {
// 		startTourButton.addEventListener("click", (event) => {
// 			event.preventDefault();
// 			tour.start();
// 		});
// 	}

// 	// Auto-start if first visit
// 	const hasSeenTour = localStorage.getItem('hasSeenTour');
// 	if (!hasSeenTour) {
// 		tour.start();
// 		localStorage.setItem('hasSeenTour', 'true');
// 	}
// }