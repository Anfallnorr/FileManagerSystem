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
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('folderName', TextType::class, [
				'label' => new TranslatableMessage('file_manager.folder_name'),
				'help' => "Use `+` to add multiple folders at the same level, `>` to add nested folders. Example: `folder1+folder2`, `folder1-1>folder1-2`"
				// 'help' => "Utilisez `+` pour ajouter plusieurs dossiers de même niveau, `>` pour ajouter des dossiers imbriqués. Exemple : `folder1+folder2`, `folder1-1>folder1-2`"
				// 'help' => new TranslatableMessage('file_manager.folder_name_help', ['%example%' => "`folder1+folder2`, `folder1-1>folder1-2`"], 'forms')
			])
			->add('submit', SubmitType::class, [
				'label' => new TranslatableMessage('file_manager.create')
			]);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([]);
	}
}
