import { Controller } from "@hotwired/stimulus";

/**
 * Update 20250917
 * assets/controllers/table_check_controller.js
 */
export default class extends Controller {
	static targets = ["rowInputs", "itemDownload", "itemDirDownload", "itemMove", "itemDirMove", "itemRename", "itemDirRename", "itemDelete", "itemDirDelete"];
	static values = {
		check: Array,
		checkDir: Array
	}

	connect() {
		console.log("Contrôleur table-check connecté !");
		// this.checkValue = []; // Initialise le tableau
	}

	changes(event) {
		// console.log(event.target.closest('table'));
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
		// console.log(this.checkValue);
	}

	check(event) {
		const rowValue = event.target.value;

		if (event.target.checked) {
			this.checkValue = [...this.checkValue, rowValue]; // Ajout
		} else {
			this.checkValue = this.checkValue.filter(v => v !== rowValue); // Suppression
		}

		this.updateHiddenInput();
		// console.log(this.checkValue);
	}

	updateHiddenInput() {
		if (this.hasItemDeleteTarget) {
			this.itemDeleteTarget.value = JSON.stringify(this.checkValue);
		}
		if (this.hasItemDownloadTarget) {
			this.itemDownloadTarget.value = JSON.stringify(this.checkValue);
		}
		if (this.hasItemMoveTarget) {
			this.itemMoveTarget.value = JSON.stringify(this.checkValue);
		}
		if (this.hasItemRenameTarget) {
			this.itemRenameTarget.value = JSON.stringify(this.checkValue);
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
		// console.log(this.checkDirValue);
	}

	updateHiddenDirInput() {
		if (this.hasItemDirDeleteTarget) {
			this.itemDirDeleteTarget.value = JSON.stringify(this.checkDirValue);
		}
		if (this.hasItemDirDownloadTarget) {
			this.itemDirDownloadTarget.value = JSON.stringify(this.checkDirValue);
		}
		if (this.hasItemDirMoveTarget) {
			this.itemDirMoveTarget.value = JSON.stringify(this.checkDirValue);
		}
		if (this.hasItemDirRenameTarget) {
			this.itemDirRenameTarget.value = JSON.stringify(this.checkDirValue);
		}
	}
}
