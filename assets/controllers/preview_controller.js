import { Controller } from "@hotwired/stimulus";
// import { Modal } from "bootstrap";

export default class extends Controller {
	static targets = ["previewItem"];
	// static values = {
	// 	check: Array,
	// 	checkDir: Array
	// }

	connect()
	{
		// console.log("Contrôleur preview connecté !");

		// Ajoute la modal au DOM si elle n'existe pas encore
		if (!document.getElementById('modal_viewer')) {
			console.log('toto');
			document.body.insertAdjacentHTML('beforeend', this.getModal());
		}

		document.getElementById('modal_viewer').addEventListener('hidden.bs.modal', () => {
			document.body.focus();

			const modalContent = document.querySelector('#modal_viewer .modal-body #content_result');
			if (modalContent) {
				modalContent.classList.remove('processing');
				modalContent.innerHTML = '';
			}
		});
	}

	dblClick(event)
	{
		const row = event.currentTarget; // Récupère le <tr> cliqué
		const previewItem = row.querySelector('[data-preview-target="previewItem"]'); // Cherche l'élément enfant

		if (previewItem) {
			this.preview(previewItem);
		}
	}

	showItem(event)
	{
		const element = event.target; // Récupère l'élément cliqué
		
		if (element) {
			this.preview(element); // Vérifie la ligne cliquée
		}
	}

	preview(data)
	{
		// Sélectionne l'élément de la modal
		const modalViewer = new bootstrap.Modal(document.getElementById('modal_viewer')),
			modalContent = document.querySelector('#modal_viewer .modal-body #content_result');

		modalContent.innerHTML = '';
		modalContent.classList.add('processing');
		modalViewer.show();

		const ext = data.dataset.ext,
			srcFile = data.dataset.srcFile,
			filename = data.dataset.filename;

		const arrImg = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'svg', 'ico', 'tif', 'webp'],
			arrDoc = ['doc', 'docx', 'odf', 'odp', 'ods', 'odt', 'otf', 'pdf', 'ppt', 'csv', 'pps', 'pptx', 'txt'], // 'xls', 'xlsx',
			arrAudio = ['mp3', 'wav', 'wave', 'wma', 'aac', 'mid', 'midi', 'ogg'],
			arrVideo = ['mp4', 'webm', 'ogg'],
			arrCode = ['html', 'css', 'js', 'sql'];

		if (arrImg.includes(ext)) {
			this.getImageFromUrl(srcFile, 'modal-img').then((imgElement) => {
				modalContent.appendChild(imgElement);
				modalContent.classList.remove('processing');
			}).catch(console.error);
		}
		else if (arrDoc.includes(ext) || arrCode.includes(ext)) {
			modalContent.innerHTML = `<iframe src="${srcFile}" class="modal-iframe" title="${filename}" allow="fullscreen"></iframe>`;
			modalContent.classList.remove('processing');
		}
		else if (arrAudio.includes(ext)) {
			modalContent.innerHTML = `<audio class="modal-audio" controls><source src="${srcFile}" type="audio/${ext}">Your browser does not support the audio element.</audio>`;
			modalContent.classList.remove('processing');
		}
		else if (arrVideo.includes(ext)) {
			modalContent.innerHTML = `<video class="modal-video" controls><source src="${srcFile}" type="video/${ext}">Your browser does not support the video tag.</video>`;
			modalContent.classList.remove('processing');
		}
		else {
			modalContent.innerHTML = `<div id="notification_modal" class="alert bg-dark border-0 border-start border-5 border-warning alert-dismissible fade show">
				<div class="d-flex align-items-center">
					<div class="font-35 text-white"><i class="bx bx-info-circle"></i></div>
					<div class="ms-3">
						<h6 class="mb-0 text-white">Warning</h6>
						<div class="text-white">Aucun aperçu disponible pour <strong>${filename}</strong></div>
					</div>
				</div>
			</div>`;
			modalContent.classList.remove('processing');
		}
	}

	/**
	 * La fonction getImageFromURL permet de charger une image à partir
	 * d'une URL donnée et de la transformer en objet img HTML.
	 * Le paramètre classImg permet d'ajouter une classe CSS à l'élément img généré.
	 *
	 * @param url
	 * @param classImg
	 * @return Promise
	 */
	getImageFromUrl(url, classImg = '')
	{
		url = window.location.origin + url;

		return new Promise((resolve, reject) => {
			const img = new Image();
			img.crossOrigin = 'Anonymous';

			img.onload = () => {
				const canvas = document.createElement('canvas');
				const ctx = canvas.getContext('2d');
				canvas.width = img.width;
				canvas.height = img.height;
				ctx.drawImage(img, 0, 0);
				
				const dataURL = canvas.toDataURL('image/png');
				const imgElement = document.createElement('img');
				imgElement.src = dataURL;
				imgElement.className = classImg;
				resolve(imgElement);
			};

			img.onerror = () => reject(new Error('Failed to load image'));
			img.src = url;
		});
	}

	getModal(id = 'modal_viewer')
	{
		return `<div class="modal fade" id="${id}" tabindex="-1" aria-labelledby="${id}_label" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title h4" id="${id}_label">Aperçu</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
					</div>
					<div class="modal-body">
						<div id="content_result"></div>
						<div class="process justify-content-center align-items-center">
							<div class="spinner-border text-green spinner-w10" role="status"></div>
						</div>
					</div>
				</div>
			</div>
		</div>`;
	}
}
