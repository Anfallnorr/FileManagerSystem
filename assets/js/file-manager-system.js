// (function () {
	/* ********************************************************* */
	/* ******************* CHECK ALL IN TABLE ****************** */
	/* ********************************************************* */
	console.log('Init File manager system JS');
	/* var checkFolders = new Array;
	var check = new Array;

	$('#check_all').on('click',function() {
		check = []; // Vide le tableau
		// console.log(this);

		if (this.checked) {
			$('.table tbody .md-checkbox').each(function() {
				this.checked = true;
				check.push(this.value);
			});
		} else {
			$('.table tbody .md-checkbox').each(function() {
				this.checked = false;
			});
		}

		$('[name="filesToDelete"]').val(JSON.stringify(check));
		console.log(check);
	});
	$('#data_files-list .md-checkbox').on('click',function() {
		if (this.checked) {
			check.push(this.value);
		} else {
			check = check.filter(item => item !== this.value); // Supprime correctement l'élément
		}

		$('[name="filesToDelete"]').val(JSON.stringify(check));
		console.log(check);
	});
	$('#data_folders-list .md-checkbox').on('click',function() {
		if (this.checked) {
			checkFolders.push(this.value);
		} else {
			checkFolders = checkFolders.filter(item => item !== this.value); // Supprime correctement l'élément
		}

		$('[name="foldersToDelete"]').val(JSON.stringify(checkFolders));
		console.log(checkFolders);
	}); */
// });

/* // const modalMoveFile = document.getElementById('modal_move_file')
const modalMoveFile = $('#modal_move_file')
if (modalMoveFile) {
//   modalMoveFile.addEventListener('show.bs.modal', event => {
  modalMoveFile.on('show.bs.modal', event => {
    // Button that triggered the modal
    const button = $(event.relatedTarget);
	// console.log(button);

    const target = $(event.currentTarget);
	console.log(target);

	const filename = button.data('name');
	// console.log(filename);
	// console.log(event);
	// console.log(button.data('name'));

	$(target).find('#filename').text(filename);
	$(target).find('#move_file_currentPath').val(filename);
  });
  modalMoveFile.on('hidden.bs.modal', event => {
    // Button that triggered the modal
    const button = $(event.relatedTarget);
	console.log(button);
  });
} */
