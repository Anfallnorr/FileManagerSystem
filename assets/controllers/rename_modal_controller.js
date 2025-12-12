import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
	static targets = ['filenameSpan', 'currentFileInput'];

	connect() {}

	open(event) {
		const trigger = event.relatedTarget; // élément ayant déclenché l'ouverture
		const filename = trigger.getAttribute('title').replace('Rename ', '').trim();

		// Met à jour le titre
		this.filenameSpanTarget.textContent = filename;

		// Met à jour le champ du formulaire
		this.currentFileInputTarget.value = filename;
	}
}
