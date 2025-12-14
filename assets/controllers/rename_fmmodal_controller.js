import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
	static targets = ['filenameTitle', 'currentFileInput'];
	/* static values = {
		name: String
	} */

	connect()
	{

	}

	open(event)
	{
		// console.log(this.currentFileInputTarget);
		// console.log(this.filenameTitleTarget);
		// console.log(this.nameValue);
		const trigger = event.relatedTarget; // élément ayant déclenché l'ouverture
		// const filename = trigger.getAttribute('title').replace('Rename ', '').trim();
		// const filename = this.nameValue;

		if (!trigger) {
			console.warn("No relatedTarget");
			return;
		}

		const filename = trigger.dataset.name;

		// Met à jour le titre
		this.filenameTitleTarget.textContent = filename;
		// this.filenameTitleTarget.textContent = this.nameValue;

		// Met à jour le champ du formulaire
		this.currentFileInputTarget.value = filename;
		// this.currentFileInputTarget.value = this.nameValue;
	}
}
