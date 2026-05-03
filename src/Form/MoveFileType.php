<?php

namespace Anfallnorr\FileManagerSystem\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
// use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class MoveFileType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		// dd($options);
		$builder
			->add(child: 'currentPath', type: HiddenType::class, options: [
				'data' => $options['current_folder']
				// 'data' => '/toto'
			])
			// ->add(child: 'newPath', type: TextType::class, options: [
			->add(child: 'newPath', type: ChoiceType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.new_path'),
				'choices' => $options['folder_list']
			])
			->add(child: 'submit', type: SubmitType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.move')
			])
		;

		$submitOptions = [
			'label' => new TranslatableMessage(message: 'file_manager.move'),
			'attr' => ['class' => $options['submit_class']],
		];

		if (!empty($options['submit_icon'])) {
			$submitOptions['icon_before'] = $options['submit_icon'];
		}

		$builder->add(
			child: 'submit',
			type: SubmitType::class,
			options: $submitOptions
		);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(defaults: [
			'current_folder' => null,
			'folder_list' => null,
			'submit_class' => 'btn-primary rounded-pill px-4',
			'submit_icon' => null
		]);
	}
}
