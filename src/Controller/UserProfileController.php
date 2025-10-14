<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'app_user_profile')]
    public function index(#[CurrentUser] User $user): Response
    {
        
        
        return $this->render('user_profile/index.html.twig', [
            'user' => $user,
        ]);
        
    }

    #[Route('/change-password', name: 'app_user_change_password')]
    public function changePassword(Request $request, #[CurrentUser] User $user): Response
    {
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            
            // Verificar contraseña actual
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'La contraseña actual no es correcta.');
                return $this->redirectToRoute('app_user_change_password');
            }

            // Cambiar contraseña
            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $newPassword)
            );

            $this->entityManager->flush();

            $this->addFlash('success', 'Contraseña actualizada correctamente.');
            return $this->redirectToRoute('app_home');
            //return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user_profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Punto de entrada 
     */

    #[Route('/2fa-entry', name: 'app_user_2fa_entry')]
    public function entry2fa(#[CurrentUser] 
        User $user,
        Request $request,  
        EntityManagerInterface $entityManager,
        GoogleAuthenticatorInterface $googleAuthenticator
        ): Response
    {
        /**
         * El usuario tiene activado 2FA hay que desactivarlo 
         */
        if ($user->isGoogleAuthenticatorEnabled()) {
            //$this->addFlash('info', 'La autenticación de dos factores ya está activada.');
            // Hay que desactivarla por que la tiene activa
            // Hay que borrar también Secreto y Backup Codes. 
            $user->setGoogleAuthenticatorSecret(null);
            $user->setBackupCodes([]);
            $user->setGoogleAuthenticatorEnabled(false);
            
            $entityManager->flush();
            $this->addFlash('info', 'La autenticación de dos factores Se ha desactivado.');
            return $this->redirectToRoute('app_home');
           
        }
        /**
         * El usuario no tiene activado 2FA hay que activarlo. 
         */
        else {
            // Hay que activar 2FA 
            // Mostrar el código 
            // Mostrar los códigos de backup 
            
            $count_codes = 10; // Num. de codigos backup

            $secret = $this->generateSecret();
            $backupCodes = $this->generateBackupCodes($count_codes);
            $user->setGoogleAuthenticatorEnabled(true);
            $user->setGoogleAuthenticatorSecret($secret);
            $user->setBackupCodes($backupCodes);
            $entityManager->flush();
            //$this->addFlash('info', 'La Autenticación de 2FA se ha activado.');
            // Generamos un mensaje seguro con los datos
            /*$message = sprintf(
                "✅ Autenticación en dos pasos activada correctamente.<br><br>
                <strong>Secreto de Google Authenticator:</strong><br>
                <code>%s</code><br><br>
                <strong>Códigos de respaldo (%d):</strong><br>
                <code>%s</code><br><br>
                ⚠️ <strong>Guarda estos datos en un lugar seguro.</strong><br>
                Si pierdes el acceso a tu dispositivo, solo podrás entrar con estos códigos.",
                $secret,
                $count_codes,
                implode('<br>', $backupCodes)
            );

            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_home');*/
            // Guarda temporalmente en sesión (solo para mostrar en la siguiente vista)
           // Renderiza directamente la vista con los datos
            return $this->render('user_profile/2fa_show_codes.html.twig', [
                'secret' => $secret,
                'backupCodes' => $backupCodes,
                'countCodes' => $count_codes,
            ]);
        }

        // Generar secret para Google Authenticator
        $secret = $this->generateSecret();
        $user->setGoogleAuthenticatorSecret($secret);
        $this->entityManager->flush();

        // Generar QR code
        /*
        $qrCodeContent = $this->getQRContent($user, $secret);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $qrCodeDataUri = $result->getDataUri();
        */
        return $this->render('user_profile/enable_2fa.html.twig', [
            //'qrCode' => $qrCodeDataUri,
            'secret' => $secret,
        ]);
    }


    #[Route('/2fa/enable', name: 'app_user_2fa_enable')]
    public function enable2fa(#[CurrentUser] User $user): Response
    {
        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'La autenticación de dos factores ya está activada.');
            return $this->redirectToRoute('app_user_profile');
        }

        // Generar secret para Google Authenticator
        $secret = $this->generateSecret();
        $user->setGoogleAuthenticatorSecret($secret);
        $this->entityManager->flush();

        // Generar QR code
        $qrCodeContent = $this->getQRContent($user, $secret);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $qrCodeDataUri = $result->getDataUri();

        return $this->render('user_profile/enable_2fa.html.twig', [
            'qrCode' => $qrCodeDataUri,
            'secret' => $secret,
        ]);
    }

    #[Route('/2fa/confirm', name: 'app_user_2fa_confirm', methods: ['POST'])]
    public function confirm2fa(Request $request, #[CurrentUser] User $user): Response
    {
        $code = $request->request->get('code');

        if (!$this->verifyCode($user, $code)) {
            $this->addFlash('error', 'Código de verificación incorrecto. Inténtalo de nuevo.');
            return $this->redirectToRoute('app_user_2fa_enable');
        }

        // Activar 2FA después de verificar el código
        $user->setGoogleAuthenticatorEnabled(true);
        
        // Generar códigos de respaldo
        $backupCodes = $this->generateBackupCodes();
        $user->setBackupCodes($backupCodes);
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Autenticación de dos factores activada correctamente.');
        $this->addFlash('backup_codes', json_encode($backupCodes));
        
        return $this->redirectToRoute('app_user_2fa_backup_codes');
    }

    #[Route('/2fa/backup-codes', name: 'app_user_2fa_backup_codes')]
    public function showBackupCodes(#[CurrentUser] User $user): Response
    {
        if (!$user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('danger', 'Debes activar 2FA primero.');
            return $this->redirectToRoute('app_user_profile');
        }

        $backupCodesFlash = $this->getFlashes('backup_codes');
        $backupCodes = !empty($backupCodesFlash) ? json_decode($backupCodesFlash[0], true) : null;

        return $this->render('user_profile/backup_codes.html.twig', [
            'backup_codes' => $backupCodes,
        ]);
    }

    #[Route('/2fa/regenerate-backup-codes', name: 'app_user_2fa_regenerate_backup', methods: ['POST'])]
    public function regenerateBackupCodes(Request $request, #[CurrentUser] User $user): Response
    {
        /*
        if (!$this->isCsrfTokenValid('regenerate-backup', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');
            return $this->redirectToRoute('app_user_profile');
        }

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('error', '2FA no está activado.');
            return $this->redirectToRoute('app_user_profile');
        }

        $backupCodes = $this->generateBackupCodes();
        $user->setBackupCodes($backupCodes);
        $this->entityManager->flush();

        $this->addFlash('success', 'Códigos de respaldo regenerados. Guarda los nuevos códigos.');
        $this->addFlash('backup_codes', json_encode($backupCodes));

        return $this->redirectToRoute('app_user_2fa_backup_codes');
        */
        // Validar token CSRF
        if (!$this->isCsrfTokenValid('regenerate-backup', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');
            return $this->redirectToRoute('app_user_profile');
        }

        // Comprobar si el usuario tiene 2FA activo
        if (!$user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('error', '2FA no está activado.');
            return $this->redirectToRoute('app_user_profile');
        }

        // 🔒 Generar 10 nuevos códigos de respaldo aleatorios y únicos
        $codes = [];
        while (count($codes) < 10) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 caracteres hexadecimales
            $codes[$code] = true; // evitar duplicados
        }
        $backupCodes = array_keys($codes);

        // Guardar en el usuario
        $user->setBackupCodes($backupCodes);
        $this->entityManager->flush();

        // Mostrar mensaje y guardar en flash para renderizado posterior
        $this->addFlash('success', 'Códigos de respaldo regenerados. Guarda los nuevos códigos.');
        $this->addFlash('backup_codes', json_encode($backupCodes));

        // Redirigir a la vista donde se muestran los códigos
        return $this->redirectToRoute('app_user_2fa_backup_codes');

    }

    #[Route('/2fa/disable', name: 'app_user_2fa_disable', methods: ['POST'])]
    public function disable2fa(Request $request, #[CurrentUser] User $user): Response
    {
        if (!$this->isCsrfTokenValid('disable-2fa', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');
            return $this->redirectToRoute('app_user_profile');
        }

        $user->setGoogleAuthenticatorEnabled(false);
        $user->setGoogleAuthenticatorSecret(null);
        $user->setBackupCodes([]);
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Autenticación de dos factores desactivada.');
        return $this->redirectToRoute('app_user_profile');
    }

    // ------------------------
    // Métodos privados
    // ------------------------

    private function generateSecret(): string
    {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    private function generateBackupCodes(int $count): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }

    private function getQRContent(User $user, string $secret): string
    {
        $email = $user->getUserIdentifier();
        $issuer = 'GreenHarvest';
        
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer)
        );
    }

    private function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getGoogleAuthenticatorSecret();

        if (!$secret) {
            return false;
        }

        $timeSlice = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) {
            if ($this->calculateCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    private function calculateCode(string $secret, int $timeSlice): string
    {
        $secret = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount === $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) !== str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        foreach ($secret as $char) {
            if (!isset($base32charsFlipped[$char])) {
                return '';
            }
            $binaryString .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
        }
        
        $eightBits = str_split($binaryString, 8);
        $decoded = '';
        
        foreach ($eightBits as $binary) {
            if (strlen($binary) === 8) {
                $decoded .= chr(bindec($binary));
            }
        }
        
        return $decoded;
    }

    private function getFlashes(string $type): array
    {
        return $this->container->get('request_stack')->getSession()->getFlashBag()->get($type);
    }
}