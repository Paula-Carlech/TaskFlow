<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class TaskController extends AbstractController
{
    #[Route('/tasks', name: 'task_index')]
    public function index(TaskRepository $taskRepository): Response
    {
        $newTasks = $taskRepository->findBy(['status' => 'new']);
        $inProgressTasks = $taskRepository->findBy(['status' => 'in_progress']);
        $finishedTasks = $taskRepository->findBy(['status' => 'completed']);

        return $this->render('task/index.html.twig', [
            'new_tasks' => $newTasks,
            'in_progress_tasks' => $inProgressTasks,
            'finished_tasks' => $finishedTasks,
        ]);
    }

    #[Route('/tasks/new', name: 'task_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit')]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/status', name: 'task_update_status', methods: ['POST'])]
    public function updateStatus(Task $task, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($csrfTokenManager->isTokenValid(new CsrfToken('task_status', $data['_csrf_token']))) {
            $task->setStatus($data['status']);
            $entityManager->flush();

            return new JsonResponse(['status' => 'ok'], 200);
        }

        return new JsonResponse(['status' => 'error'], 400);
    }
}
