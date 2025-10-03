<?php

namespace App\Form;

use App\Entity\User;
//use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;


class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Correo electrónico',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('roles', ChoiceType::class, [
                    'choices'  => [
                        'Usuario' => 'ROLE_USER',
                        'Administrador' => 'ROLE_ADMIN',
                        'Picker' => 'ROLE_PICKER',
                        'Prueba' => 'ROLE_TEST',
                    ],
                    'expanded' => false,   // muestra como checkboxes
                    'multiple' => true,   // permite seleccionar más de uno
                    'label'    => 'Roles',])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña (dejar en blanco si no se cambia)',
                'mapped' => false,       // no se asigna directo a la entidad
                'required' => false,     // opcional
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Introduce una nueva contraseña'],
                    ])
            ->add('isActive', CheckboxType::class, [
                'label'    => '¿Usuario activo?',
                'required' => false,     ])
            ->add('isGoogleAuthenticatorEnabled', CheckboxType::class, [
                'label' => '2FA activado',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'mapped' => false,
                'label_attr' => ['class' => 'form-check-label fw-bold text-success'],
            ])
            ->add('googleAuthenticatorSecret', TextType::class, [
                'label' => 'Google Authenticator Secret',
                'required' => false,
                'disabled' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('backupCodes', TextType::class, [
                'label' => 'Códigos de respaldo (JSON)',
                'required' => false,
                'disabled' => true,
                'attr' => ['class' => 'form-control'],
            ]);

        $builder->get('backupCodes')
            ->addModelTransformer(new CallbackTransformer(
                function ($backupCodesArray) {
                    // De array a string JSON para mostrar en el formulario
                    return $backupCodesArray ? json_encode($backupCodesArray) : '';
                },
                function ($backupCodesString) {
                    // De string JSON a array para guardar en la entidad
                    return $backupCodesString ? json_decode($backupCodesString, true) : [];
                }
            ));
            
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
