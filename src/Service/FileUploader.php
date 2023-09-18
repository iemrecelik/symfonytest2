<?php

// src/Service/FileUploader.php
namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploader
{
    private ?string $fullPathName = null;
    private ?string $newFileName = null;

    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $this->setFullPathName();
        $this->setNewFileName($file);

        try {
            $file->move(
                $this->getTargetDirectory().'/'.$this->getFullPathName(), 
                $this->getNewFileName()
            );
        } catch (FileException $e) {
            dd($e);
            // ... handle exception if something happens during file upload
        }

        return $this->getFullPathName().'/'.$this->getNewFileName();
    }

    public function remove(String $path): bool
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getTargetDirectory()."/".$path);
        return true;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Get the value of fullPathName
     */ 
    public function getFullPathName(): string
    {
        return $this->fullPathName;
    }

    /**
     * Set the value of fullPathName
     *
     */ 
    public function setFullPathName(): self
    {
        $this->fullPathName = implode('/', [
			date('Y'),
			date('m'),
			date('d'),
			date('H'),
		]);

        // $this->fullPathName = "{$pathName}/{$fileName}";
        // $this->fullPathName = $this->getTargetDirectory().'/'.$pathName;

        return $this;
    }

    public function getNewFileName(): string
    {
        return $this->newFileName;
    }

    public function setNewFileName(UploadedFile $file): static
    {
        /* $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension(); */
        $this->newFileName = uniqid().'.'.$file->guessExtension();

        return $this;
    }
}