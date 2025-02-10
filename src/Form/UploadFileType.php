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
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('file', FileType::class, [
				'label' => new TranslatableMessage('file_manager.select_a_file'),
				'mapped' => false,
				'multiple' => true,
				'required' => true,
				/* 'row_attr' => [
					'class' => 'hidden'
				], */
				'attr' => [
					'class' => 'dropzone-field',
					'placeholder' => new TranslatableMessage('file_manager.drag_and_drop_or_browse', [], 'forms'),
					'data-user' => $options['user'],
					'data-url' => $options['route'],
					'data-current-folder' => $options['current_folder'],
					'data-cancel-label' => $this->translator->trans('file_manager.cancel', [], 'forms'),
					'data-remove-label' => $this->translator->trans('file_manager.clear', [], 'forms'),
					'data-max-size-folder' => 50,
					'data-max-filesize' => 500,
					'data-max-files' => 50,
					'data-param-name' => 'file_path'
				],
			])
			->add('submit', SubmitType::class, [
				'label' => new TranslatableMessage('file_manager.send')
			]);
	}

    public function configureOptions(OptionsResolver $resolver)
    {
        // $resolver->setDefaults([]);
        $resolver->setDefaults([
            // 'data_class' => null,
            'user' => null, // User parameter for route: userId
            'route' => null, // Route to the dropzone form
            'current_folder' => null,
		]);
    }
}
