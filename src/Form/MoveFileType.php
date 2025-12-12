<?php

namespace Anfallnorr\FileManagerSystem\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
			->add(child: 'newPath', type: TextType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.new_path')
			])
			->add(child: 'submit', type: SubmitType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.move')
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(defaults: [
			'current_folder' => null,
		]);
	}
}
