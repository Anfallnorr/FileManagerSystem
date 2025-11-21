<?php

namespace Anfallnorr\FileManagerSystem\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadFileType extends AbstractType
{
	public function __construct(
		private TranslatorInterface $translator
	) {}

	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(child: 'file', type: FileType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.select_a_file'),
				'mapped' => false,
				'multiple' => true,
				'required' => true,
				/* 'row_attr' => [
					'class' => 'hidden' // Optional
				], */
				'attr' => [
					'class' => 'dropzone-field',
					'placeholder' => new TranslatableMessage(message: 'file_manager.drag_and_drop_or_browse'),
					'data-user' => $options['user'], // id user
					'data-url' => $options['route'], // generateUrl with params
					'data-current-folder' => $options['current_folder'], // {folder} route param
					'data-cancel-label' => $this->translator->trans(id: 'file_manager.cancel'),
					'data-remove-label' => $this->translator->trans(id: 'file_manager.clear'),
					'data-max-size-folder' => 100, // Personal folder limit to avoid overload in percentage
					'data-max-filesize' => 2, // PHP upload_max_filesize param
					'data-max-files' => 20, // PHP max_file_uploads param
					'data-param-name' => 'file_manager_system', // Override for Dropzone JS
					'data-dropzone-target' => 'dropzoneField' // For Stimulus Dropzone JS
				],
			])
            /* ->add('rootPath', HiddenType::class, [
                'data' => base64_encode($options['root_path']), // $this->getParameter('kernel.project_dir')
            ]) */
			->add(child: 'submit', type: SubmitType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.send')
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		// $resolver->setDefaults([]);
		$resolver->setDefaults(defaults: [
			// 'data_class' => null,
			'user' => null, // User parameter for route: userId
			'route' => null, // Route to the dropzone form, generateUrl with params
			'current_folder' => null, // {folder} route param
			// 'root_path' => null, // $this->getParameter('kernel.project_dir')
		]);
	}
}
