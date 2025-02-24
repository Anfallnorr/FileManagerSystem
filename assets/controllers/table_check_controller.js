import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static targets = ["rowInputs", "itemDelete", "itemDirDelete"];
	static values = {
		check: Array,
		checkDir: Array
	}

	connect()
	{
		console.log("Contrôleur table-check connecté !");
	}

	changes(event) {
		this.checks(event.target.checked);
	}

	checks = (value = false) => {
		let check = [];

		this.rowInputsTargets.forEach(input => {
			input.checked = value;

			if (value) {
				check.push(input.value);
			}
		});

		this.checkValue = check; // Met à jour la valeur réactive

		this.updateHiddenInput();
	}

	check(event) {
		const rowValue = event.target.value;

		if (event.target.checked) {
			this.checkValue = [...this.checkValue, rowValue]; // Ajout
		} else {
			this.checkValue = this.checkValue.filter(v => v !== rowValue); // Suppression
		}

		this.updateHiddenInput();
	}

	updateHiddenInput() {
		if (this.hasItemDeleteTarget) {
			this.itemDeleteTarget.value = JSON.stringify(this.checkValue);
		}
	}

	checkDir(event) {
		const rowValue = event.target.value;

		if (event.target.checked) {
			this.checkDirValue = [...this.checkDirValue, rowValue]; // Ajout
		} else {
			this.checkDirValue = this.checkDirValue.filter(v => v !== rowValue); // Suppression
		}

		this.updateHiddenDirInput();
	}

	updateHiddenDirInput() {
		if (this.hasItemDirDeleteTarget) {
			this.itemDirDeleteTarget.value = JSON.stringify(this.checkDirValue);
		}
	}
}
