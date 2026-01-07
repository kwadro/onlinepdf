<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TinyMceUploadController extends AbstractController
{
    #[Route(
        '/admin/tinymce/upload',
        name: 'admin_tinymce_upload',
        methods: ['POST'])
    ]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/tinymce',
            $filename
        );

        return new JsonResponse([
            'location' => '/uploads/tinymce/' . $filename
        ]);
    }
}
