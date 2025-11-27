<?php

namespace Anfallnorr\FileManagerSystem\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Translation\TranslatableMessage;

class CreateFolderType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(child: 'folderName', type: TextType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.folder_name'),
				'help' => new TranslatableMessage(message: 'file_manager.folder_name_help')
				// 'help' => "Utilisez `+` pour ajouter plusieurs dossiers de même niveau, `>` pour ajouter des dossiers imbriqués. Exemple : `folder1+folder2`, `folder1-1>folder1-2`"
				// 'help' => new TranslatableMessage('file_manager.folder_name_help', ['%example%' => "`folder1+folder2`, `folder1-1>folder1-2`"], 'forms')
			])
			->add(child: 'submit', type: SubmitType::class, options: [
				'label' => new TranslatableMessage(message: 'file_manager.create')
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(defaults: []);
	}
}
