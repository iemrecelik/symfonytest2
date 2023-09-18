<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Form\BookType;
use App\Entity\BookCategory;
use App\Service\FileUploader;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Repository\BookCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/book')]
class BookController extends AbstractController
{
    private $bookRepository;
    private $authorRepository;

    public function __construct(BookRepository $bookRepository, AuthorRepository $authorRepository)
    {
        $this->bookRepository = $bookRepository;
        $this->authorRepository = $authorRepository;
    }

    #[Route('/', name: 'app_api_book_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->get('page') ?? 50;
        
        $books = $this->bookRepository->listWithLimit($page);

        foreach ($books as $book) {
            $datas[] = [
                'book_name' => $book->getBkName(),
            ];
        }

        return new JsonResponse($datas, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_api_book_show', methods:['GET'])]
    public function show($id): JsonResponse
    {
        $book= $this->bookRepository->find(['id' => $id]);

        if (empty($id)) {
            return new JsonResponse(['status' => 'Abone bulunamadı.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $book->getId(),
            'book_name' => $book->getBkName(),
        ], Response::HTTP_CREATED);
    }

    #[Route(path: '/add', name: 'app_api_book_add', methods: ['POST'])]
    public function add(Request $request, FileUploader $fileUploader, ValidatorInterface $validator): JsonResponse
    {
        $params = $request->request->all();
        $imageFile = $request->files->get('imageFile');

        $bkName = $params['bkName'];
        $authorId = $params['authorId'];

        // dd($imageFile);

        $author = $this->authorRepository->find(['id' => $authorId]);
        $book = new Book();
        $book->setBkName($bkName);
        $book->addAuthor($author);
        $book->setImageFile($imageFile);

        $errors = $validator->validate($book, null, ['create']);

        if ($errors->count() > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return new JsonResponse($messages);
        }else {
            $imageFileName = $fileUploader->upload($imageFile);
            $book->setBkImageFileName($imageFileName);
            $this->bookRepository->storeBook($book);
        }

        return new JsonResponse(['status' => 'Abone ekleme işlemi başarılı.'], Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}/update', name: 'app_api_book_update', methods: ['POST'])]
    public function update(Book $book, Request $request, FileUploader $fileUploader, ValidatorInterface $validator)
    {
        $params = $request->request->all();
        $imageFile = $request->files->get('imageFile');

        $authorIds = $params['authorId'];

        $oldAuthors = clone $book->getAuthors();

        empty($params['bkName']) ? true : $book->setBkName($params['bkName']);
        empty($imageFile) ? true : $book->setImageFile($imageFile);
        
        $newAuthors = $this->authorRepository->findBy(['id' => $authorIds]);
        $newAuthors = new ArrayCollection($newAuthors);

        if (!empty($newAuthors)) {
            foreach ($newAuthors as $author) {
                $book->addAuthor($author);
            }

            foreach ($oldAuthors as $oldAuthor) {
                if(!$newAuthors->contains($oldAuthor)) {
                    $oldAuthor->removeBook($book);
                }
            }
        }

        $errors = $validator->validate($book, null, ['update']);

        if ($errors->count() > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return new JsonResponse($messages);
        }else {
            if (!empty($imageFile)) {
                $oldImageFileName = $book->getBkImageFileName();
                $fileUploader->remove($oldImageFileName);

                $imageFileName = $fileUploader->upload($imageFile);
                $book->setBkImageFileName($imageFileName);
            }
        }

        $this->bookRepository->updateBook($book);

        return new JsonResponse([
                'status' => "{$book->getId()} id'li {$book->getBkName()} isimli kitabın güncelleme işlemi başarılı."
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route(path: '/{id}', name: 'app_api_book_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete($id, EntityManagerInterface $em): JsonResponse
    {
        $book = $this->bookRepository->find($id);

        if (empty($book)) {
            return new JsonResponse(['status' => 'Kitap bulunamadı.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($book);
        $em->flush();

        return new JsonResponse(['status' => 'Kitap silindi.'], Response::HTTP_OK);
    }
}
