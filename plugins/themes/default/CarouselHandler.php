<?php

/**
 * @file plugins/themes/default/CarouselHandler.php
 *
 * @brief Carousel management page handler
 */

namespace APP\plugins\themes\default;

use APP\core\Application;
use APP\handler\Handler;
use APP\template\TemplateManager;
use APP\notification\NotificationManager;
use PKP\security\Role;
use PKP\notification\Notification;
use Illuminate\Support\Facades\DB;

class CarouselHandler extends Handler
{
    private string $imageDir;
    private string $imageUrl;

    public function __construct()
    {
        parent::__construct();
        $this->imageDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'carousel' . DIRECTORY_SEPARATOR;
        $this->imageUrl = Application::get()->getRequest()->getBaseUrl() . '/plugins/themes/default/img/carousel/';
        
        if (!is_dir($this->imageDir)) {
            mkdir($this->imageDir, 0755, true);
        }
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $roleAssignments = [
            Role::ROLE_ID_MANAGER => ['index', 'upload', 'delete', 'toggle', 'reorder'],
            Role::ROLE_ID_SITE_ADMIN => ['index', 'upload', 'delete', 'toggle', 'reorder'],
        ];
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function index($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        $slides = DB::table('carousel_slides')
            ->where('journal_id', $journalId)
            ->orderBy('display_order', 'asc')
            ->get()
            ->toArray();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'slides' => $slides,
            'imageUrl' => $this->imageUrl,
            'pageTitle' => 'Carousel Manager',
        ]);

        $templateFile = 'file:' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'carousel' . DIRECTORY_SEPARATOR . 'index.tpl';
        $templateMgr->display($templateFile);
    }

    public function upload($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        if (!$request->isPost()) {
            $this->redirectToIndex($request);
            return;
        }

        $uploadedFile = $_FILES['carousel_image'] ?? null;

        if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $this->notify($request, 'Error uploading file.', false);
            $this->redirectToIndex($request);
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($uploadedFile['tmp_name']);
        
        if (!in_array($fileType, $allowed)) {
            $this->notify($request, 'Only JPG, PNG and WebP allowed.', false);
            $this->redirectToIndex($request);
            return;
        }

        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            $this->notify($request, 'Image must be less than 5MB.', false);
            $this->redirectToIndex($request);
            return;
        }

        $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $filename = 'carousel_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $this->imageDir . $filename;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            $this->notify($request, 'Failed to save image.', false);
            $this->redirectToIndex($request);
            return;
        }

        $maxOrder = DB::table('carousel_slides')
            ->where('journal_id', $journalId)
            ->max('display_order') ?? 0;

        DB::table('carousel_slides')->insert([
            'journal_id' => $journalId,
            'image' => $filename,
            'display_order' => $maxOrder + 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->notify($request, 'Image uploaded successfully!', true);
        $this->redirectToIndex($request);
    }

    public function delete($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();
        $slideId = (int) ($request->getUserVar('id') ?? 0);

        if ($slideId) {
            $slide = DB::table('carousel_slides')
                ->where('id', $slideId)
                ->where('journal_id', $journalId)
                ->first();

            if ($slide) {
                $filePath = $this->imageDir . $slide->image;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                DB::table('carousel_slides')
                    ->where('id', $slideId)
                    ->where('journal_id', $journalId)
                    ->delete();

                $this->notify($request, 'Image deleted.', true);
            }
        }

        $this->redirectToIndex($request);
    }

    public function toggle($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();
        $slideId = (int) ($request->getUserVar('id') ?? 0);

        if ($slideId) {
            DB::table('carousel_slides')
                ->where('id', $slideId)
                ->where('journal_id', $journalId)
                ->update([
                    'status' => DB::raw('CASE WHEN status = 1 THEN 0 ELSE 1 END'),
                    'updated_at' => now(),
                ]);
        }

        $this->redirectToIndex($request);
    }

    public function reorder($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        if ($request->isPost()) {
            $order = $request->getUserVar('order');
            
            if (is_array($order)) {
                foreach ($order as $slideId => $position) {
                    DB::table('carousel_slides')
                        ->where('id', (int) $slideId)
                        ->where('journal_id', $journalId)
                        ->update([
                            'display_order' => (int) $position,
                            'updated_at' => now(),
                        ]);
                }
                $this->notify($request, 'Order updated.', true);
            }
        }

        $this->redirectToIndex($request);
    }

    private function notify($request, string $message, bool $isSuccess = true): void
    {
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            $request->getUser()->getId(),
            $isSuccess ? Notification::NOTIFICATION_TYPE_SUCCESS : Notification::NOTIFICATION_TYPE_ERROR,
            ['contents' => $message]
        );
    }

    private function redirectToIndex($request)
    {
        $context = $request->getContext();
        $url = $request->getDispatcher()->url(
            $request,
            \PKP\core\PKPApplication::ROUTE_PAGE,
            $context->getPath(),
            'carousel'
        );
        $request->redirectUrl($url);
    }
}