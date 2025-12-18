<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Story;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\StoryRepository;

class StoryController extends AbstractController
{
    #[Route('/api/stories', name: 'api_story_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        Filesystem $fs,
        #[Autowire('%stories_upload_dir%')] string $stories_upload_dir
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], 401);
        }

        $title = trim((string) $request->request->get('title', ''));
        $content = (string) $request->request->get('content', '');

        $story = new Story();
        $story->setTitle($title);
        $story->setContent($content);
        $story->setAuthor($user);

        // Validation (title not blank, content max 25000, etc.)
        $errors = $validator->validate($story);
        if (count($errors) > 0) {
            $out = [];
            foreach ($errors as $e) {
                $out[] = ['field' => $e->getPropertyPath(), 'message' => $e->getMessage()];
            }
            return $this->json(['message' => 'Validation failed', 'errors' => $out], 422);
        }

        // 1) Persist + flush pour obtenir l'ID
        $em->persist($story);
        $em->flush();

        $tagSlugs = $request->request->all('tags'); // correspond à tags[]

        if (!is_array($tagSlugs)) {
            $tagSlugs = [];
        }

        // normaliser: strings, uniques, non vides
        $tagSlugs = array_values(array_unique(array_filter(array_map('strval', $tagSlugs))));

        if (count($tagSlugs) > 0) {
            $tagRepo = $em->getRepository(Tag::class);

            foreach ($tagSlugs as $slug) {
                $tag = $tagRepo->findOneBy(['slug' => $slug]);

                // Choix strict (recommandé): si le front envoie un tag inconnu => 422
                if (!$tag) {
                    return $this->json(['message' => "Unknown tag: $slug"], 422);
                }

                $story->addTag($tag);
            }
        }

        $id = $story->getId();
        $storyDir = rtrim($stories_upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $id;

        if (!$fs->exists($storyDir)) {
            $fs->mkdir($storyDir, 0775);
        }

        // 2) Image upload (optionnel)
        /** @var UploadedFile|null $image */
        $image = $request->files->get('image');
        if ($image instanceof UploadedFile) {
            // (simple checks)
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($image->getMimeType(), $allowed, true)) {
                return $this->json(['message' => 'Invalid image type (jpeg/png/webp only)'], 415);
            }

            $ext = $image->guessExtension() ?: $image->getClientOriginalExtension() ?: 'bin';
            $imageFilename = $id . '.' . $ext;

            $image->move($storyDir, $imageFilename);

            $story->setImgLink('/uploads/stories/' . $id . '/' . $imageFilename);
        }

        // 3) PDF auto généré (toujours)
        $pdfFilename = $id . '.pdf';
        $pdfPath = $storyDir . DIRECTORY_SEPARATOR . $pdfFilename;

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // support accents
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        // Force UTF-8 clean (évite iconv incomplete multibyte)
        $title = mb_convert_encoding($story->getTitle(), 'UTF-8', 'UTF-8');
        $content = mb_convert_encoding($story->getContent(), 'UTF-8', 'UTF-8');

        $html = $this->renderView('pdf/story.html.twig', [
            'title' => $title,
            'content' => $content,
            'author' => $user->getPseudo(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());

        $story->setPdfLink('/uploads/stories/' . $id . '/' . $pdfFilename);

        // 4) Update story with links
        $em->flush();

        return $this->json([
            'id' => $story->getId(),
            'title' => $story->getTitle(),
            'contentLength' => mb_strlen($story->getContent()),
            'tags' => array_map(
                fn (Tag $t) => ['title' => $t->getTitle(), 'slug' => $t->getSlug()],
                $story->getTags()->toArray()
            ),
            'imgLink' => $story->getImgLink(),
            'pdfLink' => $story->getPdfLink(),
        ], 201);
    }

    #[Route('/api/stories', name: 'api_story_list', methods: ['GET'])]
    public function list(Request $request, StoryRepository $repo): JsonResponse
    {
        $tagSlugs = $request->query->all('tags'); // tags[]=...
        if (!is_array($tagSlugs)) {
            $tagSlugs = [];
        }

        // normaliser : strings, uniques, non vides
        $tagSlugs = array_values(array_unique(array_filter(array_map('strval', $tagSlugs))));

        $stories = $repo->findAllFilteredByTags($tagSlugs);

        return $this->json(array_map(function (Story $s) {
            return [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'imgLink' => $s->getImgLink(),
                'pdfLink' => $s->getPdfLink(),
                'author' => [
                    'id' => $s->getAuthor()->getId(),
                    'pseudo' => $s->getAuthor()->getPseudo(),
                ],
                'tags' => array_map(
                    fn(Tag $t) => ['title' => $t->getTitle(), 'slug' => $t->getSlug()],
                    $s->getTags()->toArray()
                ),
            ];
        }, $stories));
    }
}
