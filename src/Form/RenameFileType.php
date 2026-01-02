<?php

namespace Anfallnorr\FileManagerSystem\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Translation\TranslatableMessage;

class RenameFileType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(child: 'currentPath', type: HiddenType::class, options: [
				'data' => $options['current_folder']
				// 'data' => '/toto'
			])
			->add(child: 'currentFileName', type: HiddenType::class, options: [
				'attr' => [
					'data-rename-fmmodal-target' => "currentFileInput",
					'data-fmmodal-rename-target' => "currentFileInput"
				]
			])
			->add(child: 'newFileName', type: TextType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.new_file_name'), // 'Nouveau nom du fichier'
				'attr' => [
					'data-rename-fmmodal-target' => "newFileInput",
					'data-fmmodal-rename-target' => "newFileInput"
				]
			])
			->add(child: 'submit', type: SubmitType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.rename') // 'Renommer'
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(defaults: [
			'current_folder' => null
		]);
	}
}
