<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')] // Solo los admins pueden entrar a gestionar usuarios
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher // Inyectamos el hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Asignamos la contraseña por defecto
            $hashedPassword = $passwordHasher->hashPassword($user, 'nopassword');
            $user->setPassword($hashedPassword);

            // Asignamos el rol de usuario
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/import', name: 'app_user_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $results = null;

        if ($request->isMethod('POST')) {
            $file = $request->files->get('excel_file');

            if (!$file || !in_array($file->getClientOriginalExtension(), ['xlsx', 'xls', 'ods'])) {
                $this->addFlash('error', 'Formato de archivo no válido. Usa .xlsx, .xls u .ods.');
                return $this->redirectToRoute('app_user_import');
            }

            $spreadsheet = IOFactory::load($file->getPathname());

            // Buscar hoja "INFO JUGADORES", si no usar la primera
            $sheet = $spreadsheet->getSheetByName('INFO JUGADORES') ?? $spreadsheet->getActiveSheet();

            $created = [];
            $skipped = [];
            $errors  = [];
            $seenPhones = []; // teléfonos ya procesados en este batch

            $firstRow = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($firstRow) { $firstRow = false; continue; } // saltar cabecera

                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);

                $cols = [];
                foreach ($cells as $cell) {
                    $cols[] = trim((string) $cell->getValue());
                }

                // Columnas: 0=NOMBRE, 1=LIGA, 2=INSTAGRAM, 3=TELEFONO, 4=FECHA NAC
                $name  = $cols[0] ?? '';
                $phone = preg_replace('/\s+/', '', $cols[3] ?? ''); // quitar todos los espacios

                if (empty($name) && empty($phone)) continue; // fila vacía

                if (empty($name)) {
                    $errors[] = "Fila sin nombre (teléfono: {$phone})";
                    continue;
                }
                if (empty($phone)) {
                    $errors[] = "Fila sin teléfono (nombre: {$name})";
                    continue;
                }

                // Duplicado dentro del mismo Excel
                if (isset($seenPhones[$phone])) {
                    $skipped[] = "{$name} ({$phone}) — duplicado en el Excel";
                    continue;
                }

                // Comprobar si ya existe por teléfono en BDD (normalizando espacios en ambos lados)
                $conn = $entityManager->getConnection();
                $existingId = $conn->fetchOne(
                    'SELECT id FROM "user" WHERE REPLACE(phone, \' \', \'\') = ?',
                    [$phone]
                );
                if ($existingId) {
                    $skipped[] = "{$name} ({$phone})";
                    continue;
                }

                $seenPhones[$phone] = true;

                $user = new User();
                $user->setName($name)
                     ->setPhone($phone)
                     ->setRoles(['ROLE_USER'])
                     ->setPassword('NO_PASSWORD');
                $entityManager->persist($user);
                $created[] = "{$name} ({$phone})";
            }

            $entityManager->flush();

            $results = ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
        }

        return $this->render('user/import.html.twig', ['results' => $results]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher // <--- Inyectar
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si por alguna razón la contraseña se quedó vacía o corrupta, la reseteamos
            if (!$user->getPassword()) {
                $user->setPassword($passwordHasher->hashPassword($user, 'nopassword'));
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
