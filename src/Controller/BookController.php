<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Service\FileUploader;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/book')]
class BookController extends AbstractController
{
    #[Route('/', name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book, [
            'validation_groups' => ['create'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($book->getAuthors() as $author) {
                $author->addBook($book);
            }

            $imageFileName = $fileUploader->upload($book->getImageFile());
            $book->setBkImageFilename($imageFileName);

            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(BookType::class, $book,  [
            'validation_groups' => ['update'],
        ]);

        $oldAuthors = clone $book->getAuthors();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newAuthors = $book->getAuthors();

            if($newAuthors) {
                foreach ($newAuthors as $author) {
                    $author->addBook($book);
                }   
            }

            foreach ($oldAuthors as $oldAuthor) {
                if(!$newAuthors->contains($oldAuthor)) {
                    $oldAuthor->removeBook($book);
                }
            }

            if($book->getImageFile()) {
                $oldImageFileName = $book->getBkImageFileName();
                $fileUploader->remove($oldImageFileName);

                $imageFileName = $fileUploader->upload($book->getImageFile());
                $book->setBkImageFilename($imageFileName);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
}
